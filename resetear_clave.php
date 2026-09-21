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

$clave_temporal = '123456';

$clave_hash = password_hash(
    $clave_temporal,
    PASSWORD_DEFAULT
);

$stmt = mysqli_prepare(
    $conexion,
    "UPDATE usuarios
     SET clave = ?
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $clave_hash,
    $id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

echo "<script>

alert('Contraseña restablecida correctamente. La contraseña temporal es 123456');

window.location='usuarios.php';

</script>";

?>