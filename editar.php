<?php

session_start();


if($_SESSION['rol'] != 'Administrador'){

    header("Location:index.php");
    exit();
}

$tiempo_inactivo = 1800;

if(isset($_SESSION['ultimo_acceso'])){

    $tiempo_transcurrido = time() - $_SESSION['ultimo_acceso'];

    if($tiempo_transcurrido > $tiempo_inactivo){

        session_destroy();

        header("Location: login.php");

        exit();
    }
}

$_SESSION['ultimo_acceso'] = time();

if(!isset($_SESSION['usuario'])){

    header("Location: login.php");

    exit();
}

include("conexion.php");



$id = $_GET['id'];

$consulta = "SELECT * FROM productos WHERE id='$id'";
$resultado = mysqli_query($conexion, $consulta);

$fila = mysqli_fetch_assoc($resultado);

if(isset($_POST['actualizar'])){

    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $unidad = $_POST['unidad'];

    $actualizar = "UPDATE productos 
    SET nombre='$nombre',
    precio='$precio',
    stock='$stock',
    unidad='$unidad'
    WHERE id='$id'";

    mysqli_query($conexion, $actualizar);

    header("Location: productos.php");

    exit();

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Editar Producto</title>

<link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header>
    <h1>Módulo de Productos</h1>
</header>

<nav class="menu">

    <a href="index.php">Inicio</a>
    <a href="productos.php">Productos</a>

</nav>

<div class="contenedor">

    <div class="card">

        <h2>Editar Producto</h2>

        <form method="POST">

            <input type="text" name="nombre"
            value="<?php echo $fila['nombre']; ?>">

            <input type="number" step="0.01" name="precio"
            value="<?php echo $fila['precio']; ?>">

            <input type="number" name="stock"
            value="<?php echo $fila['stock']; ?>">

            <input type="text" name="unidad"
            value="<?php echo $fila['unidad']; ?>">

            <button type="submit" name="actualizar">
                Actualizar Producto
            </button>

        </form>

    </div>

</div>

</body>

</html>