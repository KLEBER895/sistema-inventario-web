# Correcciones para el despliegue automático

**Fecha:** 21/10/2026
**Rama:** `feature/despliegue-automatico`

Este documento explica qué impedía desplegar el sistema de forma sencilla,
qué se cambió y qué tiene que hacer cada integrante del equipo después de
integrar estos cambios.

---

## 1. Problemas encontrados

| # | Problema | Dónde estaba | Por qué impedía desplegar |
|---|----------|--------------|---------------------------|
| 1 | Configuración repartida en varios archivos PHP privados (`config_db.php`, `config_mail.php`) | `conexion.php`, `olvide_clave.php` | Cada servidor necesitaba crear y mantener a mano varios archivos PHP con formato propio. No había una lista única de lo que el sistema necesita. |
| 2 | Ruta de Windows y usuario `root` fijos en el respaldo: `C:\xampp\mysql\bin\mysqldump -u root inventario_web` | `backup.php`, `crear_respaldo.php` | En el servidor Linux el comando no existe, el usuario no es `root` y la base tiene otro nombre: el módulo **Respaldos** fallaba sin avisar. |
| 3 | Respaldos guardados en `respaldos/` dentro de la carpeta pública y descargados por enlace directo | `backup.php`, `lista_respaldos.php` | Expone una copia completa de la base (con hashes de contraseñas). Además, `.htaccess` ya bloqueaba `respaldos/`, así que el botón **Descargar** daba 404 en producción. |
| 4 | El enlace de recuperación de contraseña se armaba con la cabecera `Host` de la petición | `olvide_clave.php` | Un atacante puede enviar una cabecera `Host` falsa: el correo legítimo le llega a la víctima pero el enlace con el token apunta al dominio del atacante (*password reset poisoning*). |
| 5 | SMTP sin cifrado forzado (`SMTPAutoTLS = false`, `SMTPSecure = false`) | `olvide_clave.php` | Era un ajuste para pruebas locales, pero en producción la clave SMTP y el enlace con el token viajaban en texto plano. |
| 6 | Zona horaria fija en código (`America/Guayaquil` y `'-05:00'`) | `conexion.php` | No se podía cambiar por servidor y el desfase de MySQL estaba duplicado a mano. |
| 7 | Sin control de errores por entorno | (todo el sistema) | En producción PHP podía mostrar rutas, consultas y datos internos en pantalla. |
| 8 | Línea en blanco antes de `<?php` en `conexion.php` | `conexion.php` | Enviaba un salto de línea antes de las cabeceras; en servidores sin *output buffering* rompe `header("Location: ...")`. |
| 9 | El despliegue no comprobaba la configuración del servidor | `scripts/desplegar.sh` | Si faltaba una credencial, el despliegue llegaba a las migraciones y fallaba a mitad de camino. |
| 10 | Todos los datos de GitHub Actions como secretos, sin *environment* | `.github/workflows/desplegar.yml` | No se distinguía lo sensible de lo público y no se verificaban los Pull Requests antes de integrarlos. |
| 11 | Archivo duplicado sin uso | `crear_respaldo.php` | Mismo código roto que `backup.php`; ningún menú lo enlazaba. |

---

## 2. Solución: variables de entorno

Toda la configuración se lee ahora desde **variables de entorno**, a través de
un único archivo: `configuracion.php`.

```
Variable real del servidor  ─┐
                             ├─►  configuracion.php  ─►  $config['db'], $config['mail'], ...
Archivo .env (no versionado) ┘         (valores por defecto solo para lo no sensible)
```

- Si la variable existe en el servidor (panel del hosting, Docker, etc.) se usa esa.
- Si no, se toma del archivo `.env` de la raíz del proyecto.
- `.env` está en `.gitignore` y bloqueado para el navegador en `.htaccess`.
- `.env.example` sí se versiona: es la lista oficial de variables.

### Variables definidas

| Variable | Obligatoria en producción | Ejemplo / valor por defecto | Uso |
|----------|:---:|---------|-----|
| `APP_ENV` | Sí | `local` / `produccion` | En `produccion` se ocultan los errores en pantalla. |
| `APP_DEBUG` | — | `true` en local, `false` en producción | Muestra u oculta errores de PHP. |
| `APP_URL` | Sí | `https://CUENTA.alwaysdata.net` | Base de los enlaces enviados por correo. Sin `/` final. |
| `APP_TIMEZONE` | — | `America/Guayaquil` | Zona horaria de PHP; el desfase de MySQL se calcula a partir de ella. |
| `DB_HOST` | Sí | `localhost` | Servidor MySQL. |
| `DB_PORT` | — | `3306` | Puerto MySQL. |
| `DB_DATABASE` | Sí | `inventario_web` | Nombre de la base. |
| `DB_USERNAME` | Sí | `root` | Usuario MySQL. |
| `DB_PASSWORD` | Sí | *(vacío en XAMPP)* | Contraseña MySQL. |
| `MAIL_HOST` | Sí | `smtp-relay.brevo.com` | Servidor SMTP. |
| `MAIL_PORT` | — | `587` | Puerto SMTP. |
| `MAIL_ENCRYPTION` | Sí (`tls`/`ssl`) | `tls` | `tls`, `ssl` o `ninguno` (solo pruebas locales). |
| `MAIL_USERNAME` | Sí | — | Usuario SMTP. |
| `MAIL_PASSWORD` | Sí | — | Clave SMTP. |
| `MAIL_FROM_ADDRESS` | Sí | — | Remitente de los correos. |
| `MAIL_FROM_NAME` | — | `Sistema Inventario Web` | Nombre del remitente. |
| `BACKUP_DIR` | Sí (fuera del proyecto) | `respaldos/` del proyecto | Carpeta de los respaldos `.sql`. |
| `MYSQLDUMP_PATH` | — | `mysqldump` | En XAMPP: `C:\xampp\mysql\bin\mysqldump.exe`. |

---

## 3. Cambios archivo por archivo

### Archivos nuevos

| Archivo | Para qué sirve |
|---------|----------------|
| `configuracion.php` | Lee `.env` y las variables del servidor, define zona horaria y muestra/oculta errores. Devuelve el arreglo `$config`. |
| `.env.example` | Plantilla documentada con todas las variables. |
| `respaldo.php` | Función `generar_respaldo()` compartida por el módulo **Respaldos** y por las migraciones. Usa las mismas credenciales de la conexión; la contraseña va en un archivo temporal `0600`, nunca en la línea de comandos. |
| `descargar_respaldo.php` | Descarga de respaldos solo para administradores. Valida el nombre del archivo (evita `../`). |
| `scripts/verificar_entorno.php` | Revisa que el `.env` del servidor esté completo y sea apto para producción (`APP_ENV=produccion`, `APP_DEBUG=false`, `APP_URL` con `https://`, SMTP cifrado, `BACKUP_DIR` fuera del proyecto). No imprime contraseñas. |
| `CORRECCIONES.md` | Este documento. |

### Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `conexion.php` | Usa `configuracion.php` en lugar de `config_db.php`. Mantiene `$conexion`, `$servidor`, `$usuario`, etc., así que **ningún módulo existente tuvo que cambiar**. Se quitó la línea en blanco antes de `<?php`. El `SET time_zone` se calcula desde `APP_TIMEZONE`. |
| `olvide_clave.php` | SMTP desde `MAIL_*`. Cifrado según `MAIL_ENCRYPTION`. El enlace usa `APP_URL`; la detección por `HTTP_HOST` solo queda si `APP_URL` está vacía (pruebas locales). |
| `backup.php` | Usa `generar_respaldo()`. Muestra un mensaje real si falla (antes siempre decía "correctamente"). |
| `lista_respaldos.php` | Lista desde `BACKUP_DIR`, más reciente primero, y descarga por `descargar_respaldo.php`. |
| `scripts/migrar.php` | El respaldo previo usa `generar_respaldo()` y se guarda en `BACKUP_DIR`. |
| `scripts/desplegar.sh` | Ejecuta `verificar_entorno.php` antes de las migraciones. |
| `.github/workflows/desplegar.yml` | Ver sección 4. |
| `.htaccess` | Bloquea todos los archivos ocultos (`.env`, etc.) y los `*.example`. |
| `README.md` | Instrucciones de configuración local y del servidor con `.env`. |

### Archivos eliminados

| Archivo | Motivo |
|---------|--------|
| `crear_respaldo.php` | Duplicado de `backup.php`, sin uso, con la ruta de XAMPP fija. |
| `config_db.example.php` | Reemplazado por `.env.example`. |

---

## 4. GitHub Actions

Flujo de `.github/workflows/desplegar.yml`:

```
Pull Request a main ──► verificar  (sintaxis PHP, composer.json, que no se suban credenciales)

push a main ─────────► verificar ──► desplegar (environment "produccion")
                                        ├─ SSH: scripts/desplegar.sh
                                        │     git pull → composer install →
                                        │     verificar_entorno → respaldo → migraciones
                                        └─ curl APP_URL/login.php  (el sitio responde)
```

**Qué va en GitHub y qué no:**

| Dato | Dónde se guarda | Tipo |
|------|-----------------|------|
| `SSH_HOST`, `SSH_USER`, `DEPLOY_PATH`, `APP_URL` | GitHub → Environment `produccion` | *Variable* (no sensible) |
| `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS` | GitHub → Environment `produccion` | *Secret* |
| `DB_*`, `MAIL_*`, `BACKUP_DIR`… | Solo en el `.env` del servidor | Nunca pasan por GitHub |

Se decidió que las credenciales de la aplicación **no** viajen por GitHub
Actions: viven únicamente en el servidor. Así, si alguien obtiene acceso a los
secretos del repositorio, solo obtiene una llave de despliegue, no la base de
datos ni el correo.

> **Importante:** los secretos que antes estaban en *Repository secrets*
> (`SSH_HOST`, `SSH_USER`, `DEPLOY_PATH`) deben moverse al environment
> `produccion` como **variables**. Si no, el despliegue fallará al conectar.

---

## 5. Qué debe hacer cada integrante

### En su PC (una vez)

```bash
git pull
cp .env.example .env            # Windows: copy .env.example .env
```

Completar `.env` con los mismos datos que tenían en `config_db.php` y
`config_mail.php`, por ejemplo:

```ini
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=inventario_web
MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe
MAIL_ENCRYPTION=ninguno          # solo si su red local tenía el problema del certificado
```

Luego pueden borrar `config_db.php` y `config_mail.php`: ya no se usan.

### En el servidor (una vez)

```bash
cd ~/inventario
cp .env.example .env && nano .env
chmod 600 .env
mkdir -p ~/respaldos
php scripts/verificar_entorno.php   # debe decir "Variables de entorno correctas."
rm -f config_db.php config_mail.php
```

Valores clave en producción: `APP_ENV=produccion`, `APP_DEBUG=false`,
`APP_URL=https://...`, `MAIL_ENCRYPTION=tls`, `BACKUP_DIR=/home/CUENTA/respaldos`.

Si existían respaldos en `~/inventario/respaldos/`, moverlos a `~/respaldos/`.

### En GitHub (una vez)

*Settings → Environments → New environment* → `produccion`, y cargar las
variables y secretos de la tabla de la sección 4.

---

## 6. Cómo se verificó

- `php -l` sobre todos los archivos PHP del proyecto: sin errores.
- `composer validate`: válido.
- Sintaxis de `scripts/desplegar.sh` (`bash -n`) y del workflow (YAML): correctas.
- `configuracion.php`: lee `.env`, respeta comillas y da prioridad a la variable
  real del servidor sobre `.env`; zona horaria `America/Guayaquil` → `-05:00`.
- `verificar_entorno.php`: rechaza la configuración local de ejemplo (lista
  los 9 problemas) y acepta una configuración de producción completa.
- `generar_respaldo()`: probado con un `mysqldump` simulado y una contraseña
  con comillas y `$`; el archivo se crea en `BACKUP_DIR` y el archivo temporal
  de credenciales se elimina.

**Pendiente de probar con base real:** generar y descargar un respaldo desde
el módulo **Respaldos**, y enviar un correo de recuperación en el servidor.

---

## 7. Pendientes recomendados (fuera de este cambio)

De la lista *Mejoras de seguridad pendientes* del `README.md`, este cambio
cubre **"Configurar el entorno de producción para no mostrar errores
sensibles"** (`APP_ENV=produccion` / `APP_DEBUG=false`, controlado por
`scripts/verificar_entorno.php`). Las demás siguen pendientes.

- **Cookies de sesión:** cada archivo llama a `session_start()` antes de
  cargar la configuración. Conviene un `sesion.php` común que active
  `session.cookie_secure`, `httponly` y `samesite` antes de iniciar la sesión.
- **Chart.js sin versión fija** en `index.php`
  (`https://cdn.jsdelivr.net/npm/chart.js`): fijar una versión exacta para
  que una actualización externa no rompa el panel.
- **Esquema completo de la base:** el repositorio solo tiene `productos.sql`
  y un complemento. Agregar una exportación de la estructura completa (sin
  datos) permitiría crear un servidor nuevo sin depender de una base local.
- **`dompdf/` copiado dentro del repositorio:** podría instalarse con
  Composer igual que PHPMailer.
