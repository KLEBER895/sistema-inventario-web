
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

if(isset($_POST['vender'])){

    $cliente_id = $_POST['cliente_id'];
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];

    $buscar = mysqli_query($conexion,
    "SELECT * FROM productos WHERE id='$producto_id'");

    $producto = mysqli_fetch_assoc($buscar);

    $stock_actual = $producto['stock'];

    if($stock_actual >= $cantidad){

        $precio = $producto['precio_venta'];

        $subtotal = $precio * $cantidad;
        $iva = $subtotal * 0.15;
        $total = $subtotal + $iva;

        $nuevo_stock = $stock_actual - $cantidad;

        mysqli_query($conexion,
        "UPDATE productos
        SET stock='$nuevo_stock'
        WHERE id='$producto_id'");

        mysqli_query($conexion,
        "INSERT INTO ventas
        (cliente_id, producto_id, cantidad,
        subtotal, iva, total, fecha)

        VALUES

        ('$cliente_id','$producto_id','$cantidad',
        '$subtotal','$iva','$total',NOW())");

        echo "<div class='mensaje' style='color:green;'>
                Venta realizada correctamente
                <br><br>

                <a href='factura.php?id=".mysqli_insert_id($conexion)."'
                    target='_blank'>

        <button>
        Ver Factura
        </button>

        </a>
              </div>";

    }else{

        echo "<div class='mensaje' style='color:red;'>
                Stock insuficiente
              </div>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Módulo de Ventas</title>

<link rel="stylesheet"
href="css/estilos.css">

<style>

.contenedor{
    max-width:900px;
    margin:auto;
}

</style>

</head>

<body>

<header>
    <h1>Módulo de Ventas</h1>
</header>

<?php include("menu.php"); ?>


<div class="ventas-contenedor">

<a href="clientes.php">
    Registrar nuevo cliente
</a>

<br><br>

<form method="POST">

<label>Buscar por cédula</label>

<input type="text"
id="buscarCedula"
placeholder="Ingrese cédula">

<label>Cliente</label>

<select name="cliente_id"
id="clienteSelect"
required>

<option value="">
Seleccione cliente
</option>

<?php

$clientes = mysqli_query($conexion,
"SELECT * FROM clientes");

while($cliente = mysqli_fetch_assoc($clientes)){

echo "<option value='".$cliente['id']."'>
".$cliente['nombre']." - ".$cliente['cedula']."
</option>";

}

?>

</select>

<label>Producto</label>

<select name="producto_id" required>

<option value="">
Seleccione producto
</option>

<?php

$productos = mysqli_query($conexion,
"SELECT * FROM productos");

while($producto = mysqli_fetch_assoc($productos)){

echo "<option value='".$producto['id']."'>
".$producto['nombre']."
</option>";

}

?>

</select>

<label>Cantidad</label>

<input type="number"
name="cantidad"
min="1"
placeholder="Ingrese cantidad"
required>

<label>Unidad</label>

<select name="unidad">

<option value="Unidades">Unidades</option>

<option value="Libras">Libras</option>

<option value="Litros">Litros</option>

<option value="Kg">Kg</option>

</select>

<button type="submit" name="vender">
    Vender
</button>

<a href="generar_pdf.php" target="_blank">

<button type="button"
style="
background:#198754;
color:white;
padding:10px 20px;
border:none;
border-radius:5px;
cursor:pointer;
margin-top:10px;
">

Generar Factura PDF

</button>

</a>

</form>

<hr>

<h3>Productos Disponibles</h3>

<div style="overflow-x:auto;">

<table>

<tr>

<th>Código</th>
<th>Producto</th>
<th>Precio</th>
<th>Stock</th>

</tr>

<?php

$lista = mysqli_query($conexion,
"SELECT * FROM productos");

while($p = mysqli_fetch_assoc($lista)){

?>

<tr>

<td><?php echo $p['codigo']; ?></td>

<td><?php echo $p['nombre']; ?></td>

<td>
$<?php echo $p['precio_venta']; ?>
</td>

<td><?php echo $p['stock']; ?></td>

</tr>

<?php } ?>

</table>

</div>

</div>

<script>

const buscarCedula =
document.getElementById("buscarCedula");

const clienteSelect =
document.getElementById("clienteSelect");

buscarCedula.addEventListener("keyup", function(){

    let cedula =
    buscarCedula.value.toLowerCase();

    for(let i = 0;
        i < clienteSelect.options.length;
        i++){

        let texto =
        clienteSelect.options[i]
        .text.toLowerCase();

        if(texto.includes(cedula)){

            clienteSelect.selectedIndex = i;
            break;
        }
    }
});

</script>

</body>
</html>

