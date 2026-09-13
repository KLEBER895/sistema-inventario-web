<?php

include("conexion.php");

// Obtener el ID de la venta de forma segura
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Factura no válida.");
}

$consulta = mysqli_query(
    $conexion,
    "SELECT
        ventas.id,
        ventas.cliente_id,
        ventas.producto_id,
        ventas.cantidad,
        ventas.subtotal,
        ventas.iva,
        ventas.total,
        ventas.fecha,

        clientes.nombre AS cliente,
        clientes.cedula,
        clientes.telefono,

        productos.nombre AS producto,

        COALESCE(
            detalle_ventas.precio_unitario,
            ventas.subtotal / NULLIF(ventas.cantidad, 0)
        ) AS precio_unitario

    FROM ventas

    INNER JOIN clientes
        ON ventas.cliente_id = clientes.id

    INNER JOIN productos
        ON ventas.producto_id = productos.id

    LEFT JOIN detalle_ventas
        ON detalle_ventas.venta_id = ventas.id
        AND detalle_ventas.producto_id = ventas.producto_id

    WHERE ventas.id = $id"
);

if (!$consulta) {
    die("Error al consultar la factura.");
}

$factura = mysqli_fetch_assoc($consulta);

if (!$factura) {
    die("Factura no encontrada.");
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

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

.resumen{

    width: 300px;
    margin-left: auto;
    margin-top: 25px;
    font-size: 18px;

}

.resumen p{

    display: flex;
    justify-content: space-between;
    margin: 10px 0;

}

.resumen .total-final{

    font-size: 21px;
    font-weight: bold;
    border-top: 2px solid #333;
    padding-top: 10px;

}

.boton-imprimir{

    text-align: center;
    margin-top: 30px;

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

@media print{

    body{

        background: white;
        padding: 0;

    }

    .factura{

        box-shadow: none;

    }

    .boton-imprimir{

        display: none;

    }

}

</style>

</head>

<body>

<div class="factura">

<h1>FACTURA</h1>

<div class="info">

<strong>Cliente:</strong>
<?php echo htmlspecialchars($factura['cliente']); ?>

<br>

<strong>Cédula:</strong>
<?php echo htmlspecialchars($factura['cedula']); ?>

<br>

<strong>Teléfono:</strong>
<?php echo htmlspecialchars($factura['telefono']); ?>

<br>

<strong>Fecha:</strong>
<?php echo htmlspecialchars($factura['fecha']); ?>

</div>

<table>

<tr>

<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Subtotal</th>

</tr>

<tr>

<td>
<?php echo htmlspecialchars($factura['producto']); ?>
</td>

<td>
<?php echo intval($factura['cantidad']); ?>
</td>

<td>
$<?php echo number_format($factura['precio_unitario'], 2); ?>
</td>

<td>
$<?php echo number_format($factura['subtotal'], 2); ?>
</td>

</tr>

</table>


<div class="resumen">

<p>
    <strong>Subtotal:</strong>

    <span>
        $<?php echo number_format($factura['subtotal'], 2); ?>
    </span>
</p>

<p>
    <strong>IVA (15%):</strong>

    <span>
        $<?php echo number_format($factura['iva'], 2); ?>
    </span>
</p>

<p class="total-final">
    <strong>Total:</strong>

    <span>
        $<?php echo number_format($factura['total'], 2); ?>
    </span>
</p>

</div>


<div class="boton-imprimir">

<button onclick="window.print()">

Imprimir Factura

</button>

</div>

</div>

</body>

</html>