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

    error_log(
        "Error de conexión MySQL: " . mysqli_connect_error()
    );

    die("No se pudo conectar con la base de datos.");
}

mysqli_set_charset($conexion, "utf8mb4");

?>