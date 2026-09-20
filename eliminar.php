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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){

    header("Location: productos.php");
    exit();
}

try {

    $stmt = mysqli_prepare(
        $conexion,
        "DELETE FROM productos
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: productos.php?eliminado=ok");
    exit();

} catch (mysqli_sql_exception $e) {

    if(isset($stmt)){
        mysqli_stmt_close($stmt);
    }

    header("Location: productos.php?eliminado=error");
    exit();
}

?>