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

include("conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){

    header("Location: usuarios.php");
    exit();
}

$stmt = mysqli_prepare(
    $conexion,
    "SELECT id, nombre, usuario, clave, rol
     FROM usuarios
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if(!$usuario){

    header("Location: usuarios.php");
    exit();
}

if(isset($_POST['actualizar'])){

    $nombre = trim($_POST['nombre']);
    $usuario_nuevo = trim($_POST['usuario']);
    $clave_nueva = $_POST['clave'];
    $rol = $_POST['rol'];

    if($clave_nueva !== ''){

        $clave_hash = password_hash(
            $clave_nueva,
            PASSWORD_DEFAULT
        );

        $stmt_update = mysqli_prepare(
            $conexion,
            "UPDATE usuarios
             SET nombre = ?,
                 usuario = ?,
                 clave = ?,
                 rol = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_update,
            "ssssi",
            $nombre,
            $usuario_nuevo,
            $clave_hash,
            $rol,
            $id
        );

    }else{

        $stmt_update = mysqli_prepare(
            $conexion,
            "UPDATE usuarios
             SET nombre = ?,
                 usuario = ?,
                 rol = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_update,
            "sssi",
            $nombre,
            $usuario_nuevo,
            $rol,
            $id
        );
    }

    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    header("Location: usuarios.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Usuario</title>

<link rel="stylesheet" href="css/estilos.css">

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
value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
required>

<input type="text"
name="usuario"
value="<?php echo htmlspecialchars($usuario['usuario']); ?>"
required>

<input type="password"
name="clave"
placeholder="Nueva contraseña (opcional)">

<select name="rol" required>

<option value="Administrador"
<?php if($usuario['rol'] === "Administrador") echo "selected"; ?>>
Administrador
</option>

<option value="Empleado"
<?php if($usuario['rol'] === "Empleado") echo "selected"; ?>>
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