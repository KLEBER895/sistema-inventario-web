<?php

/*
CAMBIO: 27/10/2026
MOTIVO: Centralizar el inicio de sesion mediante sesion.php.
Se conservan las validaciones y funciones del modulo.
*/
require __DIR__ . '/sesion.php';

if(!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])){

    header("Location: login.php");
    exit();
}

if($_SESSION['rol'] !== 'Administrador'){

    header("Location: index.php");
    exit();
}

/*
CAMBIO: 21/10/2026
MOTIVO: Los respaldos ya no están en una carpeta pública
(BACKUP_DIR puede estar fuera del sitio y respaldos/ está
bloqueada en .htaccess). La descarga pasa por este archivo,
que solo atiende a administradores.

SEGURIDAD:
- basename() evita rutas como ../../config.
- Solo se aceptan nombres de archivo .sql existentes.
*/
$config = require __DIR__ . '/configuracion.php';

$nombre = basename($_GET['archivo'] ?? '');
$ruta = $config['respaldos']['carpeta'] . DIRECTORY_SEPARATOR . $nombre;

if (!preg_match('/^[\w-]+\.sql$/', $nombre) || !is_file($ruta)) {
    http_response_code(404);
    exit('Respaldo no encontrado.');
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Content-Length: ' . filesize($ruta));

readfile($ruta);
