
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

    header("Location: login.php");
    exit();
}

include("conexion.php");

$productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));

$clientes = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM clientes"));

$ventas = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM ventas"));

$usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));

$stock_bajo = mysqli_num_rows(

    mysqli_query($conexion,

    "SELECT * FROM productos
    WHERE stock < 5")

);

$stock_medio = mysqli_num_rows(

    mysqli_query($conexion,

    "SELECT * FROM productos
    WHERE stock >= 5
    AND stock <= 10")

);

$alertas = mysqli_query($conexion,

"SELECT nombre, stock

FROM productos

WHERE stock <= 10

ORDER BY stock ASC");


$consulta_total = mysqli_query($conexion,
"SELECT SUM(total) AS dinero_total FROM ventas"
);

$total_ventas = mysqli_fetch_assoc($consulta_total);

$consulta_ganancia = mysqli_query($conexion,

"SELECT
SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id"

);

$ganancia_total = mysqli_fetch_assoc($consulta_ganancia);

$consulta_productos = mysqli_query($conexion,

"SELECT
p.nombre,
SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id

GROUP BY p.id

ORDER BY ganancia_total DESC");

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    margin:0;
    font-family:Arial, Helvetica, sans-serif;
    background:#eef2f7;
}

/* HEADER */

header{
    background:linear-gradient(135deg,#1565c0,#0d47a1);
    color:white;
    padding:30px;
    text-align:center;
    box-shadow:0px 4px 10px rgba(0,0,0,0.2);
}

header h1{
    margin:0;
    font-size:40px;
}

/* MENU */

.menu{
    background:white;
    display:flex;
    justify-content:center;
    gap:25px;
    padding:18px;
    box-shadow:0px 2px 8px rgba(0,0,0,0.1);
}

.menu a{
    text-decoration:none;
    color:#1565c0;
    font-weight:bold;
    transition:0.3s;
}

.menu a:hover{
    color:#0d47a1;
    transform:scale(1.05);
}

/* CARDS */

.card{
    background:white;
    border-radius:15px;
    padding:30px;
    text-align:center;
    box-shadow:0px 4px 15px rgba(0,0,0,0.1);
    transition:0.3s;
}

.card:hover{
    transform:translateY(-8px);
}

.card h2{
    font-size:45px;
    margin:0;
    color:#1565c0;
}

.card p{
    margin-top:15px;
    font-size:20px;
    font-weight:bold;
    color:#333;
}

/* GRAFICO */

.grafico-box{
    width:85%;
    margin:40px auto;
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0px 4px 15px rgba(0,0,0,0.1);
}

.grafico-box h2{
    text-align:center;
    color:#1565c0;
    margin-bottom:30px;
}

canvas{
    max-height:500px;
}

/* ========================= */
/* RESPONSIVE MOVIL */
/* ========================= */

@media(max-width:768px){

    body{
        padding:10px;
    }

    header h1{
        font-size:28px;
        text-align:center;
    }

    .contenedor{
        display:flex;
        flex-direction:column;
        gap:15px;
    }

    .card{
        width:100%;
    }

    .grafico-box{
        width:100%;
        padding:15px;
    }

    #grafico{
        max-height:300px;
    }

    canvas{
        width:100% !important;
        height:auto !important;
    }

    table{
        display:block;
        overflow-x:auto;
        white-space:nowrap;
    }

}

.menu-toggle{
    display:none;
    width:100%;
    padding:15px;
    background:#1565c0;
    color:white;
    border:none;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
}

@media(max-width:768px){

    .menu-toggle{
        display:block;
    }

    .menu{
        display:none;
        flex-direction:column;
        gap:10px;
        padding:15px;
    }

    .menu.mostrar{
        display:flex;
    }

}

</style>

</head>

<body>

<header>

<h1>Sistema de Inventario Web</h1>

</header>

<?php include("menu.php"); ?>

<div class="dashboard-contenedor">

<div class="card">
<h2><?php echo $productos; ?></h2>
<p>Productos Registrados</p>
</div>

<div class="card">
<h2><?php echo $clientes; ?></h2>
<p>Clientes Registrados</p>
</div>

<div class="card">
<h2><?php echo $ventas; ?></h2>
<p>Ventas Realizadas</p>
</div>

<div class="card">
<h2><?php echo $usuarios; ?></h2>
<p>Usuarios del Sistema</p>
</div>

<div class="card">
<h2>$<?php echo number_format($total_ventas['dinero_total'],2); ?></h2>
<p>Dinero Total Vendido</p>
</div>

<div class="card">
<h2>
$<?php echo number_format($ganancia_total['ganancia_total'],2); ?>
</h2>
<p>Ganancia Total</p>
</div>

<div class="card">

<h2 style="color:red;">
<?php echo $stock_bajo; ?>
</h2>

<p>🔴 Stock Bajo</p>

</div>

<div class="card">

<h2 style="color:orange;">
<?php echo $stock_medio; ?>
</h2>

<p>🟡 Stock Medio</p>

</div>

<div class="grafico-box">

<h2>Resumen General del Sistema</h2>

<canvas id="grafico"></canvas>

</div>

<div class="grafico-box">

<h2>Ganancias por Producto</h2>

<canvas id="graficoGanancias"></canvas>

</div>

<div class="grafico-box">

<h2>⚠ Alertas de Inventario</h2>

<table>

<tr>
    <th>Producto</th>
    <th>Stock</th>
    <th>Estado</th>
</tr>

<?php

while($alerta = mysqli_fetch_assoc($alertas)){

?>

<tr>

<td>
<?php echo htmlspecialchars($alerta['nombre']); ?>
</td>

<td>
<?php echo intval($alerta['stock']); ?>
</td>

<td>

<?php

if($alerta['stock'] < 5){

    echo "<span style='color:red;
    font-weight:bold;'>
    🔴 Stock Bajo
    </span>";

}else{

    echo "<span style='color:orange;
    font-weight:bold;'>
    🟡 Stock Medio
    </span>";
}

?>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<script>

const ctx = document.getElementById('grafico');

new Chart(ctx, {

    type: 'doughnut',

    data: {
        labels:[
            'Productos',
            'Clientes',
            'Ventas',
            'Usuarios',
            'Dinero',
            'Ganancia'
],

        datasets:[{
            data:[
                <?php echo $productos; ?>,
                <?php echo $clientes; ?>,
                <?php echo $ventas; ?>,
                <?php echo $usuarios; ?>,
                <?php echo $total_ventas['dinero_total']; ?>,
                <?php echo $ganancia_total['ganancia_total']; ?>
]
        }]
    }
});

const ctxGanancias =
document.getElementById('graficoGanancias');

new Chart(ctxGanancias, {

    type: 'bar',

    data: {

        labels: [

<?php

$consulta_productos = mysqli_query($conexion,

"SELECT
p.nombre,
SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id

GROUP BY p.id

ORDER BY ganancia_total DESC");

while($fila = mysqli_fetch_assoc($consulta_productos)){

    echo "'" . $fila['nombre'] . "',";
}

?>

        ],

        datasets:[{

            label:'Ganancia $',

            data:[

<?php

$consulta_productos = mysqli_query($conexion,

"SELECT
p.nombre,
SUM(v.cantidad * p.ganancia) AS ganancia_total

FROM ventas v

INNER JOIN productos p
ON v.producto_id = p.id

GROUP BY p.id

ORDER BY ganancia_total DESC");

while($fila = mysqli_fetch_assoc($consulta_productos)){

    echo $fila['ganancia_total'] . ",";
}

?>

            ]

        }]
    }
});

function toggleMenu(){

    document
    .getElementById("menu")
    .classList
    .toggle("mostrar");

}

</script>

</body>
</html>
```
