<?php

session_start();

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
