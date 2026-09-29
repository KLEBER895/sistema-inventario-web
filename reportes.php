<?php

session_start();

$tiempo_inactivo = 1800;

if(isset($_SESSION['ultimo_acceso'])){

    $tiempo_transcurrido =
        time() - $_SESSION['ultimo_acceso'];

    if($tiempo_transcurrido > $tiempo_inactivo){

        session_destroy();

        header("Location: login.php");

        exit();
    }
}

$_SESSION['ultimo_acceso'] = time();


if(
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['rol'])
){

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


/*
CAMBIO: Se agregó filtro de reportes por rango de fechas.
MOTIVO: Permitir consultar ventas realizadas entre una fecha
inicial y una fecha final, según observación de la predefensa.
FECHA: 28/09/2026
*/

$fecha_desde =
    isset($_GET['fecha_desde'])
    ? trim($_GET['fecha_desde'])
    : '';

$fecha_hasta =
    isset($_GET['fecha_hasta'])
    ? trim($_GET['fecha_hasta'])
    : '';


$mensaje_filtro = "";


/*
CAMBIO: Se utiliza consulta preparada cuando existen fechas.
MOTIVO: Evitar insertar directamente datos recibidos del usuario
dentro de la consulta SQL.
FECHA: 28/09/2026
*/

$sql_base = "
SELECT
    ventas.id,
    clientes.nombre AS cliente,
    productos.nombre AS producto,
    ventas.cantidad,

    COALESCE(
        detalle_ventas.precio_unitario,
        ventas.subtotal /
        NULLIF(ventas.cantidad, 0)
    ) AS precio_unitario,

    ventas.subtotal,
    ventas.iva,
    ventas.total,
    ventas.fecha

FROM ventas

INNER JOIN clientes
ON ventas.cliente_id = clientes.id

INNER JOIN productos
ON ventas.producto_id = productos.id

LEFT JOIN detalle_ventas
ON detalle_ventas.venta_id = ventas.id
AND detalle_ventas.producto_id = ventas.producto_id
";


if(
    $fecha_desde !== '' &&
    $fecha_hasta !== ''
){

    /*
    La fecha final se convierte al día siguiente.
    De esta manera incluimos correctamente todo
    el día seleccionado como fecha hasta.
    */

    $fecha_hasta_siguiente =
        date(
            'Y-m-d',
            strtotime(
                $fecha_hasta . ' +1 day'
            )
        );


    $sql =
        $sql_base .
        "
        WHERE ventas.fecha >= ?
        AND ventas.fecha < ?

        ORDER BY ventas.fecha DESC
        ";


    $stmt =
        mysqli_prepare(
            $conexion,
            $sql
        );


    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $fecha_desde,
        $fecha_hasta_siguiente
    );


    mysqli_stmt_execute(
        $stmt
    );


    $ventas =
        mysqli_stmt_get_result(
            $stmt
        );


    $mensaje_filtro =
        "Mostrando ventas desde "
        .
        htmlspecialchars($fecha_desde)
        .
        " hasta "
        .
        htmlspecialchars($fecha_hasta);


}else{

    $sql =
        $sql_base .
        "
        ORDER BY ventas.fecha DESC
        ";


    $ventas =
        mysqli_query(
            $conexion,
            $sql
        );
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Reportes de Ventas
</title>

<link
rel="stylesheet"
href="css/estilos.css">


<style>

.reportes-contenedor{
    max-width:1200px;
    margin:40px auto;
}

.filtro-fechas{
    background:white;
    padding:20px;
    border-radius:8px;
    margin-bottom:25px;
}

.filtro-fechas form{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:end;
}

.campo-fecha{
    flex:1;
    min-width:200px;
}

.campo-fecha label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

.campo-fecha input{
    width:100%;
    padding:10px;
    box-sizing:border-box;
}

.boton-filtrar{
    background:#0d6efd;
    color:white;
    border:none;
    padding:11px 20px;
    border-radius:5px;
    cursor:pointer;
    font-weight:bold;
}

/*
CAMBIO: Se mejoró el contraste visual del botón Limpiar filtro.
MOTIVO: Mejorar su visibilidad manteniendo un estilo profesional y sobrio.
FECHA: 28/09/2026
*/

.boton-limpiar{
    display:inline-block;
    background:#5B6B7A;
    color:white;
    text-decoration:none;
    padding:11px 20px;
    border-radius:5px;
    font-weight:bold;
}

.boton-limpiar:hover{
    background:#465563;
}

/*
CAMBIO: Se mejoró la visibilidad del mensaje del rango seleccionado.
MOTIVO: Aumentar el contraste sin utilizar colores demasiado llamativos.
FECHA: 28/09/2026
*/

.mensaje-filtro{
    background:#DCE6F1;
    color:#2F4050;
    padding:12px;
    margin-bottom:20px;
    border-left:4px solid #5B7FA3;
    border-radius:5px;
    font-weight:500;
}

</style>

</head>


<body>


<header>

<h1>
Reporte de Ventas
</h1>

</header>


<?php
include("menu.php");
?>


<div class="reportes-contenedor">


<h2>
Reporte de Ventas
</h2>


<!--
CAMBIO: Se agregó formulario visual Desde / Hasta.
MOTIVO: Permitir al usuario seleccionar el período
que desea consultar en el reporte.
FECHA: 28/09/2026
-->

<div class="filtro-fechas">

<form
method="GET"
action="reportes.php">


<div class="campo-fecha">

<label for="fecha_desde">
Desde
</label>

<input
type="date"
id="fecha_desde"
name="fecha_desde"
value="<?php
echo htmlspecialchars(
    $fecha_desde
);
?>">

</div>


<div class="campo-fecha">

<label for="fecha_hasta">
Hasta
</label>

<input
type="date"
id="fecha_hasta"
name="fecha_hasta"
value="<?php
echo htmlspecialchars(
    $fecha_hasta
);
?>">

</div>


<button
type="submit"
class="boton-filtrar">

Filtrar reporte

</button>


<a
href="reportes.php"
class="boton-limpiar">

Limpiar filtro

</a>


</form>

</div>


<?php

if($mensaje_filtro !== ''){

?>

<div class="mensaje-filtro">

<?php
echo $mensaje_filtro;
?>

</div>

<?php
}
?>


<div style="overflow-x:auto;">


<table>


<tr>

<th>ID</th>
<th>Cliente</th>
<th>Producto</th>
<th>Cantidad</th>
<th>Precio</th>
<th>Subtotal</th>
<th>IVA</th>
<th>Total</th>
<th>Fecha</th>

</tr>


<?php

if(
    $ventas &&
    mysqli_num_rows($ventas) > 0
){

    while(
        $fila =
        mysqli_fetch_assoc($ventas)
    ){

?>


<tr>


<td>
<?php
echo intval(
    $fila['id']
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $fila['cliente']
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $fila['producto']
);
?>
</td>


<td>
<?php
echo intval(
    $fila['cantidad']
);
?>
</td>


<td>
$
<?php
echo number_format(
    $fila['precio_unitario'],
    2
);
?>
</td>


<td>
$
<?php
echo number_format(
    $fila['subtotal'],
    2
);
?>
</td>


<td>
$
<?php
echo number_format(
    $fila['iva'],
    2
);
?>
</td>


<td>
$
<?php
echo number_format(
    $fila['total'],
    2
);
?>
</td>


<td>
<?php
echo htmlspecialchars(
    $fila['fecha']
);
?>
</td>


</tr>


<?php

    }

}else{

?>


<!--
CAMBIO: Se resaltó en verde la fila que informa que no existen ventas.
MOTIVO: Mejorar la visibilidad del mensaje y mantener coherencia
con los mensajes positivos del sistema.
FECHA: 28/09/2026
-->

<tr
style="
background:#d4edda;
color:#155724;
font-weight:bold;
">

<td
colspan="9"
style="
text-align:center;
padding:14px;
border-left:4px solid #198754;
">

No existen ventas en el rango seleccionado.

</td>

</tr>


<?php

}

?>


</table>


</div>

</div>


</body>

</html>