<?php

session_start();

if(!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])){

    header("Location: login.php");
    exit();
}

if($_SESSION['rol'] !== 'Administrador'){

    header("Location: index.php");
    exit();
}

$base_datos = "inventario_web";

$fecha = date("Y-m-d_H-i-s");

$archivo = __DIR__ . "/respaldos/backup_" . $fecha . ".sql";

$comando = "\"C:\\xampp\\mysql\\bin\\mysqldump.exe\" -u root $base_datos > \"$archivo\"";

system($comando);

?>