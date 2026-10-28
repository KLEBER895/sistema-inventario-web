<?php

/*
CAMBIO: 27/10/2026
MOTIVO: Centralizar el inicio de sesion mediante sesion.php.
Se conservan las validaciones y funciones del modulo.
*/
require __DIR__ . '/sesion.php';

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {

    header("Location: login.php");
    exit();
}

/*
CAMBIO:
Se mantiene la validación del rol Administrador
desde el backend.

MOTIVO:
El control de acceso real debe estar en el servidor
y no depender únicamente del menú visible.

OWASP:
A01 - Control de acceso roto.

FECHA: 05/10/2026
*/
if ($_SESSION['rol'] !== 'Administrador') {

    header("Location: index.php");
    exit();
}

include("conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {

    header("Location: usuarios.php");
    exit();
}


/*
CAMBIO:
La consulta ahora recupera también el correo electrónico.

MOTIVO:
Permitir que el administrador pueda visualizar y modificar
el correo asociado a la cuenta.

FECHA: 05/10/2026
*/
$stmt = mysqli_prepare(
    $conexion,
    "SELECT id, nombre, usuario, email, clave, rol
     FROM usuarios
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);


if (!$usuario) {

    header("Location: usuarios.php");
    exit();
}


if (isset($_POST['actualizar'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $usuario_nuevo = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave_nueva = $_POST['clave'] ?? '';
    $rol = $_POST['rol'] ?? '';

    $roles_permitidos = [
        'Administrador',
        'Empleado'
    ];


    /*
    CAMBIO:
    Se valida el formato del correo electrónico.

    MOTIVO:
    El correo será utilizado para recuperación de contraseña
    y debe ser válido.

    FECHA: 05/10/2026
    */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Ingrese un correo electrónico válido.";

    } elseif (
        $clave_nueva !== '' &&
        strlen($clave_nueva) < 8
    ) {

        /*
        CAMBIO:
        Se exige mínimo 8 caracteres solo si el administrador
        decide cambiar la contraseña.

        MOTIVO:
        Si el campo queda vacío, se conserva la contraseña actual.

        OWASP:
        A07 - Fallas de autenticación.

        FECHA: 05/10/2026
        */
        $error =
            "La nueva contraseña debe tener al menos 8 caracteres.";

    } elseif (!in_array($rol, $roles_permitidos, true)) {

        $error = "El rol seleccionado no es válido.";

    } else {

        /*
        CAMBIO:
        Se valida que el usuario o correo no pertenezcan
        a otra cuenta.

        MOTIVO:
        Evitar duplicados sin bloquear los datos del usuario
        que actualmente se está editando.

        FECHA: 05/10/2026
        */
        $stmt_verificar = mysqli_prepare(
            $conexion,
            "SELECT id
             FROM usuarios
             WHERE (usuario = ? OR email = ?)
             AND id <> ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt_verificar,
            "ssi",
            $usuario_nuevo,
            $email,
            $id
        );

        mysqli_stmt_execute($stmt_verificar);

        $resultado_verificar =
            mysqli_stmt_get_result($stmt_verificar);

        if (mysqli_num_rows($resultado_verificar) > 0) {

            $error =
                "El usuario o correo electrónico ya pertenece a otra cuenta.";

        } else {

            /*
            Si se escribió una nueva contraseña,
            también se actualiza la clave.
            */
            if ($clave_nueva !== '') {

                $clave_hash = password_hash(
                    $clave_nueva,
                    PASSWORD_DEFAULT
                );

                $stmt_update = mysqli_prepare(
                    $conexion,
                    "UPDATE usuarios
                     SET nombre = ?,
                         usuario = ?,
                         email = ?,
                         clave = ?,
                         rol = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt_update,
                    "sssssi",
                    $nombre,
                    $usuario_nuevo,
                    $email,
                    $clave_hash,
                    $rol,
                    $id
                );

            } else {

                /*
                CAMBIO:
                Si no se escribe una nueva contraseña,
                la contraseña existente no se modifica.

                MOTIVO:
                Permitir actualizar nombre, usuario, correo
                o rol sin alterar innecesariamente la clave.

                FECHA: 05/10/2026
                */
                $stmt_update = mysqli_prepare(
                    $conexion,
                    "UPDATE usuarios
                     SET nombre = ?,
                         usuario = ?,
                         email = ?,
                         rol = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt_update,
                    "ssssi",
                    $nombre,
                    $usuario_nuevo,
                    $email,
                    $rol,
                    $id
                );
            }


            if (mysqli_stmt_execute($stmt_update)) {

                mysqli_stmt_close($stmt_update);
                mysqli_stmt_close($stmt_verificar);

                header("Location: usuarios.php");
                exit();

            } else {

                $error =
                    "No fue posible actualizar el usuario.";

                mysqli_stmt_close($stmt_update);
            }
        }

        mysqli_stmt_close($stmt_verificar);
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

<title>Editar Usuario</title>

<link
rel="stylesheet"
href="css/estilos.css">

<style>

.mensaje-error {
    color: #b00020;
    font-weight: bold;
    margin-bottom: 15px;
}

</style>

</head>

<body>

<header>

<h1>Editar Usuario</h1>

</header>

<?php include("menu.php"); ?>

<div class="contenedor">

<div class="card">

<?php

if (isset($error)) {

    echo
    "<div class='mensaje-error'>"
    . htmlspecialchars($error) .
    "</div>";
}

?>

<form method="POST">

<input
type="text"
name="nombre"
value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
required>


<input
type="text"
name="usuario"
value="<?php echo htmlspecialchars($usuario['usuario']); ?>"
required>


<!--
CAMBIO:
Se agregó el campo correo electrónico
al formulario de edición.

MOTIVO:
El administrador debe poder asociar o modificar
el correo utilizado para recuperación de contraseña.

FECHA: 05/10/2026
-->
<input
type="email"
name="email"
value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>"
placeholder="Correo electrónico"
required>


<input
type="password"
name="clave"
placeholder="Nueva contraseña (opcional)"
minlength="8">


<select
name="rol"
required>

<option
value="Administrador"
<?php
if ($usuario['rol'] === "Administrador") {
    echo "selected";
}
?>>

Administrador

</option>


<option
value="Empleado"
<?php
if ($usuario['rol'] === "Empleado") {
    echo "selected";
}
?>>

Empleado

</option>

</select>


<button
type="submit"
name="actualizar">

Actualizar Usuario

</button>

</form>

</div>

</div>

</body>

</html>