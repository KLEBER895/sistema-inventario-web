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

if(!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])){

    header("Location: login.php");
    exit();
}

$rol_permitido = (
    $_SESSION['rol'] === 'Administrador' ||
    $_SESSION['rol'] === 'Empleado'
);

if(!$rol_permitido){

    header("Location: index.php");
    exit();
}

require_once 'dompdf/autoload.inc.php';
include("conexion.php");

use Dompdf\Dompdf;

$dompdf = new Dompdf();

/*
OBTENER LA ÚLTIMA VENTA
CON CLIENTE, PRODUCTO Y PRECIO HISTÓRICO
*/

$consulta = mysqli_query(
    $conexion,
    "SELECT
        ventas.id,
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

    ORDER BY ventas.id DESC

    LIMIT 1"
);

if (!$consulta) {

    die("Error al consultar la venta.");

}

$factura = mysqli_fetch_assoc($consulta);

if (!$factura) {

    die("No existen ventas registradas.");

}


/*
DATOS DE LA FACTURA
*/

$numero_factura = "FAC-" . str_pad(
    $factura['id'],
    4,
    "0",
    STR_PAD_LEFT
);

$cliente = htmlspecialchars($factura['cliente']);
$cedula = htmlspecialchars($factura['cedula']);
$telefono = htmlspecialchars($factura['telefono']);

$producto = htmlspecialchars($factura['producto']);

$cantidad = intval($factura['cantidad']);

$precio = number_format(
    $factura['precio_unitario'],
    2
);

$subtotal = number_format(
    $factura['subtotal'],
    2
);

$iva = number_format(
    $factura['iva'],
    2
);

$total = number_format(
    $factura['total'],
    2
);

$fecha = htmlspecialchars($factura['fecha']);


/*
HTML DE LA FACTURA
*/

$html = "

<!DOCTYPE html>

<html lang='es'>

<head>

<meta charset='UTF-8'>

<style>

body {

    font-family: Arial, sans-serif;
    color: #222;
    font-size: 14px;

}

.factura {

    width: 90%;
    margin: auto;

}

h1 {

    text-align: center;
    color: #1565c0;

}

.numero {

    text-align: center;
    margin-bottom: 25px;

}

.info {

    line-height: 1.8;

}

table {

    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;

}

th {

    background: #1565c0;
    color: white;
    padding: 10px;
    border: 1px solid #cccccc;

}

td {

    text-align: center;
    padding: 10px;
    border: 1px solid #cccccc;

}

.resumen {

    width: 300px;
    margin-left: auto;
    margin-top: 25px;

}

.resumen table {

    margin-top: 0;

}

.resumen td {

    border: none;
    padding: 6px;

}

.etiqueta {

    text-align: left;
    font-weight: bold;

}

.valor {

    text-align: right;

}

.total-final td {

    border-top: 2px solid #333333;
    font-size: 18px;
    font-weight: bold;

}

.gracias {

    text-align: center;
    margin-top: 40px;

}

</style>

</head>

<body>

<div class='factura'>

<h1>FACTURA DE VENTA</h1>

<div class='numero'>

<strong>
$numero_factura
</strong>

</div>

<div class='info'>

<strong>Cliente:</strong>
$cliente

<br>

<strong>Cédula:</strong>
$cedula

<br>

<strong>Teléfono:</strong>
$telefono

<br>

<strong>Fecha:</strong>
$fecha

</div>


<table>

<tr>

<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Subtotal</th>

</tr>

<tr>

<td>$producto</td>
<td>$cantidad</td>
<td>$$precio</td>
<td>$$subtotal</td>

</tr>

</table>


<div class='resumen'>

<table>

<tr>

<td class='etiqueta'>
Subtotal:
</td>

<td class='valor'>
$$subtotal
</td>

</tr>

<tr>

<td class='etiqueta'>
IVA (15%):
</td>

<td class='valor'>
$$iva
</td>

</tr>

<tr class='total-final'>

<td class='etiqueta'>
Total:
</td>

<td class='valor'>
$$total
</td>

</tr>

</table>

</div>


<p class='gracias'>
Gracias por su compra
</p>

</div>

</body>

</html>

";


$dompdf->loadHtml(
    $html,
    'UTF-8'
);

$dompdf->setPaper(
    'A4',
    'portrait'
);

$dompdf->render();

$dompdf->stream(
    "factura_" . $numero_factura . ".pdf",
    array(
        "Attachment" => false
    )
);

?>