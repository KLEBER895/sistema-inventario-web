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
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reportes de Ventas</title>

<link rel="stylesheet" href="css/estilos.css">

<style>

.reportes-contenedor{
    max-width:1200px;
    margin:40px auto;
}

</style>

</head>

<body>

<header>
    <h1>Reporte de Ventas</h1>
</header>

<?php include("menu.php"); ?>

<div class="reportes-contenedor">

<h2>Reporte de Ventas</h2>

<div style="overflow-x:auto;">

<table>

<tr>

<th>ID</th>
<th>Cliente</th>
<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Total</th>
<th>Fecha</th>

</tr>

<?php

$ventas = mysqli_query($conexion,

"SELECT ventas.id,
clientes.nombre AS cliente,
productos.nombre AS producto,
ventas.cantidad,
productos.precio_venta,
ventas.total,
ventas.fecha

FROM ventas

INNER JOIN clientes
ON ventas.cliente_id = clientes.id

INNER JOIN productos
ON ventas.producto_id = productos.id"

);

while($fila = mysqli_fetch_assoc($ventas)){

?>

<tr>

<td><?php echo $fila['id']; ?></td>

<td><?php echo $fila['cliente']; ?></td>

<td><?php echo $fila['producto']; ?></td>

<td><?php echo $fila['cantidad']; ?></td>

<td>$<?php echo $fila['precio_venta']; ?></td>

<td>$<?php echo $fila['total']; ?></td>

<td><?php echo $fila['fecha']; ?></td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>