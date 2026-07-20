<?php

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
<th>Total</th>
<th>Fecha</th>
<th>Factura</th>

</tr>

<?php

$consulta = mysqli_query($conexion,

"SELECT ventas.id,
clientes.nombre AS cliente,
productos.nombre AS producto,
ventas.cantidad,
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
FAC-00<?php echo $fila['id']; ?>
</td>

<td>
<?php echo $fila['cliente']; ?>
</td>

<td>
<?php echo $fila['producto']; ?>
</td>

<td>
<?php echo $fila['cantidad']; ?>
</td>

<td>
$<?php echo $fila['total']; ?>
</td>

<td>
<?php echo $fila['fecha']; ?>
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
