-- ==========================================================
-- database.sql — Estructura de la base de datos
-- Proyecto: Sistema de gestión de usuarios · Adopción de perros
-- Motor: MySQL / MariaDB (XAMPP) · Importar desde phpMyAdmin > SQL
-- ==========================================================

CREATE DATABASE IF NOT EXISTS sistema_usuarios
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_usuarios;

-- ----------------------------------------------------------
-- Tabla usuarios
--  - dni y email son UNIQUE: la base impide duplicados aunque
--    falle la validación de PHP.
--  - password_hash guarda el resultado de password_hash()
--    (nunca la contraseña en texto plano). 255 caracteres
--    alcanzan para cualquier algoritmo que use PHP.
--  - rol solo admite los valores 'usuario' o 'admin'.
--  - archivado_en implementa el borrado lógico:
--      NULL  = usuario activo
--      fecha = usuario archivado (se conserva el historial)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(80)   NOT NULL,
    apellido      VARCHAR(80)   NOT NULL,
    dni           VARCHAR(20)   NOT NULL UNIQUE,
    email         VARCHAR(254)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    rol           ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    creado_en     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archivado_en  DATETIME      NULL DEFAULT NULL,
    INDEX idx_archivado_en (archivado_en)
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Administrador inicial (para poder entrar la primera vez)
--   Correo:     admin@huellitas.test
--   Contraseña: Admin1234
-- ¡Cambiar la contraseña después del primer ingreso!
-- ----------------------------------------------------------
INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
VALUES (
    'Admin',
    'Huellitas',
    '10000000',
    'admin@huellitas.test',
    '$2y$10$anykBY3hVb0X/Kt83RmHfenpRioCGSIHfVOzwuNedHpVCqmf5W.qG',
    'admin'
);

-- ----------------------------------------------------------
-- Consultas útiles para comprobar en phpMyAdmin
-- ----------------------------------------------------------
-- SELECT id, nombre, apellido, dni, email, rol, creado_en, archivado_en
-- FROM usuarios ORDER BY id DESC;
