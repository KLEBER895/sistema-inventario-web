# Migraciones de base de datos

Cada cambio de estructura (nueva tabla, nueva columna, índice, etc.)
va en un archivo `.sql` nuevo en esta carpeta. El despliegue los aplica
automáticamente con `scripts/migrar.php`, en orden alfabético y una sola vez.

## Reglas

- Nombre: número correlativo + descripción, por ejemplo
  `002_agregar_tabla_auditoria.sql`.
- Nunca modificar una migración que ya llegó a `main`: si hay que
  corregir algo, crear una migración nueva.
- Solo cambios de estructura o datos de catálogo. Nunca datos reales
  ni contraseñas.
- Probar el archivo en la base local antes de abrir el Pull Request.
