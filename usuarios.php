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

if($_SESSION['rol'] != 'Administrador'){

    header("Location:index.php");
    exit();
}

include("conexion.php");

?>


<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Módulo de Usuarios</title>

<link rel="stylesheet" href="css/estilos.css">

<style>

.usuarios-contenedor{
    max-width:900px;
    margin:auto;
}

</style>

</head>

<body>

<header>
    <h1>Módulo de Usuarios</h1>
</header>

<?php include("menu.php"); ?>

<div class="usuarios-contenedor">

<form method="POST">

<input type="text"
name="nombre"
placeholder="Nombre completo"
required>

<input type="text"
name="usuario"
placeholder="Usuario"
required>

<input type="password"
name="clave"
placeholder="Contraseña"
required>

<select name="rol" required>

<option value="">
Seleccione rol
</option>

<option value="Administrador">
Administrador
</option>

<option value="Empleado">
Empleado
</option>

</select>

<button type="submit" name="guardar">
Guardar Usuario
</button>

</form>

<?php

if(isset($_POST['guardar'])){

    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];
    $clave = password_hash($clave, PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    mysqli_query($conexion,

        "INSERT INTO usuarios
        (nombre, usuario, clave, rol)

        VALUES

        ('$nombre','$usuario','$clave','$rol')"

    );

    echo "<div class='mensaje' style='color:green;'>
            Usuario registrado correctamente
          </div>";
}

?>

<h2>Lista de Usuarios</h2>

<div class="tabla-responsive">

<table>
</div>

<tr>
<th>ID</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Rol</th>
<th>Acciones</th>
</tr>

<?php

$usuarios = mysqli_query($conexion,
    "SELECT * FROM usuarios");

while($fila = mysqli_fetch_assoc($usuarios)){

?>

<tr>

<td><?php echo $fila['id']; ?></td>

<td><?php echo $fila['nombre']; ?></td>

<td><?php echo $fila['usuario']; ?></td>

<td><?php echo $fila['rol']; ?></td>

<td>

<a href="editar_usuario.php?id=<?php echo $fila['id']; ?>">
    Editar
</a>

|

<a href="resetear_clave.php?id=<?php echo $fila['id']; ?>"
onclick="return confirm('¿Restablecer contraseña?');">
    Resetear
</a>

|

<a href="eliminar_usuario.php?id=<?php echo $fila['id']; ?>"
onclick="return confirm('¿Eliminar usuario?');">
    Eliminar
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>