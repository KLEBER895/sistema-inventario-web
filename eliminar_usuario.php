<?php

session_start();

if($_SESSION['rol'] != 'Administrador'){

    header("Location:index.php");
    exit();
}

include("conexion.php");

$id = $_GET['id'];

mysqli_query($conexion,
"DELETE FROM usuarios
WHERE id='$id'");

header("Location: usuarios.php");
exit();

?>