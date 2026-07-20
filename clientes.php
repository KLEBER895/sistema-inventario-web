<?php

session_start();

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
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Clientes</title>

<link rel="stylesheet"
href="css/estilos.css">

<style>

.contenedor{
    max-width:800px;
    margin:auto;
}

</style>

</head>

<body>

<header>
    <h1>Módulo de Clientes</h1>
</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<?php if(
    $_SESSION['rol'] == 'Administrador' ||
    $_SESSION['rol'] == 'Empleado'
){ ?>

<form method="POST">

    <input type="text"
    name="nombre"
    placeholder="Nombre del cliente"
    required>

    <input type="text"
    name="cedula"
    placeholder="Cédula"
    required>

    <input type="text"
    name="telefono"
    placeholder="Teléfono"
    required>

    <input type="text"
    name="dirección"
    placeholder="Dirección"
    required>

    <button type="submit" name="guardar">
        Guardar Cliente
    </button>

</form>

<?php } ?>

<?php

if(isset($_POST['guardar'])){


    $nombre = $_POST['nombre'];
    $cedula = $_POST['cedula'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['dirección'];

    mysqli_query($conexion,
    "INSERT INTO clientes
    (nombre, cedula, telefono, dirección)
    VALUES
    ('$nombre','$cedula','$telefono','$direccion')");

    echo "<p style='color:green;
    text-align:center;
    font-weight:bold;'>
    Cliente registrado correctamente
    </p>";
}

?>

<h2>Lista de Clientes</h2>

<table>

<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Cédula</th>
    <th>Teléfono</th>
</tr>

<?php

$clientes = mysqli_query($conexion,
"SELECT * FROM clientes");

while($cliente = mysqli_fetch_assoc($clientes)){

?>

<tr>

    <td><?php echo $cliente['id']; ?></td>

    <td><?php echo $cliente['nombre']; ?></td>

    <td><?php echo $cliente['cedula']; ?></td>

    <td><?php echo $cliente['telefono']; ?></td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>