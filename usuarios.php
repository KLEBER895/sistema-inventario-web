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

/*
CAMBIO:
Se mantiene la validación del rol Administrador
desde el backend.

MOTIVO:
Ocultar el módulo en el menú no es suficiente.
El servidor debe impedir que un usuario sin permisos
acceda escribiendo directamente la URL.

OWASP:
A01 - Control de acceso roto.

FECHA: 05/10/2026
*/
if ($_SESSION['rol'] !== 'Administrador') {

    header("Location: index.php");
    exit();
}

include("conexion.php");


/*
=========================================================
REGISTRO DE USUARIO
=========================================================

CAMBIO:
Se agregó el correo electrónico al registro de usuarios.

MOTIVO:
La recuperación de contraseña implementada en el sistema
necesita que cada usuario tenga previamente un correo
electrónico asociado a su cuenta.

También se valida:

- formato del correo;
- longitud mínima de contraseña;
- roles permitidos;
- usuario duplicado;
- correo duplicado.

FECHA: 05/10/2026
=========================================================
*/

if (isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $rol = $_POST['rol'] ?? '';

    $roles_permitidos = [
        'Administrador',
        'Empleado'
    ];


    /*
    CAMBIO:
    Se valida que el correo tenga un formato correcto.

    MOTIVO:
    No se debe almacenar un correo inválido porque
    posteriormente será utilizado para enviar el enlace
    de recuperación de contraseña.

    FECHA: 05/10/2026
    */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Ingrese un correo electrónico válido.";

    } elseif (strlen($clave) < 8) {

        /*
        CAMBIO:
        Se exige una contraseña mínima de 8 caracteres.

        MOTIVO:
        Evitar contraseñas excesivamente cortas.

        OWASP:
        A07 - Fallas de autenticación.

        FECHA: 05/10/2026
        */
        $error = "La contraseña debe tener al menos 8 caracteres.";

    } elseif (!in_array($rol, $roles_permitidos, true)) {

        /*
        CAMBIO:
        El rol recibido se valida contra una lista permitida.

        MOTIVO:
        No debemos confiar únicamente en el valor enviado
        por el formulario HTML.

        OWASP:
        A01 - Control de acceso roto.

        FECHA: 05/10/2026
        */
        $error = "El rol seleccionado no es válido.";

    } else {

        /*
        CAMBIO:
        Se comprueba que usuario y correo no estén registrados.

        MOTIVO:
        Evitar cuentas duplicadas y asegurar que un correo
        de recuperación pertenezca a una sola cuenta.

        FECHA: 05/10/2026
        */
        $stmt_verificar = mysqli_prepare(
            $conexion,
            "SELECT id
             FROM usuarios
             WHERE usuario = ?
             OR email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt_verificar,
            "ss",
            $usuario,
            $email
        );

        mysqli_stmt_execute($stmt_verificar);

        $resultado_verificar =
            mysqli_stmt_get_result($stmt_verificar);

        if (mysqli_num_rows($resultado_verificar) > 0) {

            $error =
                "El usuario o correo electrónico ya está registrado.";

        } else {

            /*
            CAMBIO:
            La contraseña se almacena mediante password_hash().

            MOTIVO:
            La contraseña nunca debe guardarse en texto plano.

            OWASP:
            A04 - Fallas criptográficas.
            A07 - Fallas de autenticación.

            FECHA: 05/10/2026
            */
            $clave_hash = password_hash(
                $clave,
                PASSWORD_DEFAULT
            );


            /*
            CAMBIO:
            Se agregó el campo email al INSERT.

            MOTIVO:
            Asociar el correo con la cuenta para utilizarlo
            posteriormente en la recuperación de contraseña.

            SEGURIDAD:
            Se utiliza una consulta preparada para separar
            los datos de la instrucción SQL.

            OWASP:
            A05 - Inyección.

            FECHA: 05/10/2026
            */
            $stmt = mysqli_prepare(
                $conexion,
                "INSERT INTO usuarios
                (nombre, usuario, email, clave, rol)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $nombre,
                $usuario,
                $email,
                $clave_hash,
                $rol
            );

            if (mysqli_stmt_execute($stmt)) {

                $mensaje =
                    "Usuario registrado correctamente.";

            } else {

                /*
                CAMBIO:
                Se muestra un mensaje genérico al usuario.

                MOTIVO:
                Evitar mostrar detalles internos de MySQL
                o información técnica sensible.

                OWASP:
                A10 - Manejo inadecuado de condiciones excepcionales.

                FECHA: 05/10/2026
                */
                $error =
                    "No fue posible registrar el usuario.";
            }

            mysqli_stmt_close($stmt);
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

<title>Módulo de Usuarios</title>

<link
rel="stylesheet"
href="css/estilos.css">

<style>

.usuarios-contenedor {
    max-width: 1000px;
    margin: auto;
}

.mensaje-exito {
    color: green;
    margin: 15px 0;
    font-weight: bold;
}

.mensaje-error {
    color: #b00020;
    margin: 15px 0;
    font-weight: bold;
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

<input
type="text"
name="nombre"
placeholder="Nombre completo"
required>

<input
type="text"
name="usuario"
placeholder="Usuario"
required>


<!--
CAMBIO:
Se agregó el campo correo electrónico.

MOTIVO:
El correo quedará asociado a la cuenta y será utilizado
por el módulo de recuperación de contraseña.

FECHA: 05/10/2026
-->
<input
type="email"
name="email"
placeholder="Correo electrónico"
required>


<input
type="password"
name="clave"
placeholder="Contraseña"
minlength="8"
required>


<select
name="rol"
required>

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


<button
type="submit"
name="guardar">

Guardar Usuario

</button>

</form>


<?php

if (isset($mensaje)) {

    echo
    "<div class='mensaje-exito'>"
    . htmlspecialchars($mensaje) .
    "</div>";
}

if (isset($error)) {

    echo
    "<div class='mensaje-error'>"
    . htmlspecialchars($error) .
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

<!--
CAMBIO:
Se agregó la columna Correo.

MOTIVO:
Permitir al administrador identificar qué correo
está asociado a cada cuenta.

FECHA: 05/10/2026
-->
<th>Correo</th>

<th>Rol</th>
<th>Acciones</th>

</tr>


<?php

/*
CAMBIO:
La consulta ahora recupera también el campo email.

MOTIVO:
Mostrar el correo registrado dentro del módulo
administrativo de usuarios.

FECHA: 05/10/2026
*/
$usuarios = mysqli_query(
    $conexion,
    "SELECT id, nombre, usuario, email, rol
     FROM usuarios"
);


while ($fila = mysqli_fetch_assoc($usuarios)) {

?>

<tr>

<td>
<?php
echo intval($fila['id']);
?>
</td>


<td>
<?php
echo htmlspecialchars($fila['nombre']);
?>
</td>


<td>
<?php
echo htmlspecialchars($fila['usuario']);
?>
</td>


<td>

<?php

/*
Los usuarios antiguos pueden tener email NULL.
En ese caso se muestra un mensaje entendible
en lugar de dejar la celda vacía.
*/
if (!empty($fila['email'])) {

    echo htmlspecialchars($fila['email']);

} else {

    echo "Sin correo registrado";
}

?>

</td>


<td>
<?php
echo htmlspecialchars($fila['rol']);
?>
</td>


<td>

<a
href="editar_usuario.php?id=<?php echo intval($fila['id']); ?>">

Editar

</a>

|

<a
href="resetear_clave.php?id=<?php echo intval($fila['id']); ?>"
onclick="return confirm('¿Restablecer contraseña?');">

Resetear

</a>

|

<a
href="eliminar_usuario.php?id=<?php echo intval($fila['id']); ?>"
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