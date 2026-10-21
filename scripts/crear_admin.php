<?php

/*
CAMBIO: 19/10/2026
MOTIVO: Crear el primer usuario Administrador en un servidor
nuevo, donde todavía no se puede ingresar a usuarios.php.

USO (solo desde la terminal):
  php scripts/crear_admin.php

SEGURIDAD:
- No se puede ejecutar desde el navegador.
- La contraseña se escribe sin mostrarse en pantalla
  y no queda en el historial de la terminal.
- Se almacena con password_hash().
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require __DIR__ . '/../conexion.php';


function preguntar($texto)
{
    echo $texto;
    return trim((string) fgets(STDIN));
}

function preguntar_oculto($texto)
{
    echo $texto;
    system('stty -echo');
    $valor = trim((string) fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $valor;
}


$nombre  = preguntar('Nombre completo: ');
$usuario = preguntar('Usuario: ');
$email   = preguntar('Correo: ');

if ($nombre === '' || $usuario === '') {
    fwrite(STDERR, "El nombre y el usuario son obligatorios.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Correo no válido.\n");
    exit(1);
}

$clave = preguntar_oculto('Contraseña (mínimo 8 caracteres): ');

if (strlen($clave) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

if (preguntar_oculto('Repetir contraseña: ') !== $clave) {
    fwrite(STDERR, "Las contraseñas no coinciden.\n");
    exit(1);
}


$stmt = mysqli_prepare(
    $conexion,
    "SELECT id FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ss", $usuario, $email);
mysqli_stmt_execute($stmt);

if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
    fwrite(STDERR, "Ya existe un usuario con ese nombre de usuario o correo.\n");
    exit(1);
}

mysqli_stmt_close($stmt);


$clave_hash = password_hash($clave, PASSWORD_DEFAULT);
$rol = 'Administrador';

$stmt = mysqli_prepare(
    $conexion,
    "INSERT INTO usuarios (nombre, usuario, email, clave, rol)
     VALUES (?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sssss", $nombre, $usuario, $email, $clave_hash, $rol);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo "Administrador '$usuario' creado correctamente.\n";
