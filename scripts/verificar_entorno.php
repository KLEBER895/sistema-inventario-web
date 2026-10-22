<?php

/*
CAMBIO: 21/10/2026
MOTIVO: Detener el despliegue antes de tocar la base de datos
si al servidor le falta alguna variable de entorno o si la
configuración no es apta para producción.

USO (solo desde la terminal):
  php scripts/verificar_entorno.php

Termina con código 1 y lista los problemas encontrados.
Nunca imprime contraseñas.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require __DIR__ . '/../configuracion.php';


$obligatorias = [
    'APP_ENV', 'APP_URL',
    'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
    'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS',
    'BACKUP_DIR',
];

$problemas = [];

foreach ($obligatorias as $clave) {
    if (entorno($clave) === null) {
        $problemas[] = "Falta $clave";
    }
}

if ($config['app']['entorno'] !== 'produccion') {
    $problemas[] = 'APP_ENV debe ser "produccion" en el servidor';
}

if (entorno_bool('APP_DEBUG')) {
    $problemas[] = 'APP_DEBUG debe ser false en producción';
}

if ($config['app']['url'] !== '' && strpos($config['app']['url'], 'https://') !== 0) {
    $problemas[] = 'APP_URL debe empezar con https://';
}

if (!in_array($config['mail']['encryption'], ['tls', 'ssl'], true)) {
    $problemas[] = 'MAIL_ENCRYPTION debe ser tls o ssl en producción';
}

$raiz = realpath(__DIR__ . '/..');
$respaldos = $config['respaldos']['carpeta'];

if (strpos(realpath($respaldos) ?: $respaldos, $raiz) === 0) {
    $problemas[] = 'BACKUP_DIR debe estar fuera de la carpeta del proyecto';
}


if ($problemas) {
    fwrite(STDERR, "Configuración de entorno incompleta:\n");
    foreach ($problemas as $problema) {
        fwrite(STDERR, "  - $problema\n");
    }
    fwrite(STDERR, "Revise el archivo .env del servidor (plantilla: .env.example).\n");
    exit(1);
}

echo "Variables de entorno correctas.\n";
