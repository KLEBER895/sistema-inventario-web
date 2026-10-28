<?php

/*
CAMBIO: 27/10/2026
MOTIVO: Centralizar el inicio de sesiones y reforzar
la seguridad de las cookies del Sistema Inventario Web.

SEGURIDAD:
- HttpOnly: impide el acceso directo desde JavaScript.
- SameSite=Lax: reduce riesgos en solicitudes entre sitios.
- Secure: obligatorio en produccion y conexiones HTTPS.

Se conserva el nombre de sesion y la compatibilidad
con los modulos existentes.
*/

$config = require __DIR__ . '/configuracion.php';

if (session_status() === PHP_SESSION_NONE) {

    // En produccion exigimos cookies seguras.
    $es_produccion = $config['app']['entorno'] === 'produccion';

    // Detectar una conexion HTTPS directa.
    $usa_https = isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $es_produccion || $usa_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}