<?php

session_start();

if(!isset($_SESSION['usuario'])){

    header("Location: login.php");
    exit();
}

$base_datos = "inventario_web";

$fecha = date("Y-m-d_H-i-s");

$archivo = "respaldos/backup_" . $fecha . ".sql";

$comando = "C:\\xampp\\mysql\\bin\\mysqldump -u root $base_datos > $archivo";

system($comando);

echo "<script>

alert('Respaldo generado correctamente');

window.location='lista_respaldos.php';

</script>";

?>