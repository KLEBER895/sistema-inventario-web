<?php

session_start();

if(!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])){

    header("Location: login.php");
    exit();
}

if($_SESSION['rol'] !== 'Administrador'){

    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Historial de Respaldos</title>

<link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header>

<h1>Historial de Respaldos</h1>

</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<br>

<a href="backup.php">

<button>

Generar Nuevo Respaldo

</button>

</a>

<br><br>

<table>

<tr>

<th>Archivo</th>

<th>Descargar</th>

</tr>

<?php

/*
CAMBIO: 21/10/2026
MOTIVO: Leer los respaldos desde BACKUP_DIR y descargarlos
mediante descargar_respaldo.php (la carpeta no es pública).
*/
$config = require __DIR__ . '/configuracion.php';

$archivos = glob($config['respaldos']['carpeta'] . '/*.sql') ?: [];
rsort($archivos);

foreach($archivos as $archivo){

?>

<tr>

<td>

<?php echo htmlspecialchars(basename($archivo)); ?>

</td>

<td>

<a href="descargar_respaldo.php?archivo=<?php echo urlencode(basename($archivo)); ?>">

Descargar

</a>

</td>

</tr>

<?php

}

?>

</table>

</div>

</body>

</html>