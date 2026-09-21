<?php

session_start();

$tiempo_inactivo = 1800;

if (isset($_SESSION['ultimo_acceso'])) {

    $tiempo_transcurrido = time() - $_SESSION['ultimo_acceso'];

    if ($tiempo_transcurrido > $tiempo_inactivo) {

        session_destroy();

        header("Location: login.php");

        exit();
    }
}

$_SESSION['ultimo_acceso'] = time();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {

    header("Location: login.php");

    exit();
}

$rol_permitido = (
    $_SESSION['rol'] === 'Administrador' ||
    $_SESSION['rol'] === 'Empleado'
);

if (!$rol_permitido) {

    header("Location: index.php");
    exit();
}

include("conexion.php");


$consulta = mysqli_query(
    $conexion,
    "SELECT 
        compras.id,
        productos.codigo,
        productos.nombre AS producto,
        compras.cantidad,
        productos.unidad,
        compras.fecha

    FROM compras

    INNER JOIN productos
    ON compras.producto_id = productos.id

    ORDER BY compras.id DESC"
);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Historial de Compras</title>

    <link rel="stylesheet"
          href="css/estilos.css">

</head>

<body>

<?php include("menu.php"); ?>

<div class="contenedor">

    <header>

        <h1>Historial de Compras</h1>

    </header>

    <div class="card">

        <h2>Entradas de Inventario</h2>

        <p>
            Registro histórico de las unidades
            ingresadas al inventario.
        </p>

        <table>

            <tr>

                <th>ID</th>
                <th>Código</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Fecha</th>

            </tr>

            <?php while ($fila = mysqli_fetch_assoc($consulta)) { ?>

                <tr>

                    <td>
                        <?php echo $fila['id']; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $fila['codigo']
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $fila['producto']
                        );
                        ?>
                    </td>

                    <td>
                        <?php echo intval($fila['cantidad']); ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $fila['unidad']
                        );
                        ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($fila['fecha']); ?>
                    </td>

                </tr>

            <?php } ?>

        </table>

    </div>

</div>

</body>

</html>