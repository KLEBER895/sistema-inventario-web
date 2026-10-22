# Sistema de Inventario Web

Aplicación web para la gestión de productos, clientes, ventas, compras, usuarios y control de inventario.

## Equipo
**Innovación Web**

### Integrantes
- Kleber Iván Peña Cadena
- Christel Scarlet Alvear Pamintuan
- Evelyn Bethzabe Ramírez Ponce
- Jorge Elías Zavala García

## Tecnologías utilizadas
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- GitHub

## Módulos principales
- Productos
- Clientes
- Ventas
- Compras
- Usuarios
- Reportes y control de inventario

## Modelo de autenticación

**Sesión + cookie**

Se eligió este modelo porque el Sistema de Inventario Web está desarrollado en PHP y MySQL. Es una alternativa sencilla para mantener identificado al usuario y controlar los roles del sistema, reduciendo la complejidad de implementación.

## Roles

El sistema trabaja con roles simples:

- **Administrador:** acceso a las funciones administrativas autorizadas.
- **Empleado:** acceso a las funciones permitidas según su rol.

## Estado del proyecto

Proyecto académico en desarrollo para la materia Web Application Capstone, orientado a la creación de un Sistema de Inventario Web.

## Buenas prácticas de seguridad

Para fortalecer la seguridad del Sistema de Inventario Web se consideran las siguientes prácticas:

- Las contraseñas deben almacenarse utilizando hash seguro y nunca en texto plano.
- Las consultas a la base de datos deben realizarse de forma parametrizada para prevenir inyección SQL.
- La autenticación se gestiona mediante sesión y cookies.
- Los roles y permisos deben verificarse en el servidor antes de permitir acciones administrativas.
- Los mensajes de error no deben mostrar información sensible del sistema.
- Las acciones importantes del sistema deben registrarse sin almacenar contraseñas ni datos sensibles.

## Mejoras de seguridad pendientes

Como parte de la mejora continua del Sistema de Inventario Web, se consideran las siguientes acciones:

- Implementar límite de intentos en el inicio de sesión.
- Incorporar una tabla de auditoría para registrar acciones importantes.
- Revisar el escape y sanitización de datos mostrados en pantalla.
- Configurar el entorno de producción para no mostrar errores sensibles.
- Revisar periódicamente las dependencias del proyecto.

## Configuración local (XAMPP)

1. Clonar el repositorio dentro de `htdocs` e instalar dependencias:
   ```bash
   composer install
   copy .env.example .env        # en Linux/Mac: cp .env.example .env
   ```
2. Editar `.env`: `DB_*` con los datos de XAMPP, `MAIL_*` con la cuenta SMTP y
   `MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe`.
3. Base de datos:
   - **Base nueva y vacía** (creada en phpMyAdmin): `php scripts/migrar.php` crea las
     tablas y `php scripts/crear_admin.php` crea el primer administrador.
   - **Base que ya tenías con tablas y datos:** ejecutar una sola vez
     `php scripts/migrar.php --marcar-aplicadas`, para que no intente volver a crearlas.

El archivo `.env` nunca se sube a GitHub, y el repositorio no guarda datos
(clientes, ventas, usuarios). Las variables están explicadas en `.env.example`.

## Levantar el sistema en un servidor

Proceso para un servidor nuevo, con AlwaysData como ejemplo. Sirve para
cualquier hosting con PHP, MySQL/MariaDB, `git`, `composer` y acceso SSH.
Se hace una sola vez; después cada merge a `main` actualiza el servidor solo.

| Requisito | Detalle |
|---|---|
| PHP | 8.1 o superior, con la extensión `mysqli` |
| Base de datos | MySQL o MariaDB, con una base **vacía** creada para el sistema |
| Herramientas en el servidor | `git`, `composer` y `mysqldump` (AlwaysData los trae) |
| Acceso | SSH al servidor y permisos de administrador en el repositorio de GitHub |

En los ejemplos, `CUENTA` es el nombre de la cuenta del hosting. En AlwaysData,
el usuario SSH tiene la forma `CUENTA_user` y la carpeta pública es `~/www`.

### 1. Preparar el hosting (panel de AlwaysData)

1. **Bases de datos → MySQL:** crear una base y un usuario con acceso a ella.
   Anotar servidor (`mysql-CUENTA.alwaysdata.net`), nombre de la base, usuario y clave.
2. **Acceso remoto → SSH:** activar el acceso SSH del usuario.
3. **Entorno → PHP:** elegir PHP 8.1 o superior.
4. **Web → Sitios:** sitio de tipo **PHP** con carpeta raíz `/www/` y **HTTPS** activado.

### 2. Descargar el código (por SSH)

```bash
ssh CUENTA_user@ssh-CUENTA.alwaysdata.net
mv ~/www ~/www_original        # git clone necesita una carpeta vacía
git clone https://github.com/KLEBER895/sistema-inventario-web.git ~/www
cd ~/www
```

### 3. Configurar las variables de entorno

```bash
cp .env.example .env
nano .env
chmod 600 .env
mkdir -p ~/respaldos
```

Valores de producción (los que no aparecen se dejan como en `.env.example`):

| Variable | Valor |
|---|---|
| `APP_ENV` | `produccion` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://CUENTA.alwaysdata.net` (sin `/` al final) |
| `DB_HOST` | `mysql-CUENTA.alwaysdata.net` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Los del paso 1, copiados exactamente |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Cuenta SMTP (Brevo) |
| `BACKUP_DIR` | `/home/CUENTA/respaldos` (fuera de `~/www`) |

### 4. Instalar dependencias y verificar

```bash
composer install --no-dev --optimize-autoloader
php scripts/verificar_entorno.php
```

Debe responder `Variables de entorno correctas.`. Si no, lista lo que falta en `.env`.

### 5. Crear la base de datos y el administrador

```bash
php scripts/migrar.php        # respaldo previo + crea las tablas
php scripts/crear_admin.php   # pide nombre, usuario, correo y contraseña
```

No se importa ninguna exportación de phpMyAdmin: las tablas salen de
`database/migraciones/` y el resto de datos se carga desde la aplicación.

### 6. Comprobar

Abrir `https://CUENTA.alwaysdata.net/login.php` e ingresar con el administrador.
Con esto el sistema ya está en línea. Los pasos 7 y 8 automatizan las actualizaciones.

### 7. Llave de despliegue (GitHub → servidor)

En una PC (PowerShell en Windows), generar una llave **sin passphrase** solo para despliegues:

```powershell
ssh-keygen -t ed25519 -f deploy_inventario -C "github-actions"
ssh-keyscan ssh-CUENTA.alwaysdata.net | Out-File -Encoding ascii known_hosts.txt
```

En el servidor, agregar el contenido de `deploy_inventario.pub` al final de
`~/.ssh/authorized_keys`, en **una sola línea** y con esta restricción delante:

```
command="bash ~/www/scripts/desplegar.sh",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ssh-ed25519 AAAA... github-actions
```

```bash
chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys
```

Con `command=`, esa llave solo puede ejecutar el despliegue: no sirve para abrir una terminal.

### 8. Configurar GitHub (dueño del repositorio)

En *Settings → Environments → produccion*:

| Tipo | Nombre | Valor |
|---|---|---|
| Variable | `SSH_HOST` | `ssh-CUENTA.alwaysdata.net` |
| Variable | `SSH_USER` | `CUENTA_user` |
| Variable | `DEPLOY_PATH` | `www` |
| Variable | `APP_URL` | `https://CUENTA.alwaysdata.net` |
| Secret | `SSH_PRIVATE_KEY` | Contenido completo de `deploy_inventario` |
| Secret | `SSH_KNOWN_HOSTS` | Contenido de `known_hosts.txt` |

En *Deployment branches* limitar el environment a `main`. En *Settings → Branches*,
proteger `main` exigiendo Pull Request y el chequeo `verificar`. Después, borrar
`deploy_inventario` y `known_hosts.txt` de la PC.

Para probar: *Actions → Desplegar en AlwaysData → Run workflow*. Debe terminar en verde.

## Cómo se actualiza el sistema

```
Pull Request a main ──► verificar (sintaxis PHP, composer, sin credenciales ni volcados SQL)
merge a main ───────► verificar ──► SSH ──► scripts/desplegar.sh
                                             ├ git pull (solo si nadie editó a mano)
                                             ├ composer install
                                             ├ verificar_entorno.php
                                             └ migrar.php (respaldo + migraciones nuevas)
                                    ──► comprueba que APP_URL/login.php responde
```

Para actualizar a mano (por ejemplo, si GitHub Actions no está disponible):
`bash ~/www/scripts/desplegar.sh`. El detalle técnico de cada cambio está en
[CORRECCIONES.md](CORRECCIONES.md).

## Reglas del equipo

- Todo cambio entra a `main` por Pull Request: lo que llega a `main` se publica.
- Los cambios de base de datos van como archivo nuevo en `database/migraciones/`
  (`002_...sql`, `003_...sql`), solo con estructura. Nunca exportaciones con datos.
- Nunca modificar una migración que ya llegó a `main`: crear una nueva.
- Una variable de entorno nueva se agrega a `.env.example` y al `.env` del servidor
  (y a `scripts/verificar_entorno.php` si es obligatoria) **antes** de integrar el PR.
- Nunca editar archivos directamente en el servidor: el despliegue se detiene
  si encuentra cambios hechos a mano.
- Los respaldos quedan en `BACKUP_DIR` (`~/respaldos` en el servidor), fuera de la web.

## Solución de problemas

| Síntoma | Causa | Solución |
|---|---|---|
| `Connection refused` o `Access denied` al abrir el sitio | `DB_*` del `.env` incorrectos | Copiar los datos exactos del panel de MySQL |
| `Table '...usuarios' doesn't exist` | No se crearon las tablas | `php scripts/migrar.php` |
| `migrar.php` dice `No se pudo generar el respaldo` | `mysqldump` no conecta (ruta, credenciales o SSL) | Revisar `MYSQLDUMP_PATH` y `DB_*`; no se aplica nada sin respaldo |
| `Table '...' already exists` al migrar en una base existente | La base ya tenía las tablas | `php scripts/migrar.php --marcar-aplicadas` (una sola vez) |
| El despliegue falla en `git merge --ff-only` | Hay archivos editados a mano en el servidor | `git status` en `~/www` y descartar o llevar esos cambios a un PR |
| El despliegue falla con `Permission denied (publickey)` | La llave no está en `authorized_keys` o el secreto está incompleto | Repetir los pasos 7 y 8 |
| El correo de recuperación no llega | `MAIL_*` incorrectos | Revisar el log de errores de PHP en el panel del hosting |
