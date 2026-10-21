<?php

/*
CAMBIO: 19/10/2026
MOTIVO: Separar las credenciales de MySQL del código
versionado en GitHub y preparar el despliegue.
Se conserva la variable $conexion para compatibilidad
con los módulos existentes.

CAMBIO: 21/10/2026
MOTIVO: Las credenciales ahora se leen de variables de
entorno (.env) a través de configuracion.php, en lugar
del archivo config_db.php.
*/

$config = require __DIR__ . '/configuracion.php';

$servidor   = $config['db']['servidor'];
$usuario    = $config['db']['usuario'];
$contrasena = $config['db']['contrasena'];
$base_datos = $config['db']['base_datos'];
$puerto     = $config['db']['puerto'];

try {
    $conexion = mysqli_connect(
        $servidor,
        $usuario,
        $contrasena,
        $base_datos,
        $puerto
    );

    if (!$conexion) {
        throw new RuntimeException(
            'No se pudo establecer la conexión MySQL.'
        );
    }

    if (!mysqli_set_charset($conexion, 'utf8mb4')) {
        throw new RuntimeException(
            'No se pudo configurar utf8mb4.'
        );
    }

    /*
    CAMBIO: 19/10/2026
    MOTIVO: PHP y MySQL deben usar la misma zona horaria.
    El token de recuperación se calcula con date() en PHP
    y se compara con NOW() en MySQL.

    CAMBIO: 21/10/2026
    MOTIVO: La zona horaria se toma de APP_TIMEZONE y el
    desfase de MySQL se calcula desde PHP (ej: -05:00).
    */
    $desfase = (new DateTime())->format('P');
    mysqli_query($conexion, "SET time_zone = '$desfase'");

} catch (Throwable $error) {

    error_log(
        'Error de conexión MySQL: ' . $error->getMessage()
    );

    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}
