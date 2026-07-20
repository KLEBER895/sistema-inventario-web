<?php

$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "";
$base_datos = "inventario_web";
$puerto = 3307;

$conexion = mysqli_connect(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos,
    $puerto
);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

?>