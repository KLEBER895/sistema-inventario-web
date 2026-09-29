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

if(
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['rol'])
){

    header("Location: login.php");

    exit();
}

$rol_permitido = (
    $_SESSION['rol'] === 'Administrador' ||
    $_SESSION['rol'] === 'Empleado'
);

if(!$rol_permitido){

    header("Location: index.php");
    exit();
}

include("conexion.php");


if(isset($_POST['vender'])){

    $cliente_id =
        isset($_POST['cliente_id'])
        ? intval($_POST['cliente_id'])
        : 0;

    $producto_id =
        isset($_POST['producto_id'])
        ? intval($_POST['producto_id'])
        : 0;

    $cantidad =
        isset($_POST['cantidad'])
        ? intval($_POST['cantidad'])
        : 0;


    if(
        $cliente_id <= 0 ||
        $producto_id <= 0 ||
        $cantidad <= 0
    ){

        echo "
        <div class='mensaje'
        style='color:red;'>
            Datos de venta inválidos.
        </div>
        ";

    }else{

        $stmt_producto = mysqli_prepare(
            $conexion,
            "SELECT id, stock, precio_venta
             FROM productos
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_producto,
            "i",
            $producto_id
        );

        mysqli_stmt_execute(
            $stmt_producto
        );

        $resultado_producto =
            mysqli_stmt_get_result(
                $stmt_producto
            );

        $producto =
            mysqli_fetch_assoc(
                $resultado_producto
            );

        mysqli_stmt_close(
            $stmt_producto
        );


        if(!$producto){

            echo "
            <div class='mensaje'
            style='color:red;'>
                Producto no encontrado.
            </div>
            ";

        }else{

            $stock_actual =
                intval($producto['stock']);

            if($stock_actual >= $cantidad){

                $precio =
                    floatval(
                        $producto['precio_venta']
                    );

                $subtotal =
                    $precio * $cantidad;

                $iva =
                    $subtotal * 0.15;

                $total =
                    $subtotal + $iva;

                $nuevo_stock =
                    $stock_actual - $cantidad;


                mysqli_begin_transaction(
                    $conexion
                );

                try {

                    // 1. Descontar stock
                    $stmt_stock =
                        mysqli_prepare(
                            $conexion,
                            "UPDATE productos
                             SET stock = ?
                             WHERE id = ?"
                        );

                    mysqli_stmt_bind_param(
                        $stmt_stock,
                        "ii",
                        $nuevo_stock,
                        $producto_id
                    );

                    if(
                        !mysqli_stmt_execute(
                            $stmt_stock
                        )
                    ){

                        mysqli_stmt_close(
                            $stmt_stock
                        );

                        throw new Exception(
                            "No se pudo actualizar el stock."
                        );
                    }

                    mysqli_stmt_close(
                        $stmt_stock
                    );


                    // 2. Registrar la venta
                    $stmt_venta =
                        mysqli_prepare(
                            $conexion,
                            "INSERT INTO ventas
                            (
                                cliente_id,
                                producto_id,
                                cantidad,
                                subtotal,
                                iva,
                                total,
                                fecha
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?, ?, NOW()
                            )"
                        );

                    mysqli_stmt_bind_param(
                        $stmt_venta,
                        "iiiddd",
                        $cliente_id,
                        $producto_id,
                        $cantidad,
                        $subtotal,
                        $iva,
                        $total
                    );

                    if(
                        !mysqli_stmt_execute(
                            $stmt_venta
                        )
                    ){

                        mysqli_stmt_close(
                            $stmt_venta
                        );

                        throw new Exception(
                            "No se pudo registrar la venta."
                        );
                    }

                    $venta_id =
                        mysqli_insert_id(
                            $conexion
                        );

                    mysqli_stmt_close(
                        $stmt_venta
                    );


                    // 3. Registrar detalle de venta
                    $stmt_detalle =
                        mysqli_prepare(
                            $conexion,
                            "INSERT INTO detalle_ventas
                            (
                                venta_id,
                                producto_id,
                                cantidad,
                                precio_unitario,
                                subtotal
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?
                            )"
                        );

                    mysqli_stmt_bind_param(
                        $stmt_detalle,
                        "iiidd",
                        $venta_id,
                        $producto_id,
                        $cantidad,
                        $precio,
                        $subtotal
                    );

                    if(
                        !mysqli_stmt_execute(
                            $stmt_detalle
                        )
                    ){

                        mysqli_stmt_close(
                            $stmt_detalle
                        );

                        throw new Exception(
                            "No se pudo registrar el detalle de la venta."
                        );
                    }

                    mysqli_stmt_close(
                        $stmt_detalle
                    );


                    // 4. Confirmar toda la operación
                    mysqli_commit(
                        $conexion
                    );
                   
                    // CAMBIO: Se cambió el botón Ver Factura a color verde.
                    // MOTIVO: Diferenciar visualmente la acción disponible después
                    // de registrar correctamente la venta.
                    // FECHA: 28/09/2026

                    echo "
                    <div class='mensaje'
                    style='
                        color:#155724;
                        background:#d4edda;
                        padding:20px;
                        border-radius:8px;
                        margin-bottom:20px;
                    '>

                        <strong>
                            Venta registrada correctamente.
                        </strong>

                        <br><br>

                        Número de venta:
                        <strong>
                            #" . intval($venta_id) . "
                        </strong>

                        <br><br>
                    
                        <a
                        href='factura.php?id="
                        . intval($venta_id) .
                        "'
                        target='_blank'>

                            <button
                            type='button'
                            style='
                                background:#198754;
                                color:white;
                                padding:10px 20px;
                                border:none;
                                border-radius:5px;
                                cursor:pointer;
                                font-weight:bold;
                            '>

                                Ver Factura

                            </button>

                        </a>

                    </div>
                    ";


                } catch (Exception $e) {

                    mysqli_rollback(
                        $conexion
                    );

                    echo "
                    <div class='mensaje'
                    style='color:red;'>
                        Error:
                        " .
                        htmlspecialchars(
                            $e->getMessage()
                        )
                        . "
                    </div>
                    ";
                }


            }else{

                echo "
                <div class='mensaje'
                style='color:red;'>
                    Stock insuficiente
                </div>
                ";
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Módulo de Ventas
</title>

<link
rel="stylesheet"
href="css/estilos.css">

<style>

.contenedor{
    max-width:900px;
    margin:auto;
}

#resultadosClientes{
    display:none;
    background:white;
    border:1px solid #ccc;
    border-radius:5px;
    margin-top:5px;
    margin-bottom:15px;
    max-height:220px;
    overflow-y:auto;
}

.resultado-cliente{
    padding:12px;
    cursor:pointer;
    border-bottom:1px solid #eee;
}

.resultado-cliente:hover{
    background:#e9f2ff;
}

</style>

</head>

<body>

<header>

<h1>
Módulo de Ventas
</h1>

</header>


<?php
include("menu.php");
?>


<div class="ventas-contenedor">

<a href="clientes.php">
Registrar nuevo cliente
</a>

<br><br>


<form method="POST">


<!--
CAMBIO: Se agregó búsqueda dinámica de clientes por cédula, nombre o apellido.
MOTIVO: Mostrar automáticamente las coincidencias y permitir seleccionar
al cliente correcto sin abrir manualmente un selector.
FECHA: 28/09/2026
-->

<label>
Buscar cliente
</label>

<input
type="text"
id="buscarCliente"
placeholder="Buscar por cédula, nombres o apellidos"
autocomplete="off">


<!--
CAMBIO: El ID del cliente seleccionado se guarda en un campo oculto.
MOTIVO: Mantener la relación correcta entre la venta y el cliente seleccionado.
FECHA: 28/09/2026
-->

<input
type="hidden"
name="cliente_id"
id="clienteId"
required>


<div id="resultadosClientes">
</div>


<?php

$clientes = mysqli_query(
    $conexion,
    "SELECT id, nombre, cedula
     FROM clientes
     ORDER BY nombre ASC"
);

$lista_clientes = [];

while(
    $cliente =
    mysqli_fetch_assoc($clientes)
){

    $lista_clientes[] = [
        'id' =>
            intval(
                $cliente['id']
            ),

        'nombre' =>
            $cliente['nombre'],

        'cedula' =>
            $cliente['cedula']
    ];
}

?>


<label>
Producto
</label>

<select
name="producto_id"
required>

<option value="">
Seleccione producto
</option>


<?php

$productos =
mysqli_query(
    $conexion,
    "SELECT id, nombre
     FROM productos
     ORDER BY nombre ASC"
);

while(
    $producto =
    mysqli_fetch_assoc($productos)
){

?>

<option
value="<?php
echo intval(
    $producto['id']
);
?>">

<?php
echo htmlspecialchars(
    $producto['nombre']
);
?>

</option>

<?php
}
?>

</select>


<label>
Cantidad
</label>

<input
type="number"
name="cantidad"
min="1"
placeholder="Ingrese cantidad"
required>


<label>
Unidad
</label>

<select name="unidad">

<option value="Unidades">
Unidades
</option>

<option value="Libras">
Libras
</option>

<option value="Litros">
Litros
</option>

<option value="Kg">
Kg
</option>

</select>


<!--
CAMBIO: Se mantiene únicamente el botón para registrar la venta.
MOTIVO: La factura debe mostrarse solo después de que la venta exista.
FECHA: 28/09/2026
-->

<button
type="submit"
name="vender">

Registrar Venta

</button>

</form>


<hr>


<h3>
Productos Disponibles
</h3>


<div style="overflow-x:auto;">

<table>

<tr>

<th>
Código
</th>

<th>
Producto
</th>

<th>
Precio
</th>

<th>
Stock
</th>

</tr>


<?php

$lista =
mysqli_query(
    $conexion,
    "SELECT
        codigo,
        nombre,
        precio_venta,
        stock
     FROM productos
     ORDER BY nombre ASC"
);

while(
    $p =
    mysqli_fetch_assoc($lista)
){

?>

<tr>

<td>
<?php
echo htmlspecialchars(
    $p['codigo']
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $p['nombre']
);
?>
</td>

<td>
$
<?php
echo number_format(
    $p['precio_venta'],
    2
);
?>
</td>

<td>
<?php
echo intval(
    $p['stock']
);
?>
</td>

</tr>

<?php
}
?>

</table>

</div>

</div>


<script>

/*
CAMBIO: Se implementó autocompletado visual de clientes.
MOTIVO: Mostrar inmediatamente todas las coincidencias
por cédula, nombre o apellido mientras el usuario escribe.
FECHA: 28/09/2026
*/

const buscarCliente =
document.getElementById(
    "buscarCliente"
);

const clienteId =
document.getElementById(
    "clienteId"
);

const resultadosClientes =
document.getElementById(
    "resultadosClientes"
);

const clientes =
<?php
echo json_encode(
    $lista_clientes,
    JSON_UNESCAPED_UNICODE
);
?>;


buscarCliente.addEventListener(
"input",
function(){

    const busqueda =
    buscarCliente.value
    .toLowerCase()
    .trim();

    clienteId.value = "";

    resultadosClientes.innerHTML = "";

    if(busqueda === ""){

        resultadosClientes.style.display =
        "none";

        return;
    }


    const coincidencias =
    clientes.filter(
    function(cliente){

        const texto =
        (
            cliente.nombre +
            " " +
            cliente.cedula
        )
        .toLowerCase();

        return texto.includes(
            busqueda
        );

    });


    if(
        coincidencias.length === 0
    ){

        resultadosClientes.innerHTML =
        "<div class='resultado-cliente'>"
        +
        "No se encontraron clientes"
        +
        "</div>";

        resultadosClientes.style.display =
        "block";

        return;
    }


    coincidencias.forEach(
    function(cliente){

        const opcion =
        document.createElement(
            "div"
        );

        opcion.className =
            "resultado-cliente";

        opcion.textContent =
            cliente.nombre
            +
            " - "
            +
            cliente.cedula;


        opcion.addEventListener(
        "click",
        function(){

            buscarCliente.value =
                cliente.nombre
                +
                " - "
                +
                cliente.cedula;

            clienteId.value =
                cliente.id;

            resultadosClientes.style.display =
                "none";

        });


        resultadosClientes.appendChild(
            opcion
        );

    });


    resultadosClientes.style.display =
        "block";

});

</script>

</body>

</html>