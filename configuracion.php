<?php

/*
CAMBIO: 21/10/2026
MOTIVO: Centralizar toda la configuración del sistema en
variables de entorno para poder desplegar el mismo código
en local (XAMPP) y en el servidor sin editar archivos PHP.

ORDEN DE PRIORIDAD:
1. Variables de entorno reales del servidor (getenv).
2. Archivo .env en la raíz del proyecto (protegido por .gitignore
   y bloqueado para el navegador en .htaccess).
3. Valores por defecto de este archivo (solo los no sensibles).

La plantilla con todas las variables está en .env.example.
*/

if (!function_exists('entorno')) {

    function cargar_archivo_env($ruta)
    {
        if (!is_readable($ruta)) {
            return;
        }

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {

            $linea = trim($linea);

            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }

            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));

            // Quitar comillas envolventes: CLAVE="valor con espacios"
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'")
                && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }

            // Una variable real del servidor nunca se sobrescribe.
            if (getenv($clave) === false) {
                putenv("$clave=$valor");
                $_ENV[$clave] = $valor;
            }
        }
    }

    function entorno($clave, $por_defecto = null)
    {
        $valor = getenv($clave);

        return ($valor === false || $valor === '') ? $por_defecto : $valor;
    }

    function entorno_bool($clave, $por_defecto = false)
    {
        $valor = entorno($clave);

        if ($valor === null) {
            return $por_defecto;
        }

        return in_array(strtolower($valor), ['1', 'true', 'si', 'yes', 'on'], true);
    }

    function entorno_obligatorio($clave)
    {
        $valor = entorno($clave);

        if ($valor === null) {
            throw new RuntimeException("Falta la variable de entorno $clave.");
        }

        return $valor;
    }
}


cargar_archivo_env(__DIR__ . '/.env');


$app_env = entorno('APP_ENV', 'local');

// En producción nunca se muestran errores en pantalla.
ini_set('display_errors', entorno_bool('APP_DEBUG', $app_env !== 'produccion') ? '1' : '0');
error_reporting(E_ALL);

date_default_timezone_set(entorno('APP_TIMEZONE', 'America/Guayaquil'));


return [
    'app' => [
        'entorno' => $app_env,
        // URL pública sin "/" final. Se usa para los enlaces de los correos.
        'url'     => rtrim((string) entorno('APP_URL', ''), '/'),
    ],

    'db' => [
        'servidor'   => entorno('DB_HOST', 'localhost'),
        'puerto'     => (int) entorno('DB_PORT', '3306'),
        'base_datos' => entorno('DB_DATABASE', 'inventario_web'),
        'usuario'    => entorno('DB_USERNAME', 'root'),
        'contrasena' => entorno('DB_PASSWORD', ''),
    ],

    'mail' => [
        'host'       => entorno('MAIL_HOST'),
        'port'       => (int) entorno('MAIL_PORT', '587'),
        'username'   => entorno('MAIL_USERNAME'),
        'password'   => entorno('MAIL_PASSWORD'),
        // tls (STARTTLS, puerto 587), ssl (puerto 465) o ninguno
        'encryption' => strtolower(entorno('MAIL_ENCRYPTION', 'tls')),
        'from_email' => entorno('MAIL_FROM_ADDRESS'),
        'from_name'  => entorno('MAIL_FROM_NAME', 'Sistema Inventario Web'),
    ],

    'respaldos' => [
        'carpeta'   => rtrim(entorno('BACKUP_DIR', __DIR__ . '/respaldos'), '/\\'),
        'mysqldump' => entorno('MYSQLDUMP_PATH', 'mysqldump'),
    ],
];
