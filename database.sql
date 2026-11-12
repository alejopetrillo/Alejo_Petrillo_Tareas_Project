-

CREATE DATABASE IF NOT EXISTS sistema_usuarios
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_usuarios;


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


INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
VALUES (
    'Admin',
    'Huellitas',
    '10000000',
    'admin@huellitas.test',
    '$2y$10$anykBY3hVb0X/Kt83RmHfenpRioCGSIHfVOzwuNedHpVCqmf5W.qG',
    'admin'
);

-