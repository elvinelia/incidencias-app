-- proyecto_supernatural.sql

DROP DATABASE IF EXISTS db_supernatural;
CREATE DATABASE db_supernatural
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE db_supernatural;

-- Tabla de usuarios
CREATE TABLE usuario (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  contraseña    VARCHAR(255) NOT NULL,
  rol           ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de profesiones
CREATE TABLE profesion (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Tabla de personajes
CREATE TABLE personaje (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(150) NOT NULL,
  descripcion    TEXT,
  edad           INT,
  genero         ENUM('M','F','Otro') DEFAULT 'Otro',
  profesion_id   INT       NOT NULL,
  creado_por     INT       NOT NULL,
  creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- claves foráneas
  CONSTRAINT fk_personaje_profesion
    FOREIGN KEY (profesion_id)
    REFERENCES profesion(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_personaje_usuario
    FOREIGN KEY (creado_por)
    REFERENCES usuario(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_profesion   (profesion_id),
  INDEX idx_creado_por  (creado_por)
) ENGINE=InnoDB;
