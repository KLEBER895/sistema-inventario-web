<?php

session_start();

if($_SESSION['rol'] != 'Administrador'){

    header("Location:index.php");
    exit();
}

include("conexion.php");

$id = $_GET['id'];

mysqli_query($conexion,

"UPDATE usuarios
SET clave='123456'
WHERE id='$id'");

echo "<script>

alert('Contraseña restablecida a 123456');

window.location='usuarios.php';

</script>";

?>
