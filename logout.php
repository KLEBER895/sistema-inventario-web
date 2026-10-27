<?php

/*
CAMBIO: 27/10/2026
MOTIVO: Centralizar el inicio y cierre de sesiones
y mantener los atributos de seguridad de las cookies.

Se conserva la destruccion de la sesion y la
redireccion a login.php.
*/

require __DIR__ . '/sesion.php';

// Eliminar los datos almacenados en la sesion.
$_SESSION = [];

// Eliminar la cookie de sesion del navegador.
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

// Destruir la sesion del servidor.
session_destroy();

// Regresar al formulario de inicio de sesion.
header('Location: login.php');
exit;