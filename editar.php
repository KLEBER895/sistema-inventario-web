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

if($_SESSION['rol'] !== 'Administrador'){

    header("Location: index.php");
    exit();
}

include("conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){

    header("Location: productos.php");
    exit();
}

$stmt = mysqli_prepare(
    $conexion,
    "SELECT id, nombre, precio_compra, precio_venta,
            ganancia, stock, unidad
     FROM productos
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$fila = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if(!$fila){

    header("Location: productos.php");
    exit();
}

if(isset($_POST['actualizar'])){

    $nombre = trim($_POST['nombre']);
    $precio_compra = floatval($_POST['precio_compra']);
    $precio_venta = floatval($_POST['precio_venta']);
    $stock = intval($_POST['stock']);
    $unidad = trim($_POST['unidad']);

    $ganancia = $precio_venta - $precio_compra;

    $stmt_update = mysqli_prepare(
        $conexion,
        "UPDATE productos
         SET nombre = ?,
             precio_compra = ?,
             precio_venta = ?,
             ganancia = ?,
             stock = ?,
             unidad = ?,
             fecha_actualizacion = NOW()
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_update,
        "sdddisi",
        $nombre,
        $precio_compra,
        $precio_venta,
        $ganancia,
        $stock,
        $unidad,
        $id
    );

    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    header("Location: productos.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Producto</title>

<link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header>
    <h1>Editar Producto</h1>
</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<div class="card">

<h2>Datos del Producto</h2>

<form method="POST">

<label>Nombre</label>

<input type="text"
name="nombre"
value="<?php echo htmlspecialchars($fila['nombre']); ?>"
required>

<label>Precio de Compra</label>

<input type="number"
name="precio_compra"
step="0.01"
min="0"
value="<?php echo htmlspecialchars($fila['precio_compra']); ?>"
required>

<label>Precio de Venta</label>

<input type="number"
name="precio_venta"
step="0.01"
min="0"
value="<?php echo htmlspecialchars($fila['precio_venta']); ?>"
required>

<label>Stock</label>

<input type="number"
name="stock"
min="0"
value="<?php echo intval($fila['stock']); ?>"
required>

<label>Unidad</label>

<input type="text"
name="unidad"
value="<?php echo htmlspecialchars($fila['unidad']); ?>"
required>

<button type="submit" name="actualizar">
Actualizar Producto
</button>

</form>

</div>

</div>

</body>
</html>