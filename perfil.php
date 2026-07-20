<?php

session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

include("conexion.php");

$usuario_actual = $_SESSION['usuario'];

$consulta = mysqli_query($conexion,
"SELECT * FROM usuarios
WHERE usuario='$usuario_actual'");

$datos = mysqli_fetch_assoc($consulta);

if(isset($_POST['guardar'])){

    $nuevo_usuario = $_POST['usuario'];
    $nueva_clave = $_POST['clave'];

    mysqli_query($conexion,

    "UPDATE usuarios
    SET usuario='$nuevo_usuario',
        clave='$nueva_clave'
    WHERE id='".$datos['id']."'");

    $_SESSION['usuario'] = $nuevo_usuario;

    echo "<script>
    alert('Datos actualizados correctamente');
    window.location='perfil.php';
    </script>";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
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
value="<?php echo $datos['usuario']; ?>"
required>

<label>Nueva Contraseña</label>

<div style="position:relative;">

    <input type="password"
    id="clave"
    name="clave"
    value="<?php echo $datos['clave']; ?>"
    required>

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