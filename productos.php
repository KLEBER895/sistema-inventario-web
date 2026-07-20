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

if(isset($_POST['guardar'])){

    $nombre = $_POST['nombre'];
    $precio_compra = $_POST['precio_compra'];
    $precio_venta = $_POST['precio_venta'];

    $ganancia = $precio_venta - $precio_compra;
    $stock = $_POST['stock'];
    $unidad = $_POST['unidad'];

    // Buscar si el producto ya existe
    $buscar = mysqli_query($conexion,
    "SELECT * FROM productos WHERE nombre='$nombre'");

    if(mysqli_num_rows($buscar) > 0){

        // Si existe, actualizar stock
        $producto = mysqli_fetch_assoc($buscar);

        $nuevo_stock = $producto['stock'] + $stock;

        mysqli_query($conexion,
        "UPDATE productos 
        SET stock='$nuevo_stock',
            precio_compra='$precio_compra',
            precio_venta='$precio_venta',
            ganancia='$ganancia',
            unidad='$unidad',
            fecha_actualizacion=NOW()
        WHERE nombre='$nombre'");

    }else{

        // Generar código automático
        $codigo = "PROD-" . rand(1000,9999);


        $insertar = "INSERT INTO productos
        (codigo,nombre,precio_compra,precio_venta,
        ganancia,stock,unidad,fecha_actualizacion)

        VALUES

        ('$codigo','$nombre','$precio_compra',
        '$precio_venta','$ganancia',
        '$stock','$unidad',NOW())";

        mysqli_query($conexion, $insertar);
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos</title>

    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>

    <header>
    <h1>Módulo de Productos</h1>
</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<?php if($_SESSION['rol'] == 'Administrador'){ ?>

        <div class="card">

            <h2>Registrar Producto</h2>

            <form method="POST">

                <input type="text" name="nombre"
                    placeholder="Nombre del producto" required>

                <input type="number" name="precio_compra"
                    placeholder="Precio proveedor"
                    step="0.01" required>

                <input type="number" name="precio_venta"
                    placeholder="Precio venta al público"
                    step="0.01" required>

                <input type="number" name="stock"
                    placeholder="Stock" required>

                <input type="text" name="unidad"
                    placeholder="Unidad" required>

                <button type="submit" name="guardar">
                    Guardar Producto
                </button>

            </form>

        </div>

    <?php } ?>

            <form method="GET">

    <input type="text" name="buscar"
    placeholder="Buscar producto">

    <button type="submit">
        Buscar
    </button>

</form>


<h2>Lista de Productos</h2>

<div style="overflow-x:auto;">

<table border="1" width="100%">

   <tr>
    <th>ID</th>
    <th>Código</th>
    <th>Nombre</th>
    <th>Compra</th>
    <th>Venta</th>
    <th>Ganancia</th>
    <th>Stock</th>
    <th>Unidad</th>
    <th>Fecha</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr>

<?php

if(isset($_GET['buscar'])){

    $buscar = $_GET['buscar'];

    $consulta = "SELECT * FROM productos
    WHERE nombre LIKE '%$buscar%'
    OR codigo LIKE '%$buscar%'";

}else{

    $consulta = "SELECT * FROM productos";

}

$resultado = mysqli_query($conexion, $consulta);

while($fila = mysqli_fetch_assoc($resultado)){

?>

<tr>

<td><?php echo $fila['id']; ?></td>
<td><?php echo $fila['codigo']; ?></td>
<td><?php echo $fila['nombre']; ?></td>
<td>$<?php echo number_format($fila['precio_compra'],2); ?></td>

<td>$<?php echo number_format($fila['precio_venta'],2); ?></td>

<td>$<?php echo number_format($fila['ganancia'],2); ?></td>
<td><?php echo $fila['stock']; ?></td>
<td><?php echo $fila['unidad']; ?></td>
<td><?php echo $fila['fecha_actualizacion']; ?></td>

    <td>

<?php

if($fila['stock'] < 5){

    echo "<span style='color:red;
    font-weight:bold;'>
    ⚠ Stock Bajo
    </span>";

}elseif($fila['stock'] <= 10){

    echo "<span style='color:orange;
    font-weight:bold;'>
    ⚠ Stock Medio
    </span>";

}else{

    echo "<span style='color:green;
    font-weight:bold;'>
    ✔ Stock Normal
    </span>";
}

?>

</td>

<td>

<?php if($_SESSION['rol'] == 'Administrador'){ ?>

<a href="editar.php?id=<?php echo $fila['id']; ?>">
    Editar
</a>

<br>

<a href="eliminar.php?id=<?php echo $fila['id']; ?>">
    Eliminar
</a>

<?php }else{ ?>

Solo lectura

<?php } ?>

</td>

</tr>

<?php } ?>

</table>

<hr>
<br>

<h2>Ganancias por Producto</h2>

<table>

<tr>
    <th>Producto</th>
    <th>Cantidad Vendida</th>
    <th>Ganancia Generada</th>
</tr>

<?php

$ganancias = mysqli_query($conexion,

"SELECT
p.nombre,
SUM(v.cantidad) AS cantidad_vendida,
SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id

GROUP BY p.id

ORDER BY ganancia_total DESC");

while($g = mysqli_fetch_assoc($ganancias)){

?>

<tr>

<td><?php echo $g['nombre']; ?></td>

<td><?php echo $g['cantidad_vendida']; ?></td>

<td>
$<?php echo number_format($g['ganancia_total'],2); ?>
</td>

</tr>

<?php } ?>

</table>

<?php

$total_ganancias = mysqli_query($conexion,

"SELECT
SUM(v.cantidad * p.ganancia)
AS total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id"

);

$global = mysqli_fetch_assoc($total_ganancias);

?>

<br><br>
<h2>Resumen de Ganancias</h2>

<div class="card">

<h2>
$<?php echo number_format($global['total'],2); ?>
</h2>

<p>Ganancia Global de Productos</p>

</div>

</div>

    </div>

</body>

</html>