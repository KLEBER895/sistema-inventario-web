<?php

/*
CAMBIO: 21/10/2026
MOTIVO: Una sola función para generar respaldos con mysqldump,
usada por el módulo de Respaldos (backup.php) y por las
migraciones del despliegue (scripts/migrar.php).

Antes cada archivo tenía su propio comando con la ruta
C:\xampp\mysql\bin\mysqldump y el usuario root fijos,
lo que solo funcionaba en la PC de desarrollo.

SEGURIDAD:
- La contraseña de MySQL se pasa en un archivo temporal
  con permisos 0600, nunca en la línea de comandos.
- Todos los argumentos se escapan con escapeshellarg().
*/

function generar_respaldo(array $config, $prefijo = 'backup')
{
    $db = $config['db'];
    $carpeta = $config['respaldos']['carpeta'];

    if (!is_dir($carpeta) && !mkdir($carpeta, 0700, true)) {
        throw new RuntimeException("No se pudo crear la carpeta de respaldos: $carpeta");
    }

    $archivo = $carpeta . DIRECTORY_SEPARATOR
        . $prefijo . '_' . date('Y-m-d_H-i-s') . '.sql';

    $credenciales = tempnam(sys_get_temp_dir(), 'mysql');
    chmod($credenciales, 0600);

    file_put_contents(
        $credenciales,
        "[client]\n"
        . "host=\"" . addcslashes($db['servidor'], "\\\"") . "\"\n"
        . "port=" . $db['puerto'] . "\n"
        . "user=\"" . addcslashes($db['usuario'], "\\\"") . "\"\n"
        . "password=\"" . addcslashes($db['contrasena'], "\\\"") . "\"\n"
    );

    // --defaults-extra-file debe ser la primera opción de mysqldump.
    exec(
        escapeshellarg($config['respaldos']['mysqldump'])
        . ' --defaults-extra-file=' . escapeshellarg($credenciales)
        . ' --single-transaction --no-tablespaces'
        . ' --result-file=' . escapeshellarg($archivo)
        . ' ' . escapeshellarg($db['base_datos'])
        . ' 2>&1',
        $salida,
        $codigo
    );

    unlink($credenciales);

    if ($codigo !== 0) {
        @unlink($archivo);
        throw new RuntimeException('mysqldump falló: ' . implode(' ', $salida));
    }

    return $archivo;
}
