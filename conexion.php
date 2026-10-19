
<?php

/*
CAMBIO: 19/10/2026
MOTIVO: Separar las credenciales de MySQL del código
versionado en GitHub y preparar el despliegue.
Se conserva la variable $conexion para compatibilidad
con los módulos existentes.
*/

$config = require __DIR__ . '/config_db.php';

$servidor   = $config['servidor'];
$usuario    = $config['usuario'];
$contrasena = $config['contrasena'];
$base_datos = $config['base_datos'];
$puerto     = (int) $config['puerto'];

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

} catch (Throwable $error) {

    error_log(
        'Error de conexión MySQL: ' . $error->getMessage()
    );

    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}
