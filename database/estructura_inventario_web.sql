-- =========================================================
-- SISTEMA INVENTARIO WEB
-- ESTRUCTURA DE BASE DE DATOS COMPLEMENTARIA
-- CAMBIO: Recuperación segura de contraseña
-- MOTIVO: Documentar cambios de estructura necesarios
-- para correo, token temporal y restablecimiento de clave.
-- FECHA: 28/09/2026
-- =========================================================


-- ---------------------------------------------------------
-- 1. Agregar correo electrónico a usuarios
-- ---------------------------------------------------------

ALTER TABLE usuarios
ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER usuario;


-- ---------------------------------------------------------
-- 2. Crear tabla de recuperación de contraseñas
-- ---------------------------------------------------------

CREATE TABLE recuperacion_claves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_recuperacion_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);