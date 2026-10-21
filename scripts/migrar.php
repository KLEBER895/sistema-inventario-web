<?php

/*
CAMBIO: 19/10/2026
MOTIVO: Aplicar automáticamente los cambios de base de datos
durante el despliegue, en orden y una sola vez cada uno.

USO (solo desde la terminal):
  php scripts/migrar.php
      Aplica las migraciones pendientes de database/migraciones/.
      Antes de aplicarlas genera un respaldo en BACKUP_DIR.

  php scripts/migrar.php --marcar-aplicadas
      Registra todas las migraciones como aplicadas sin ejecutarlas.
      Se usa una sola vez, cuando la base ya fue importada completa.

SEGURIDAD:
- No se puede ejecutar desde el navegador.
- Los respaldos se guardan en BACKUP_DIR (fuera de la carpeta pública
  en producción).
- La contraseña de MySQL no aparece en la línea de comandos.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require __DIR__ . '/../conexion.php';


$marcar_aplicadas = in_array('--marcar-aplicadas', $argv, true);


mysqli_query(
    $conexion,
    "CREATE TABLE IF NOT EXISTS migraciones (
        archivo VARCHAR(255) PRIMARY KEY,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$resultado = mysqli_query($conexion, "SELECT archivo FROM migraciones");

$aplicadas = array_column(
    mysqli_fetch_all($resultado, MYSQLI_ASSOC),
    'archivo'
);

$archivos = glob(__DIR__ . '/../database/migraciones/*.sql');
sort($archivos);

$pendientes = array_values(array_filter(
    $archivos,
    fn($archivo) => !in_array(basename($archivo), $aplicadas, true)
));


if (!$pendientes) {
    echo "Base de datos al día.\n";
    exit(0);
}


$stmt_registro = mysqli_prepare(
    $conexion,
    "INSERT INTO migraciones (archivo) VALUES (?)"
);


if ($marcar_aplicadas) {

    foreach ($pendientes as $archivo) {
        $nombre = basename($archivo);
        mysqli_stmt_bind_param($stmt_registro, "s", $nombre);
        mysqli_stmt_execute($stmt_registro);
        echo "Marcada como aplicada: $nombre\n";
    }

    exit(0);
}


/*
Respaldo previo. Los cambios de estructura (ALTER, CREATE)
no se pueden deshacer con una transacción, así que el
respaldo es la forma de volver atrás si algo falla.
*/
require __DIR__ . '/../respaldo.php';

try {
    $archivo_respaldo = generar_respaldo($config, 'antes_migrar');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    fwrite(STDERR, "No se pudo generar el respaldo. No se aplicó ninguna migración.\n");
    exit(1);
}

echo "Respaldo generado: $archivo_respaldo\n";


foreach ($pendientes as $archivo) {

    $nombre = basename($archivo);

    try {

        mysqli_multi_query($conexion, file_get_contents($archivo));

        // Recorrer todos los resultados para detectar errores
        // en cualquier sentencia del archivo.
        do {
            if ($resultado = mysqli_store_result($conexion)) {
                mysqli_free_result($resultado);
            }
        } while (
            mysqli_more_results($conexion) &&
            mysqli_next_result($conexion)
        );

    } catch (mysqli_sql_exception $error) {

        fwrite(STDERR, "Error en $nombre: " . $error->getMessage() . "\n");
        fwrite(STDERR, "Respaldo disponible en: $archivo_respaldo\n");
        exit(1);
    }

    mysqli_stmt_bind_param($stmt_registro, "s", $nombre);
    mysqli_stmt_execute($stmt_registro);

    echo "Aplicada: $nombre\n";
}
