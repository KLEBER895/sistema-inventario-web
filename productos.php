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

include("conexion.php");

$es_admin = ($_SESSION['rol'] === 'Administrador');

if(isset($_POST['guardar']) && !$es_admin){

    header("Location: index.php");
    exit();
}

if (isset($_GET['compra']) && $_GET['compra'] == 'ok') {

    echo "<div class='mensaje' style='color:green; font-weight:bold; text-align:center;'>
            ✓ Compra registrada correctamente. Stock actualizado.
          </div>";
}

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

    <input type="text"
           name="nombre"
           placeholder="Nombre del producto"
           required>

    <input type="number"
           name="precio_compra"
           step="0.01"
           min="0"
           placeholder="Precio proveedor"
           required>

    <input type="number"
           name="precio_venta"
           step="0.01"
           min="0"
           placeholder="Precio venta al público"
           required>

    <input type="number"
           name="stock"
           min="0"
           placeholder="Stock"
           required>

    <input type="text"
           name="unidad"
           placeholder="Unidad"
           required>

    <button type="submit"
            name="guardar">
        Guardar Producto
    </button>

</form>

            <?php
if(isset($_GET['eliminado'])){

    if($_GET['eliminado'] === 'ok'){
        echo "<div style='
            color: green;
            font-weight: bold;
            text-align: center;
            padding: 12px;
            margin: 15px;
            background: #eaf8ea;
            border-radius: 6px;
        '>
        ✓ Producto eliminado correctamente.
        </div>";

    }elseif($_GET['eliminado'] === 'error'){
        echo "<div style='
    color: red;
    font-weight: bold;
    text-align: center;
    padding: 10px;
    margin: 15px 0;
    background: #fdeaea;
    border: 1px solid #f5b5b5;
    border-radius: 6px;
    width: 100%;
    box-sizing: border-box;
'>
⚠ No se puede eliminar el producto porque tiene compras o ventas registradas.
</div>";
    }
}
?>
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



<!--
CAMBIO: Se agregó filtro por rango de fechas para el reporte
de ganancias por producto.
MOTIVO: Permitir conocer las ventas y ganancias generadas
entre una fecha inicial y una fecha final.
FECHA: 28/09/2026
-->

<?php

$fecha_desde =
    isset($_GET['fecha_desde'])
    ? trim($_GET['fecha_desde'])
    : '';

$fecha_hasta =
    isset($_GET['fecha_hasta'])
    ? trim($_GET['fecha_hasta'])
    : '';

?>

<div class="card">

<h3>Filtrar ganancias por fecha</h3>

<form method="GET">

<label>Desde</label>

<input
type="date"
name="fecha_desde"
value="<?php
echo htmlspecialchars($fecha_desde);
?>">


<label>Hasta</label>

<input
type="date"
name="fecha_hasta"
value="<?php
echo htmlspecialchars($fecha_hasta);
?>">


<button type="submit">
Filtrar reporte
</button>


<a
href="productos.php"
style="
display:inline-block;
background:#5B6B7A;
color:white;
padding:10px 20px;
border-radius:5px;
text-decoration:none;
font-weight:bold;
margin-top:10px;
">

Limpiar filtro

</a>

</form>

</div>

<br>

<?php

if(
    $fecha_desde !== '' &&
    $fecha_hasta !== ''
){

?>

<!--
CAMBIO: Se agregó mensaje visual del rango aplicado
al reporte de ganancias por producto.
MOTIVO: Permitir identificar rápidamente que los resultados
corresponden al período seleccionado.
FECHA: 28/09/2026
-->

<div
style="
    background:#d4edda;
    color:#155724;
    border-left:4px solid #198754;
    padding:12px;
    margin:15px 0 20px 0;
    border-radius:5px;
    font-weight:bold;
">

Mostrando ganancias desde
<?php
echo htmlspecialchars($fecha_desde);
?>
hasta
<?php
echo htmlspecialchars($fecha_hasta);
?>

</div>

<?php

}

?>

<h2>Ganancias por Producto</h2>

<table>

<tr>
    <th>Producto</th>
    <th>Cantidad Vendida</th>
    <th>Ganancia Generada</th>
</tr>

<?php

/*
CAMBIO: El reporte de ganancias ahora puede filtrarse
por fecha de venta.
MOTIVO: Mostrar únicamente las ventas y ganancias
correspondientes al periodo seleccionado.
FECHA: 28/09/2026
*/

$sql_ganancias = "
SELECT
    p.nombre,
    SUM(v.cantidad) AS cantidad_vendida,
    SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id
";


if(
    $fecha_desde !== '' &&
    $fecha_hasta !== ''
){

    $fecha_hasta_siguiente =
        date(
            'Y-m-d',
            strtotime(
                $fecha_hasta . ' +1 day'
            )
        );

    $sql_ganancias .= "
    WHERE v.fecha >= ?
    AND v.fecha < ?

    GROUP BY p.id

    ORDER BY ganancia_total DESC
    ";

    $stmt_ganancias =
        mysqli_prepare(
            $conexion,
            $sql_ganancias
        );

    mysqli_stmt_bind_param(
        $stmt_ganancias,
        "ss",
        $fecha_desde,
        $fecha_hasta_siguiente
    );

    mysqli_stmt_execute(
        $stmt_ganancias
    );

    $ganancias =
        mysqli_stmt_get_result(
            $stmt_ganancias
        );

}else{

    $sql_ganancias .= "
    GROUP BY p.id

    ORDER BY ganancia_total DESC
    ";

    $ganancias =
        mysqli_query(
            $conexion,
            $sql_ganancias
        );
}

/*
CAMBIO: Se agregó mensaje cuando el rango filtrado
no contiene ventas ni ganancias.
MOTIVO: Informar claramente al usuario que la consulta
fue correcta, pero no existen datos para ese período.
FECHA: 28/09/2026
*/

if(
    $ganancias &&
    mysqli_num_rows($ganancias) > 0
){

    while(
        $g = mysqli_fetch_assoc($ganancias)
    ){

?>

<tr>

<td>
<?php
echo htmlspecialchars(
    $g['nombre']
);
?>
</td>

<td>
<?php
echo intval(
    $g['cantidad_vendida']
);
?>
</td>

<td>
$
<?php
echo number_format(
    $g['ganancia_total'],
    2
);
?>
</td>

</tr>

<?php

    }

}else{

?>

<tr>

<td
colspan="3"
style="
    text-align:center;
    background:#FFF3CD;
    color:#664D03;
    border-left:4px solid #D39E00;
    padding:14px;
    font-weight:bold;
">

No se registraron ventas ni ganancias
en el rango de fechas seleccionado.

</td>

</tr>

<?php

}

?>

</table>

<?php

/*
CAMBIO: El resumen global de ganancias utiliza
el mismo rango de fechas del reporte.
MOTIVO: Evitar que la tabla filtrada y el total
muestren periodos diferentes.
FECHA: 28/09/2026
*/

$sql_total = "
SELECT
SUM(v.cantidad * p.ganancia)
AS total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id
";


if(
    $fecha_desde !== '' &&
    $fecha_hasta !== ''
){

    $sql_total .= "
    WHERE v.fecha >= ?
    AND v.fecha < ?
    ";

    $stmt_total =
        mysqli_prepare(
            $conexion,
            $sql_total
        );

    mysqli_stmt_bind_param(
        $stmt_total,
        "ss",
        $fecha_desde,
        $fecha_hasta_siguiente
    );

    mysqli_stmt_execute(
        $stmt_total
    );

    $total_ganancias =
        mysqli_stmt_get_result(
            $stmt_total
        );

}else{

    $total_ganancias =
        mysqli_query(
            $conexion,
            $sql_total
        );
}


$global =
    mysqli_fetch_assoc(
        $total_ganancias
    );

?>

<br><br>
<h2>Resumen de Ganancias</h2>

<div class="card">

<h2>
$<?php
echo number_format(
    $global['total'] ?? 0,
    2
);
?>
</h2>

<p>Ganancia Global de Productos</p>

</div>

</div>

    </div>

</body>

</html>