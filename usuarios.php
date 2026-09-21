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

if(isset($_POST['guardar'])){

    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];

    $clave_hash = password_hash(
        $clave,
        PASSWORD_DEFAULT
    );

    $stmt = mysqli_prepare(
        $conexion,
        "INSERT INTO usuarios
        (nombre, usuario, clave, rol)
        VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nombre,
        $usuario,
        $clave_hash,
        $rol
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $mensaje = "Usuario registrado correctamente";
}

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

if(isset($mensaje)){

    echo "<div class='mensaje' style='color:green;'>"
        . htmlspecialchars($mensaje) .
        "</div>";
}

?>

<h2>Lista de Usuarios</h2>

<div class="tabla-responsive">

<table>

<tr>
<th>ID</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Rol</th>
<th>Acciones</th>
</tr>

<?php

$usuarios = mysqli_query(
    $conexion,
    "SELECT id, nombre, usuario, rol
     FROM usuarios"
);

while($fila = mysqli_fetch_assoc($usuarios)){

?>

<tr>

<td><?php echo intval($fila['id']); ?></td>

<td><?php echo htmlspecialchars($fila['nombre']); ?></td>

<td><?php echo htmlspecialchars($fila['usuario']); ?></td>

<td><?php echo htmlspecialchars($fila['rol']); ?></td>

<td>

<a href="editar_usuario.php?id=<?php echo intval($fila['id']); ?>">
    Editar
</a>

|

<a href="resetear_clave.php?id=<?php echo intval($fila['id']); ?>"
onclick="return confirm('¿Restablecer contraseña?');">
    Resetear
</a>

|

<a href="eliminar_usuario.php?id=<?php echo intval($fila['id']); ?>"
onclick="return confirm('¿Eliminar usuario?');">
    Eliminar
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>