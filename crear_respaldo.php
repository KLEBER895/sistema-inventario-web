<?php

$base_datos = "inventario_web";

$fecha = date("Y-m-d_H-i-s");

$archivo = __DIR__ . "/respaldos/backup_" . $fecha . ".sql";

$comando = "\"C:\\xampp\\mysql\\bin\\mysqldump.exe\" -u root $base_datos > \"$archivo\"";

system($comando);

?>