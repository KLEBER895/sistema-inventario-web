<?php

/*
CAMBIO: 27/10/2026
MOTIVO: Centralizar el inicio de sesion mediante sesion.php.
Se conservan las validaciones y funciones del modulo.
*/
require __DIR__ . '/sesion.php';

if(!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])){

    header("Location: login.php");
    exit();
}

if($_SESSION['rol'] !== 'Administrador'){

    header("Location: index.php");
    exit();
}

/*
CAMBIO: 21/10/2026
MOTIVO: El respaldo usaba C:\xampp\mysql\bin\mysqldump, el
usuario root y la base inventario_web fijos en el código.
Ahora usa las mismas credenciales de la conexión (.env) y
la carpeta definida en BACKUP_DIR.
*/
$config = require __DIR__ . '/configuracion.php';
require __DIR__ . '/respaldo.php';

try {

    generar_respaldo($config);
    $mensaje = 'Respaldo generado correctamente';

} catch (Throwable $error) {

    error_log('Error al generar respaldo: ' . $error->getMessage());
    $mensaje = 'No se pudo generar el respaldo. Revise el log del servidor.';
}

echo "<script>

alert(" . json_encode($mensaje) . ");

window.location='lista_respaldos.php';

</script>";

?>
