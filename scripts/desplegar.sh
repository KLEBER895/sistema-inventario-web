#!/usr/bin/env bash
#
# CAMBIO: 19/10/2026
# MOTIVO: Actualizar el sistema en el servidor con un solo comando.
# Lo ejecuta GitHub Actions por SSH, o manualmente:
#   bash ~/inventario/scripts/desplegar.sh
#
# CAMBIO: 21/10/2026
# MOTIVO: Verificar las variables de entorno (.env) antes de
# aplicar migraciones, para no dejar el sitio a medio actualizar.

set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Descargando cambios de main"
git fetch --quiet origin main

# Solo avanza si no hay cambios hechos a mano en el servidor.
# Si falla aquí, alguien editó archivos versionados directamente.
git merge --ff-only origin/main

echo "==> Instalando dependencias"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Verificando variables de entorno"
php scripts/verificar_entorno.php

echo "==> Aplicando migraciones de base de datos"
php scripts/migrar.php

echo "==> Despliegue completado: $(git log -1 --format='%h %s')"
