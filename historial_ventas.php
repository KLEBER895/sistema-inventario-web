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

include("conexion.php");

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Historial de Ventas</title>

<link rel="stylesheet" href="css/estilos.css">

<style>

.historial-contenedor{
    max-width:1200px;
    margin:40px auto;
}

.boton{
    background:#198754;
    color:white;
    padding:8px 15px;
    border-radius:5px;
    display:inline-block;
    text-decoration:none;
}

.boton:hover{
    background:#146c43;
}

</style>

</head>

<body>

<header>
    <h1>Historial de Ventas</h1>
</header>

<?php include("menu.php"); ?>

<div class="historial-contenedor">

<h2>Historial de Ventas</h2>

<input type="text"
id="buscador"
placeholder="Buscar factura, cliente o producto"
style="
width:100%;
padding:12px;
margin-bottom:20px;
border:1px solid #ccc;
border-radius:5px;
font-size:16px;
">

<div class="tabla-responsive">

<table>

<tr>

<th># Factura</th>
<th>Cliente</th>
<th>Producto</th>
<th>Cantidad</th>
<th>Subtotal</th>
<th>IVA</th>
<th>Total</th>
<th>Fecha</th>
<th>Factura</th>

</tr>

<?php

$consulta = mysqli_query($conexion,

"SELECT
ventas.id,
clientes.nombre AS cliente,
productos.nombre AS producto,
ventas.cantidad,
ventas.subtotal,
ventas.iva,
ventas.total,
ventas.fecha

FROM ventas

INNER JOIN clientes
ON ventas.cliente_id = clientes.id

INNER JOIN productos
ON ventas.producto_id = productos.id

ORDER BY ventas.id DESC");

while($fila = mysqli_fetch_assoc($consulta)){

?>

<tr>

<td>
<?php
echo "FAC-" . str_pad(
    $fila['id'],
    4,
    "0",
    STR_PAD_LEFT
);
?>
</td>

<td>
<?php echo htmlspecialchars($fila['cliente']); ?>
</td>

<td>
<?php echo htmlspecialchars($fila['producto']); ?>
</td>

<td>
<?php echo intval($fila['cantidad']); ?>
</td>

<td>
$<?php echo number_format($fila['subtotal'], 2); ?>
</td>

<td>
$<?php echo number_format($fila['iva'], 2); ?>
</td>

<td>
$<?php echo number_format($fila['total'], 2); ?>
</td>

<td>
<?php echo htmlspecialchars($fila['fecha']); ?>
</td>

<td>

<a class="boton"
href="factura.php?id=<?php echo $fila['id']; ?>"
target="_blank">

Ver PDF

</a>

</td>

</tr>
<?php } ?>

</table>

</div>

</div>
<script>

const buscador =
document.getElementById("buscador");

buscador.addEventListener("keyup", function(){

    let texto =
    buscador.value.toLowerCase();

    let filas =
    document.querySelectorAll("table tr");

    filas.forEach((fila, index)=>{

        if(index === 0) return;

        let contenido =
        fila.textContent.toLowerCase();

        if(contenido.includes(texto)){

            fila.style.display = "";

        }else{

            fila.style.display = "none";
        }
    });

});

</script>

</div>

</div>

</body>
</html>
```
