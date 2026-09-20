<?php

session_start();

if(!isset($_SESSION['usuario']) || !isset($_SESSION['id_usuario'])){

    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_usuario = intval($_SESSION['id_usuario']);

$stmt = mysqli_prepare(
    $conexion,
    "SELECT id, usuario
     FROM usuarios
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$datos = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if(!$datos){

    session_destroy();

    header("Location: login.php");
    exit();
}

if(isset($_POST['guardar'])){

    $nuevo_usuario = trim($_POST['usuario']);
    $nueva_clave = $_POST['clave'];

    if($nueva_clave !== ''){

        $clave_hash = password_hash(
            $nueva_clave,
            PASSWORD_DEFAULT
        );

        $stmt_update = mysqli_prepare(
            $conexion,
            "UPDATE usuarios
             SET usuario = ?,
                 clave = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_update,
            "ssi",
            $nuevo_usuario,
            $clave_hash,
            $id_usuario
        );

    }else{

        $stmt_update = mysqli_prepare(
            $conexion,
            "UPDATE usuarios
             SET usuario = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_update,
            "si",
            $nuevo_usuario,
            $id_usuario
        );
    }

    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    $_SESSION['usuario'] = $nuevo_usuario;

    echo "<script>
    alert('Datos actualizados correctamente');
    window.location='perfil.php';
    </script>";

    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mi Perfil</title>

<link rel="stylesheet" href="css/estilos.css">

</head>

<body>

<header>
<h1>Mi Perfil</h1>
</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<div class="card">

<form method="POST">

<label>Usuario</label>

<input type="text"
name="usuario"
value="<?php echo htmlspecialchars($datos['usuario']); ?>"
required>

<label>Nueva Contraseña</label>

<div style="position:relative;">

    <input type="password"
    id="clave"
    name="clave"
    placeholder="Dejar vacío para conservar la contraseña actual">

    <span id="ojo"
    onclick="mostrarClave()"
    style="
    position:absolute;
    right:15px;
    top:12px;
    cursor:pointer;
    font-size:20px;">
    👁️
    </span>

</div>

<br><br>

<button type="submit" name="guardar">
    Actualizar Datos
</button>

</form>

</div>

</div>

<script>

function mostrarClave(){

    var x = document.getElementById("clave");

    if(x.type === "password"){

        x.type = "text";

    }else{

        x.type = "password";
    }
}

</script>

</body>

</html>