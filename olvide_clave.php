<?php

/*
CAMBIO: Se implementó solicitud de recuperación de contraseña
mediante correo electrónico y token temporal.

MOTIVO:
Permitir que un usuario recupere su acceso sin que el
administrador tenga que asignarle manualmente una clave temporal.

SEGURIDAD:
- Consulta preparada para buscar el correo.
- Token generado con random_bytes().
- En la base de datos se guarda únicamente el hash SHA-256 del token.
- El token expira después de 5 minutos.
- Se utiliza un mensaje genérico para evitar enumeración de usuarios.
- Las credenciales SMTP permanecen fuera de GitHub.

FECHA: 28/09/2026
*/

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Cargar configuración SMTP privada.
// config_mail.php está protegido por .gitignore.
$config_mail = require __DIR__ . '/config_mail.php';


$mensaje = '';
$tipo_mensaje = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');


    /*
    SEGURIDAD:
    Siempre mostramos un mensaje genérico.
    Así una persona externa no puede descubrir
    qué correos existen en el sistema.
    */
    $mensaje_generico =
        'Si el correo está registrado, recibirás un enlace '
        . 'para restablecer tu contraseña.';


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Ingrese una dirección de correo válida.';
        $tipo_mensaje = 'error';

    } else {

        /*
        1. Buscar usuario mediante consulta preparada.
        */
        $stmt_usuario = mysqli_prepare(
            $conexion,
            "SELECT id, nombre, email
             FROM usuarios
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt_usuario,
            "s",
            $email
        );

        mysqli_stmt_execute(
            $stmt_usuario
        );

        $resultado_usuario =
            mysqli_stmt_get_result(
                $stmt_usuario
            );

        $usuario =
            mysqli_fetch_assoc(
                $resultado_usuario
            );

        mysqli_stmt_close(
            $stmt_usuario
        );


        /*
        Solo continuamos internamente si el correo existe.
        Hacia el usuario siempre se mantiene el mismo mensaje.
        */
        if ($usuario) {

            /*
            2. Invalidar tokens anteriores que todavía
            estuvieran pendientes para este usuario.
            */
            $stmt_invalidar = mysqli_prepare(
                $conexion,
                "UPDATE recuperacion_claves
                 SET usado_en = NOW()
                 WHERE usuario_id = ?
                 AND usado_en IS NULL"
            );

            mysqli_stmt_bind_param(
                $stmt_invalidar,
                "i",
                $usuario['id']
            );

            mysqli_stmt_execute(
                $stmt_invalidar
            );

            mysqli_stmt_close(
                $stmt_invalidar
            );


            /*
            3. Generar token criptográficamente seguro.

            random_bytes(32) genera 32 bytes aleatorios.
            bin2hex() los convierte en un token hexadecimal.
            */
            $token = bin2hex(
                random_bytes(32)
            );


            /*
            4. Guardar solamente el hash del token.

            El token real viajará por correo.
            La base de datos no almacena ese valor directamente.
            */
            $token_hash = hash(
                'sha256',
                $token
            );


            /*
            CAMBIO: Se ajustó la vigencia del token de recuperación.
            MOTIVO: El profesor indicó que 5 minutos es un tiempo adecuado
            para la recuperación de contraseña.
            FECHA: 28/09/2026
            */
            $expira_en = date(
                'Y-m-d H:i:s',
                time() + (5 * 60)
            );


            /*
            5. Guardar token en la base de datos.
            */
            $stmt_token = mysqli_prepare(
                $conexion,
                "INSERT INTO recuperacion_claves
                (
                    usuario_id,
                    token_hash,
                    expira_en
                )
                VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt_token,
                "iss",
                $usuario['id'],
                $token_hash,
                $expira_en
            );

            mysqli_stmt_execute(
                $stmt_token
            );

            mysqli_stmt_close(
                $stmt_token
            );


            /*
            6. Construir automáticamente la URL.

            Esto permite que funcione tanto en localhost
            como cuando usamos el enlace temporal de Cloudflare.
            */
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {

                $protocolo =
                    $_SERVER['HTTP_X_FORWARDED_PROTO'];

            } elseif (
                isset($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] === 'on'
            ) {

                $protocolo = 'https';

            } else {

                $protocolo = 'http';
            }


            $host =
                $_SERVER['HTTP_HOST'];


            $directorio =
                rtrim(
                    dirname($_SERVER['PHP_SELF']),
                    '/\\'
                );


            $enlace_recuperacion =
                $protocolo
                . '://'
                . $host
                . $directorio
                . '/restablecer_clave.php?token='
                . urlencode($token);


            /*
            7. Enviar correo mediante PHPMailer + Brevo.

            CAMBIO:
            Se ajustó la configuración SMTP del proceso
            de recuperación.

            MOTIVO:
            Utilizar la misma configuración que fue probada
            correctamente con PHPMailer y Brevo SMTP.

            FECHA: 28/09/2026
            */
            try {

                $mail = new PHPMailer(true);

                $mail->isSMTP();

                $mail->Host =
                    $config_mail['host'];

                $mail->SMTPAuth = true;

                $mail->Username =
                    $config_mail['username'];

                $mail->Password =
                    $config_mail['password'];

                /*
                CAMBIO:
                Se desactiva el inicio automático de STARTTLS
                en el entorno local de pruebas.

                MOTIVO:
                PHPMailer activa TLS automáticamente aunque
                SMTPSecure esté desactivado. En este entorno,
                el certificado recibido presenta un nombre
                distinto al host configurado.

                FECHA: 28/09/2026
                */
                $mail->SMTPAutoTLS = false;

                $mail->SMTPSecure = false;

                $mail->Port =
                $config_mail['port'];


                $mail->CharSet = 'UTF-8';


                $mail->setFrom(
                    $config_mail['from_email'],
                    $config_mail['from_name']
                );


                $mail->addAddress(
                    $usuario['email'],
                    $usuario['nombre']
                );


                $mail->isHTML(true);

                $mail->Subject =
                    'Recuperación de contraseña - Sistema Inventario Web';


                $mail->Body = "
                <div style=\"
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: auto;
                    padding: 20px;
                \">

                    <h2 style=\"color:#1f67c1;\">
                        Recuperación de contraseña
                    </h2>

                    <p>
                        Hola,
                        <strong>"
                        . htmlspecialchars(
                            $usuario['nombre']
                        )
                        . "</strong>.
                    </p>

                    <p>
                        Recibimos una solicitud para
                        restablecer la contraseña de tu cuenta
                        en el Sistema Inventario Web.
                    </p>

                    <p>
                        El siguiente enlace será válido
                        durante <strong>5 minutos</strong>.
                    </p>

                    <p style=\"text-align:center;\">

                        <a
                            href=\""
                            . htmlspecialchars(
                                $enlace_recuperacion
                            )
                            . "\"
                            style=\"
                                display:inline-block;
                                background:#198754;
                                color:white;
                                padding:12px 22px;
                                text-decoration:none;
                                border-radius:6px;
                                font-weight:bold;
                            \"
                        >
                            Restablecer contraseña
                        </a>

                    </p>

                    <p>
                        Si no solicitaste este cambio,
                        puedes ignorar este correo.
                    </p>

                    <hr>

                    <p style=\"
                        color:#666;
                        font-size:12px;
                    \">
                        Sistema Inventario Web
                    </p>

                </div>
                ";


                /*
                CAMBIO: Se ajustó el tiempo de vigencia del enlace
                de recuperación a 5 minutos.

                MOTIVO: Reducir la ventana de exposición del token y
                seguir la recomendación indicada por el profesor.

                FECHA: 28/09/2026
                */
                $mail->AltBody =
                    "Recuperación de contraseña\n\n"
                    . "Abre el siguiente enlace:\n"
                    . $enlace_recuperacion
                    . "\n\n"
                    . "Este enlace vence en 5 minutos.";


                $mail->send();


            } catch (Exception $e) {

                /*
                No mostramos detalles SMTP al usuario.
                Los detalles técnicos se registran
                únicamente en el log del servidor.
                */
                error_log(
                    'Error SMTP recuperación: '
                    . $mail->ErrorInfo
                );
            }
        }


        /*
        Mensaje idéntico exista o no el correo.
        Evita enumeración de usuarios.
        */
        $mensaje = $mensaje_generico;
        $tipo_mensaje = 'exito';
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
Recuperar contraseña
</title>

<link
rel="stylesheet"
href="css/estilos.css"
>

<style>

.recuperacion-contenedor{
    max-width:500px;
    margin:70px auto;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 4px 15px rgba(0,0,0,0.10);
}

.recuperacion-contenedor h2{
    text-align:center;
    color:#1f67c1;
}

.recuperacion-contenedor p{
    line-height:1.6;
}

.recuperacion-contenedor input{
    width:100%;
    padding:12px;
    margin:10px 0 15px 0;
    box-sizing:border-box;
}

.recuperacion-contenedor button{
    width:100%;
    padding:12px;
    background:#1f67c1;
    color:white;
    border:none;
    border-radius:5px;
    font-weight:bold;
    cursor:pointer;
}

.recuperacion-contenedor button:hover{
    background:#18549f;
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


<div class="recuperacion-contenedor">

<h2>
Recuperar contraseña
</h2>

<p>
Ingrese el correo electrónico asociado
a su cuenta.
</p>


<?php if ($mensaje !== '') { ?>

<div class="<?php
echo $tipo_mensaje === 'error'
    ? 'mensaje-error'
    : 'mensaje-exito';
?>">

<?php
echo htmlspecialchars($mensaje);
?>

</div>

<?php } ?>


<form method="POST">

<label for="email">
Correo electrónico
</label>

<input
type="email"
id="email"
name="email"
placeholder="Ingrese su correo electrónico"
required
>

<button type="submit">
Enviar enlace de recuperación
</button>

</form>


<a
href="login.php"
class="volver-login"
>
Volver al inicio de sesión
</a>

</div>

</body>

</html>