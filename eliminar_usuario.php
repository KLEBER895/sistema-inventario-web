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

include("conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){

    header("Location: usuarios.php");
    exit();
}

$stmt = mysqli_prepare(
    $conexion,
    "DELETE FROM usuarios
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: usuarios.php");
exit();

?>