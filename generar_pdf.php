```php
<?php

require_once 'dompdf/autoload.inc.php';
include("conexion.php");

use Dompdf\Dompdf;

$dompdf = new Dompdf();

/*
ULTIMA VENTA
*/
$venta = mysqli_query($conexion,
"SELECT * FROM ventas ORDER BY id DESC LIMIT 1");

$venta_data = mysqli_fetch_assoc($venta);

$cliente_id = $venta_data['cliente_id'];
$producto_id = $venta_data['producto_id'];

$cantidad = $venta_data['cantidad'];
$subtotal = $venta_data['subtotal'];
$iva = $venta_data['iva'];
$total = $venta_data['total'];
$fecha = $venta_data['fecha'];

/*
CLIENTE
*/
$cliente = mysqli_query($conexion,
"SELECT * FROM clientes WHERE id='$cliente_id'");

$cliente_data = mysqli_fetch_assoc($cliente);

$nombre_cliente = $cliente_data['nombre'];
$cedula = $cliente_data['cedula'];
$telefono = $cliente_data['telefono'];

/*
PRODUCTO
*/
$producto = mysqli_query($conexion,
"SELECT * FROM productos WHERE id='$producto_id'");

$producto_data = mysqli_fetch_assoc($producto);

$nombre_producto = $producto_data['nombre'];
$precio = $producto_data['precio_venta'];

/*
HTML FACTURA
*/
$html = "

<h1 style='text-align:center;color:#0d6efd;'>
FACTURA DE VENTA
</h1>

<hr>

<h3>Fecha:</h3>
<p>$fecha</p>

<h3>Cliente:</h3>
<p>$nombre_cliente</p>

<h3>Cédula:</h3>
<p>$cedula</p>

<h3>Teléfono:</h3>
<p>$telefono</p>

<hr>

<table width='100%' border='1' cellspacing='0' cellpadding='8'>

<tr style='background:#0d6efd;color:white;'>

<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Subtotal</th>

</tr>

<tr>

<td>$nombre_producto</td>
<td>$cantidad</td>
<td>$$precio</td>
<td>$$subtotal</td>

</tr>

</table>

<br>

<h3>IVA:</h3>
<p>$$iva</p>

<h2>Total:</h2>
<p>$$total</p>

<hr>

<p style='text-align:center;'>
Gracias por su compra
</p>

";

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$dompdf->stream("factura.pdf", array("Attachment" => false));

?>
```
