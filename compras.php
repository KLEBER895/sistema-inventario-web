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

if (isset($_POST['registrar_compra']) && !$rol_permitido) {

    header("Location: index.php");
    exit();
}

if (isset($_GET['compra']) && $_GET['compra'] == 'ok') {

    echo "<div class='mensaje' style='color:green; font-weight:bold; text-align:center;'>
            ✓ Compra registrada correctamente. Stock actualizado.
          </div>";
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['registrar_compra'])) {

    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);

    if ($producto_id <= 0 || $cantidad <= 0) {

        $mensaje = "Debe seleccionar un producto e ingresar una cantidad válida.";
        $tipo_mensaje = "red";

    } else {

        mysqli_begin_transaction($conexion);

        try {

            $consulta_producto = mysqli_prepare(
                $conexion,
                "SELECT stock FROM productos WHERE id = ? FOR UPDATE"
            );

            mysqli_stmt_bind_param(
                $consulta_producto,
                "i",
                $producto_id
            );

            mysqli_stmt_execute($consulta_producto);

            $resultado_producto = mysqli_stmt_get_result($consulta_producto);

            if (mysqli_num_rows($resultado_producto) == 0) {

                throw new Exception("El producto seleccionado no existe.");
            }

            $producto = mysqli_fetch_assoc($resultado_producto);

            mysqli_stmt_close($consulta_producto);

            $stock_actual = intval($producto['stock']);

            $nuevo_stock = $stock_actual + $cantidad;

            $insertar_compra = mysqli_prepare(
                $conexion,
                "INSERT INTO compras
                (producto_id, cantidad, fecha)
                VALUES (?, ?, NOW())"
            );

            mysqli_stmt_bind_param(
                $insertar_compra,
                "ii",
                $producto_id,
                $cantidad
            );

            if (!mysqli_stmt_execute($insertar_compra)) {

                throw new Exception(
                    "No se pudo registrar la compra."
                );
            }

            mysqli_stmt_close($insertar_compra);

            $actualizar_stock = mysqli_prepare(
                $conexion,
                "UPDATE productos
                SET stock = ?,
                    fecha_actualizacion = NOW()
                WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $actualizar_stock,
                "ii",
                $nuevo_stock,
                $producto_id
            );

            if (!mysqli_stmt_execute($actualizar_stock)) {

                throw new Exception(
                    "No se pudo actualizar el stock."
                );
            }

            mysqli_stmt_close($actualizar_stock);

           mysqli_commit($conexion);

            header("Location: productos.php?compra=ok");
            exit();

        } catch (Exception $e) {

            mysqli_rollback($conexion);

            $mensaje = $e->getMessage();
            $tipo_mensaje = "red";
        }
    }
}

$productos = mysqli_query(
    $conexion,
    "SELECT id, codigo, nombre, stock, unidad
    FROM productos
    ORDER BY nombre ASC"
);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Compras - Ingreso de Stock</title>

    <link rel="stylesheet"
          href="css/estilos.css">

</head>

<body>

<?php include("menu.php"); ?>

<div class="contenedor">

    <header>

        <h1>Compras / Ingreso de Stock</h1>

    </header>

    <?php if ($mensaje != "") { ?>

        <div class="mensaje"
             style="color:<?php echo $tipo_mensaje; ?>;">

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php } ?>

    <div class="card">

        <h2>Registrar Compra</h2>

        <p>
            Registre aquí las nuevas unidades que ingresan
            al inventario.
        </p>

        <form method="POST">

            <label>
                Producto
            </label>

            <select name="producto_id" required>

                <option value="">
                    Seleccione un producto
                </option>

                <?php while ($producto = mysqli_fetch_assoc($productos)) { ?>

                    <option value="<?php echo $producto['id']; ?>">

                        <?php
                        echo htmlspecialchars(
                            $producto['nombre']
                            . " | Stock actual: "
                            . $producto['stock']
                            . " "
                            . $producto['unidad']
                        );
                        ?>

                    </option>

                <?php } ?>

            </select>

            <br><br>

            <label>
                Cantidad comprada
            </label>

            <input type="number"
                   name="cantidad"
                   min="1"
                   placeholder="Cantidad que ingresa"
                   required>

            <br><br>

            <button type="submit"
                    name="registrar_compra">

                Registrar Compra

            </button>

        </form>

    </div>

</div>

</body>

</html>