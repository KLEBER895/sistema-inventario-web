<?php

include("conexion.php");

$id = $_GET['id'];

$consulta = mysqli_query($conexion,

"SELECT ventas.*,
clientes.nombre AS cliente,
clientes.cedula,
clientes.telefono,
productos.nombre AS producto,
productos.precio_venta

FROM ventas

INNER JOIN clientes
ON ventas.cliente_id = clientes.id

INNER JOIN productos
ON ventas.producto_id = productos.id

WHERE ventas.id = '$id'"

);

$factura = mysqli_fetch_assoc($consulta);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Factura</title>

<style>

body{
    font-family: Arial;
    background: #f4f4f4;
    padding: 30px;
}

.factura{
    width: 700px;
    background: white;
    margin: auto;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px #ccc;
}

h1{
    color: #1565c0;
    text-align: center;
}

table{
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td{
    border: 1px solid #ccc;
}

th{
    background: #1565c0;
    color: white;
    padding: 10px;
}

td{
    padding: 10px;
    text-align: center;
}

.info{
    margin-top: 20px;
    line-height: 30px;
}

.total{
    text-align: right;
    margin-top: 20px;
    font-size: 20px;
    font-weight: bold;
}

button{
    padding: 10px 20px;
    background: #1565c0;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover{
    background: #0d47a1;
}

</style>

</head>

<body>

<div class="factura">

<h1>FACTURA</h1>

<div class="info">

<strong>Cliente:</strong>
<?php echo $factura['cliente']; ?>

<br>

<strong>Cédula:</strong>
<?php echo $factura['cedula']; ?>

<br>

<strong>Teléfono:</strong>
<?php echo $factura['telefono']; ?>

<br>

<strong>Fecha:</strong>
<?php echo $factura['fecha']; ?>

</div>

<table>

<tr>

<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Subtotal</th>

</tr>

<tr>

<td><?php echo $factura['producto']; ?></td>

<td><?php echo $factura['cantidad']; ?></td>

<td>$<?php echo $factura['precio_venta']; ?></td>

<td>$<?php echo $factura['subtotal']; ?></td>

</tr>

</table>

<div class="total">

IVA:
$<?php echo $factura['iva']; ?>

<br><br>

TOTAL:
$<?php echo $factura['total']; ?>

</div>

<br>

<center>

<button onclick="window.print()">
Imprimir Factura
</button>

</center>

</div>

</body>
</html>
```
