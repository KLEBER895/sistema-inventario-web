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