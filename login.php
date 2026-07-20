<?php

session_start();

include("conexion.php");

if(isset($_POST['ingresar'])){

    $usuario = $_POST['usuario'];
$clave = $_POST['clave'];

$consulta = mysqli_query($conexion,

    "SELECT * FROM usuarios
    WHERE usuario='$usuario'"

);

if(mysqli_num_rows($consulta) > 0){

    $fila = mysqli_fetch_assoc($consulta);

    if(
    password_verify($clave, $fila['clave'])
    || $clave == $fila['clave']
){

        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['rol'] = $fila['rol'];

        header("Location:index.php");
        exit();

    }else{

        echo "<script>
        alert('Contraseña incorrecta');
        window.location='login.php';
        </script>";
        exit();
    }

}else{

    echo "<script>
    alert('Usuario no encontrado');
    window.location='login.php';
    </script>";
    exit();
}

        
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login</title>

<style>

body{

    margin:0;
    font-family:Arial;
    background:#f4f4f4;
}

.login{

    width:350px;
    background:white;
    padding:30px;
    margin:auto;
    margin-top:100px;
    border-radius:10px;
    box-shadow:0px 0px 10px #ccc;
}

h2{

    text-align:center;
    color:#1565c0;
}

input{

    width:100%;
    padding:10px;
    margin-top:10px;
    margin-bottom:20px;
}

button{

    width:100%;
    padding:10px;
    background:#1565c0;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

button:hover{

    background:#0d47a1;
}

.error{

    color:red;
    text-align:center;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="login">

<h2>Iniciar Sesión</h2>

<form method="POST">

<input type="text"
name="usuario"
placeholder="Usuario"
required>

<div style="position:relative;">

<div style="position:relative;">

    <input type="password"
    id="clave"
    name="clave"
    required
    style="width:100%;">

    <span id="ojo"
    onclick="mostrarClave()"
    style="
    position:absolute;
    right:15px;
    top:12px;
    cursor:pointer;
    font-size:20px;
    ">
    👁️
    </span>

</div>

<button type="submit" name="ingresar">
Ingresar
</button>

</form>

<?php

if(isset($error)){

    echo "<p class='error'>$error</p>";
}

?>

</div>

<script>

function mostrarClave(){

    var clave = document.getElementById("clave");
    var ojo = document.getElementById("ojo");

    if(clave.type === "password"){

        clave.type = "text";
        ojo.innerHTML = "🙈";

    }else{

        clave.type = "password";
        ojo.innerHTML = "👁️";
    }
}

</script>

</body>
</html>