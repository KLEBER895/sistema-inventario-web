<?php

session_start();

if($_SESSION['rol'] != 'Administrador'){

    header("Location:index.php");
    exit();
}

include("conexion.php");

$id = $_GET['id'];

$consulta = mysqli_query($conexion,
"SELECT * FROM usuarios
WHERE id='$id'");

$usuario = mysqli_fetch_assoc($consulta);

if(isset($_POST['actualizar'])){

    $nombre = $_POST['nombre'];
    $usuario_nuevo = $_POST['usuario'];
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];

    mysqli_query($conexion,

    "UPDATE usuarios

    SET nombre='$nombre',
        usuario='$usuario_nuevo',
        clave='$clave',
        rol='$rol'

    WHERE id='$id'");

    header("Location: usuarios.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Editar Usuario</title>

<link rel="stylesheet"
href="css/estilos.css">

</head>

<body>

<header>
<h1>Editar Usuario</h1>
</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<div class="card">

<form method="POST">

<input type="text"
name="nombre"
value="<?php echo $usuario['nombre']; ?>"
required>

<input type="text"
name="usuario"
value="<?php echo $usuario['usuario']; ?>"
required>

<input type="password"
name="clave"
value="<?php echo $usuario['clave']; ?>"
required>

<select name="rol">

<option value="Administrador"
<?php if($usuario['rol']=="Administrador") echo "selected"; ?>>
Administrador
</option>

<option value="Empleado"
<?php if($usuario['rol']=="Empleado") echo "selected"; ?>>
Empleado
</option>

</select>

<button type="submit"
name="actualizar">

Actualizar Usuario

</button>

</form>

</div>

</div>

</body>

</html>