
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

if(isset($_POST['vender']) && !$rol_permitido){

    header("Location: index.php");
    exit();
}

if(isset($_POST['vender'])){

    $cliente_id = $_POST['cliente_id'];
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];

    $stmt_producto = mysqli_prepare(
    $conexion,
    "SELECT id, stock, precio_venta
     FROM productos
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt_producto,
    "i",
    $producto_id
);

mysqli_stmt_execute($stmt_producto);

$resultado_producto = mysqli_stmt_get_result($stmt_producto);

$producto = mysqli_fetch_assoc($resultado_producto);

mysqli_stmt_close($stmt_producto);

    $stock_actual = $producto['stock'];

    if($stock_actual >= $cantidad){

        $precio = $producto['precio_venta'];

        $subtotal = $precio * $cantidad;
        $iva = $subtotal * 0.15;
        $total = $subtotal + $iva;

       $nuevo_stock = $stock_actual - $cantidad;

mysqli_begin_transaction($conexion);

try {

    // 1. Descontar stock
    $stmt_stock = mysqli_prepare(
    $conexion,
    "UPDATE productos
     SET stock = ?
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt_stock,
    "ii",
    $nuevo_stock,
    $producto_id
);

if(!mysqli_stmt_execute($stmt_stock)){
    mysqli_stmt_close($stmt_stock);
    throw new Exception("No se pudo actualizar el stock.");
}

mysqli_stmt_close($stmt_stock);


    // 2. Registrar la venta
    $stmt_venta = mysqli_prepare(
    $conexion,
    "INSERT INTO ventas
    (cliente_id, producto_id, cantidad,
    subtotal, iva, total, fecha)
    VALUES (?, ?, ?, ?, ?, ?, NOW())"
);

mysqli_stmt_bind_param(
    $stmt_venta,
    "iiiddd",
    $cliente_id,
    $producto_id,
    $cantidad,
    $subtotal,
    $iva,
    $total
);

if(!mysqli_stmt_execute($stmt_venta)){
    mysqli_stmt_close($stmt_venta);
    throw new Exception("No se pudo registrar la venta.");
}

mysqli_stmt_close($stmt_venta);

$venta_id = mysqli_insert_id($conexion);

    // 3. Registrar el detalle de la venta
    $stmt_detalle = mysqli_prepare(
    $conexion,
    "INSERT INTO detalle_ventas
    (venta_id, producto_id, cantidad,
    precio_unitario, subtotal)
    VALUES (?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt_detalle,
    "iiidd",
    $venta_id,
    $producto_id,
    $cantidad,
    $precio,
    $subtotal
);

if(!mysqli_stmt_execute($stmt_detalle)){
    mysqli_stmt_close($stmt_detalle);
    throw new Exception("No se pudo registrar el detalle de la venta.");
}

mysqli_stmt_close($stmt_detalle);


    // 4. Confirmar toda la operación
    mysqli_commit($conexion);


    echo "<div class='mensaje' style='color:green;'>
            Venta realizada correctamente
            <br><br>

            <a href='factura.php?id=".$venta_id."'
               target='_blank'>

                <button>
                    Ver Factura
                </button>

            </a>
          </div>";

} catch (Exception $e) {

    mysqli_rollback($conexion);

    echo "<div class='mensaje' style='color:red;'>
            Error: ".htmlspecialchars($e->getMessage())."
          </div>";
}

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

