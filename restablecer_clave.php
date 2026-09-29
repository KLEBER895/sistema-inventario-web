<?php

/*
CAMBIO:
Se implementó el restablecimiento seguro de contraseña
mediante token temporal recibido por correo electrónico.

MOTIVO:
Permitir que el usuario establezca una nueva contraseña
solo si presenta un token válido, no utilizado y no expirado.

SEGURIDAD:
- El token recibido nunca se busca directamente en la BD.
- Se calcula SHA-256 y se compara con token_hash.
- Se verifica que usado_en sea NULL.
- Se verifica que expira_en sea mayor que NOW().
- La nueva contraseña se almacena con password_hash().
- Se utilizan consultas preparadas.
- El cambio de contraseña y el consumo del token
  se realizan dentro de una transacción.

FECHA: 28/09/2026
*/

require_once __DIR__ . '/conexion.php';


$mensaje = '';
$tipo_mensaje = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

$token_valido = false;
$usuario_id = null;


/*
1. Validar que exista un token en la URL.
*/
if ($token !== '') {

    /*
    El token verdadero no está almacenado en la base de datos.

    Calculamos SHA-256 del token recibido para buscar
    únicamente su hash.
    */
    $token_hash = hash(
        'sha256',
        $token
    );


    /*
    2. Buscar token válido.

    Debe cumplir:
    - existir,
    - no haber sido usado,
    - no haber expirado.
    */
    $stmt_token = mysqli_prepare(
        $conexion,
        "SELECT
            rc.id,
            rc.usuario_id,
            rc.expira_en,
            u.nombre,
            u.email
         FROM recuperacion_claves rc
         INNER JOIN usuarios u
            ON u.id = rc.usuario_id
         WHERE rc.token_hash = ?
           AND rc.usado_en IS NULL
           AND rc.expira_en > NOW()
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt_token,
        "s",
        $token_hash
    );

    mysqli_stmt_execute(
        $stmt_token
    );

    $resultado_token =
        mysqli_stmt_get_result(
            $stmt_token
        );

    $datos_token =
        mysqli_fetch_assoc(
            $resultado_token
        );

    mysqli_stmt_close(
        $stmt_token
    );


    if ($datos_token) {

        $token_valido = true;

        $usuario_id =
            (int)$datos_token['usuario_id'];

    } else {

        $mensaje =
            'El enlace de recuperación no es válido, '
            . 'ya fue utilizado o ha expirado.';

        $tipo_mensaje = 'error';
    }

} else {

    $mensaje =
        'No se recibió un token de recuperación válido.';

    $tipo_mensaje = 'error';
}


/*
3. Procesar nueva contraseña.
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    $token_valido
) {

    $clave_nueva =
        $_POST['clave_nueva'] ?? '';

    $clave_confirmar =
        $_POST['clave_confirmar'] ?? '';


    /*
    Validación básica de contraseña.
    Para esta implementación exigimos mínimo 8 caracteres.
    */
    if (strlen($clave_nueva) < 8) {

        $mensaje =
            'La nueva contraseña debe tener '
            . 'al menos 8 caracteres.';

        $tipo_mensaje = 'error';

    } elseif ($clave_nueva !== $clave_confirmar) {

        $mensaje =
            'Las contraseñas no coinciden.';

        $tipo_mensaje = 'error';

    } else {

        /*
        4. Generar hash seguro de la nueva contraseña.
        */
        $clave_hash =
            password_hash(
                $clave_nueva,
                PASSWORD_DEFAULT
            );


        /*
        5. Transacción:
        actualizar contraseña + consumir token.
        */
        mysqli_begin_transaction(
            $conexion
        );


        try {

            /*
            Actualizar contraseña del usuario.
            */
            $stmt_clave = mysqli_prepare(
                $conexion,
                "UPDATE usuarios
                 SET clave = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_clave,
                "si",
                $clave_hash,
                $usuario_id
            );

            if (
                !mysqli_stmt_execute(
                    $stmt_clave
                )
            ) {

                throw new Exception(
                    'No se pudo actualizar la contraseña.'
                );
            }

            mysqli_stmt_close(
                $stmt_clave
            );


            /*
            6. Marcar como usados todos los tokens
            pendientes del usuario.

            Así el mismo enlace no puede reutilizarse.
            */
            $stmt_usado = mysqli_prepare(
                $conexion,
                "UPDATE recuperacion_claves
                 SET usado_en = NOW()
                 WHERE usuario_id = ?
                   AND usado_en IS NULL"
            );

            mysqli_stmt_bind_param(
                $stmt_usado,
                "i",
                $usuario_id
            );

            if (
                !mysqli_stmt_execute(
                    $stmt_usado
                )
            ) {

                throw new Exception(
                    'No se pudo invalidar el token.'
                );
            }

            mysqli_stmt_close(
                $stmt_usado
            );


            /*
            7. Confirmar ambas operaciones.
            */
            mysqli_commit(
                $conexion
            );


            $mensaje =
                'Contraseña actualizada correctamente. '
                . 'Ya puede iniciar sesión con su nueva contraseña.';

            $tipo_mensaje = 'exito';

            /*
            Ya no debe mostrarse nuevamente
            el formulario de cambio.
            */
            $token_valido = false;


        } catch (Exception $e) {

            mysqli_rollback(
                $conexion
            );

            /*
            No exponemos detalles técnicos al usuario.
            */
            error_log(
                'Error restableciendo contraseña: '
                . $e->getMessage()
            );

            $mensaje =
                'No fue posible actualizar la contraseña. '
                . 'Inténtelo nuevamente.';

            $tipo_mensaje = 'error';
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
content="width=device-width, initial-scale=1.0"
>

<title>
Restablecer contraseña
</title>

<link
rel="stylesheet"
href="css/estilos.css"
>

<style>

.restablecer-contenedor{
    max-width:500px;
    margin:70px auto;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 4px 15px rgba(0,0,0,0.10);
}

.restablecer-contenedor h2{
    text-align:center;
    color:#1f67c1;
}

.restablecer-contenedor p{
    line-height:1.6;
}

.restablecer-contenedor input{
    width:100%;
    padding:12px;
    margin:8px 0 15px 0;
    box-sizing:border-box;
}

.restablecer-contenedor button{
    width:100%;
    padding:12px;
    background:#198754;
    color:white;
    border:none;
    border-radius:5px;
    font-weight:bold;
    cursor:pointer;
}

.restablecer-contenedor button:hover{
    background:#157347;
}

.mensaje-exito{
    background:#d4edda;
    color:#155724;
    border-left:4px solid #198754;
    padding:12px;
    margin-bottom:20px;
    border-radius:5px;
}

.mensaje-error{
    background:#f8d7da;
    color:#842029;
    border-left:4px solid #dc3545;
    padding:12px;
    margin-bottom:20px;
    border-radius:5px;
}

.volver-login{
    display:block;
    text-align:center;
    margin-top:20px;
}

</style>

</head>

<body>

<header>

<h1>
Sistema Inventario Web
</h1>

</header>


<div class="restablecer-contenedor">

<h2>
Restablecer contraseña
</h2>


<?php if ($mensaje !== '') { ?>

<div class="<?php
echo $tipo_mensaje === 'exito'
    ? 'mensaje-exito'
    : 'mensaje-error';
?>">

<?php
echo htmlspecialchars(
    $mensaje
);
?>

</div>

<?php } ?>


<?php if ($token_valido) { ?>

<p>
Ingrese su nueva contraseña.
El enlace de recuperación tiene una
vigencia máxima de 5 minutos.
</p>


<form method="POST">

<input
type="hidden"
name="token"
value="<?php
echo htmlspecialchars(
    $token
);
?>"
>


<label for="clave_nueva">
Nueva contraseña
</label>

<input
type="password"
id="clave_nueva"
name="clave_nueva"
placeholder="Mínimo 8 caracteres"
minlength="8"
required
>


<label for="clave_confirmar">
Confirmar nueva contraseña
</label>

<input
type="password"
id="clave_confirmar"
name="clave_confirmar"
placeholder="Repita la nueva contraseña"
minlength="8"
required
>


<button type="submit">
Actualizar contraseña
</button>

</form>

<?php } ?>


<a
href="login.php"
class="volver-login"
>
Volver al inicio de sesión
</a>

</div>

</body>

</html>