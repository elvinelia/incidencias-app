-- incidencias.sql

USE db_incidencias;

-- Usuarios (reportero / validador)
CREATE TABLE usuario (
  id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  rol           ENUM('reportero','validador') NOT NULL DEFAULT 'reportero',
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Provincias
CREATE TABLE provincia (
  id_provincia  INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Municipios
CREATE TABLE municipio (
  id_municipio  INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  provincia_id  INT NOT NULL,
  CONSTRAINT fk_mpio_prov
    FOREIGN KEY (provincia_id)
    REFERENCES provincia(id_provincia)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE (nombre, provincia_id)
) ENGINE=InnoDB;

-- Barrios
CREATE TABLE barrio (
  id_barrio     INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  municipio_id  INT NOT NULL,
  CONSTRAINT fk_barrio_mpio
    FOREIGN KEY (municipio_id)
    REFERENCES municipio(id_municipio)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE (nombre, municipio_id)
) ENGINE=InnoDB;

-- Catálogo de tipos de incidencia
CREATE TABLE tipo_incidencia (
  id_tipo       INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Incidencias
CREATE TABLE incidencia (
  id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
  fecha_ocurrida DATETIME NOT NULL,
  titulo        VARCHAR(150) NOT NULL,
  descripcion   TEXT,
  provincia_id  INT NOT NULL,
  municipio_id  INT NOT NULL,
  barrio_id     INT NOT NULL,
  latitud       DECIMAL(10,7),
  longitud      DECIMAL(10,7),
  muertos       INT DEFAULT 0,
  heridos       INT DEFAULT 0,
  perdida_rd    DECIMAL(12,2) DEFAULT 0,
  link_red      VARCHAR(255),
  foto_url      VARCHAR(255),
  reportero_id  INT NOT NULL,
  validado      BOOL DEFAULT FALSE,
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inc_prov
    FOREIGN KEY (provincia_id)
    REFERENCES provincia(id_provincia)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_inc_mpio
    FOREIGN KEY (municipio_id)
    REFERENCES municipio(id_municipio)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_inc_barrio
    FOREIGN KEY (barrio_id)
    REFERENCES barrio(id_barrio)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_inc_usuario
    FOREIGN KEY (reportero_id)
    REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_fecha (fecha_ocurrida),
  INDEX idx_reportero (reportero_id)
) ENGINE=InnoDB;

-- Relación N:M Incidencia ↔ Tipo
CREATE TABLE incidencia_tipo (
  incidencia_id INT NOT NULL,
  id_tipo       INT NOT NULL,
  PRIMARY KEY (incidencia_id, id_tipo),
  CONSTRAINT fk_it_inc
    FOREIGN KEY (incidencia_id)
    REFERENCES incidencia(id_incidencia)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_it_tipo
    FOREIGN KEY (id_tipo)
    REFERENCES tipo_incidencia(id_tipo)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Comentarios públicos
CREATE TABLE comentario (
  id_coment     INT AUTO_INCREMENT PRIMARY KEY,
  texto         TEXT NOT NULL,
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  autor_id      INT NOT NULL,
  incidencia_id INT NOT NULL,
  CONSTRAINT fk_com_usuario
    FOREIGN KEY (autor_id)
    REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_com_inc
    FOREIGN KEY (incidencia_id)
    REFERENCES incidencia(id_incidencia)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sugerencias de corrección
CREATE TABLE correccion (
  id_corr       INT AUTO_INCREMENT PRIMARY KEY,
  campo         VARCHAR(50) NOT NULL,   -- p.ej. 'muertos','provincia_id'
  valor_nuevo   VARCHAR(255) NOT NULL,
  estado        ENUM('pendiente','aprobada','rechazada') 
                   NOT NULL DEFAULT 'pendiente',
  autor_id      INT NOT NULL,
  incidencia_id INT NOT NULL,
  creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cor_usuario
    FOREIGN KEY (autor_id)
    REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cor_inc
    FOREIGN KEY (incidencia_id)
    REFERENCES incidencia(id_incidencia)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
