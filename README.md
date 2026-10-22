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

Toda la configuración está en variables de entorno (ver `.env.example`).

```bash
cp .env.example .env      # en Windows: copy .env.example .env
composer install
```

En `.env` ajustar `DB_*`, `MAIL_*` y, en Windows,
`MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe`.
El archivo `.env` nunca se sube a GitHub.

Base de datos:

- **Base nueva y vacía:** `php scripts/migrar.php` crea todas las tablas y
  `php scripts/crear_admin.php` crea el primer administrador.
- **Base que ya tenías con tablas y datos:** ejecutar una sola vez
  `php scripts/migrar.php --marcar-aplicadas`, para que no intente volver a crearlas.

El repositorio no guarda datos: ni clientes, ni ventas, ni usuarios.

## Despliegue automático (AlwaysData)

Cada cambio integrado en `main` se publica solo: GitHub Actions revisa la
sintaxis PHP y luego, por SSH, ejecuta `scripts/desplegar.sh` en el servidor
(`git pull`, `composer install`, verificación de `.env` y migraciones de base
de datos con respaldo previo). En cada Pull Request solo se ejecutan las
verificaciones. El detalle de los cambios está en [CORRECCIONES.md](CORRECCIONES.md).

### Configuración inicial (una sola vez)

1. **Servidor (SSH en AlwaysData):**
   ```bash
   git clone https://github.com/KLEBER895/sistema-inventario-web.git ~/inventario
   cd ~/inventario
   cp .env.example .env && nano .env   # APP_ENV=produccion, APP_DEBUG=false, ...
   chmod 600 .env
   mkdir -p ~/respaldos                 # BACKUP_DIR=/home/CUENTA/respaldos
   composer install --no-dev --optimize-autoloader
   php scripts/verificar_entorno.php
   ```
2. **Base de datos** (vacía, creada en el panel de AlwaysData). No se importa
   ninguna exportación: las tablas salen de `database/migraciones/`.
   ```bash
   php scripts/migrar.php        # crea las tablas (001_estructura_inicial.sql)
   php scripts/crear_admin.php   # primer administrador
   ```
3. **Sitio web (panel de AlwaysData):** tipo PHP, carpeta raíz `/inventario/`, activar HTTPS.
4. **Llave de despliegue** (en tu PC):
   ```bash
   ssh-keygen -t ed25519 -f deploy_inventario -N "" -C "github-actions"
   ssh-copy-id -i deploy_inventario.pub USUARIO@ssh-CUENTA.alwaysdata.net
   ssh-keyscan ssh-CUENTA.alwaysdata.net
   ```
5. **GitHub** (*Settings → Environments → New environment* `produccion`):
   - *Environment variables:* `SSH_HOST`, `SSH_USER`, `DEPLOY_PATH` (`inventario`)
     y `APP_URL` (`https://CUENTA.alwaysdata.net`).
   - *Environment secrets:* `SSH_PRIVATE_KEY` (contenido de `deploy_inventario`)
     y `SSH_KNOWN_HOSTS` (salida de `ssh-keyscan`).
   - Después borrar la llave privada del PC.

### Reglas del equipo

- Todo cambio entra a `main` por Pull Request: lo que llega a `main` se publica.
- Los cambios de base de datos van como archivo nuevo en `database/migraciones/`.
- Una variable de entorno nueva se agrega a `.env.example` y al `.env` del servidor
  (y a `scripts/verificar_entorno.php` si es obligatoria) **antes** de integrar el PR.
- Nunca editar archivos directamente en el servidor: el despliegue se detiene
  si encuentra cambios hechos a mano.
- Los respaldos quedan en `BACKUP_DIR` (`~/respaldos` en el servidor), fuera de la web.
