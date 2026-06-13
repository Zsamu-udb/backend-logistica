-- ========== DB_AUTH ==========
CREATE DATABASE IF NOT EXISTS db_auth;
USE db_auth;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('administrador','logistica','operador') NOT NULL,
    token VARCHAR(255) NULL,
    sesion_activa BOOLEAN DEFAULT FALSE,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO usuarios (nombre, correo, usuario, contrasena, rol, token, sesion_activa, estado, created_at, updated_at)
VALUES
('Administrador General','admin@logitrans.com','admin','admin123','administrador',NULL,FALSE,'activo',NOW(),NOW()),
('Operador Logistico','logistica@logitrans.com','logistica','logistica123','logistica',NULL,FALSE,'activo',NOW(),NOW());


-- ========== DB_CONDUCTORES ==========
CREATE DATABASE IF NOT EXISTS db_conductores;
USE db_conductores;

CREATE TABLE conductores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    documento VARCHAR(30) NOT NULL UNIQUE,
    telefono VARCHAR(30) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    numero_licencia VARCHAR(50) NOT NULL UNIQUE,
    categoria_licencia VARCHAR(20) NOT NULL,
    fecha_vencimiento_licencia DATE NOT NULL,
    estado ENUM('disponible','en_ruta','inactivo') DEFAULT 'disponible',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO conductores (nombres, apellidos, documento, telefono, correo, numero_licencia, categoria_licencia, fecha_vencimiento_licencia, estado, created_at, updated_at)
VALUES
('Carlos','Ramirez','1000123456','3001234567','carlos@logitrans.com','LIC-1001','C2','2027-05-10','disponible',NOW(),NOW()),
('Andres','Martinez','1000789456','3014567890','andres@logitrans.com','LIC-1002','C3','2026-12-15','disponible',NOW(),NOW());


-- ========== DB_VEHICULOS ==========
CREATE DATABASE IF NOT EXISTS db_vehiculos;
USE db_vehiculos;

CREATE TABLE vehiculos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,
    tipo_vehiculo VARCHAR(100) NOT NULL,
    capacidad_carga DECIMAL(10,2) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    estado ENUM('disponible','en_ruta','mantenimiento','inactivo') DEFAULT 'disponible',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO vehiculos (placa, tipo_vehiculo, capacidad_carga, modelo, marca, estado, created_at, updated_at)
VALUES
('ABC123','Camion',5000,'2022','Chevrolet','disponible',NOW(),NOW()),
('XYZ789','Furgon',2500,'2021','Renault','disponible',NOW(),NOW());


-- ========== DB_RUTAS ==========
CREATE DATABASE IF NOT EXISTS db_rutas;
USE db_rutas;

CREATE TABLE rutas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ciudad_origen VARCHAR(100) NOT NULL,
    ciudad_destino VARCHAR(100) NOT NULL,
    distancia DECIMAL(10,2) NOT NULL,
    tiempo_estimado VARCHAR(50) NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE programaciones_viajes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conductor_id BIGINT UNSIGNED NOT NULL,
    vehiculo_id BIGINT UNSIGNED NOT NULL,
    ruta_id BIGINT UNSIGNED NOT NULL,
    fecha_salida DATE NOT NULL,
    hora_salida TIME NOT NULL,
    fecha_estimada_llegada DATE NOT NULL,
    observaciones TEXT NULL,
    estado ENUM('programado','en_transito','retrasado','finalizado','cancelado') DEFAULT 'programado',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO rutas (ciudad_origen, ciudad_destino, distancia, tiempo_estimado, observaciones, created_at, updated_at)
VALUES
('Bogota','Medellin',420,'8 horas','Ruta principal nacional',NOW(),NOW()),
('Tunja','Bogota',150,'3 horas','Ruta regional',NOW(),NOW());

INSERT INTO programaciones_viajes (conductor_id, vehiculo_id, ruta_id, fecha_salida, hora_salida, fecha_estimada_llegada, observaciones, estado, created_at, updated_at)
VALUES
(1,1,1,'2026-06-15','06:00:00','2026-06-15','Carga de alimentos','programado',NOW(),NOW());


-- ========== DB_VIAJES ==========
CREATE DATABASE IF NOT EXISTS db_viajes;
USE db_viajes;

CREATE TABLE seguimientos_viajes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    programacion_viaje_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('programado','en_transito','retrasado','finalizado','cancelado') NOT NULL,
    novedad TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO seguimientos_viajes (programacion_viaje_id, fecha, hora, estado, novedad, created_at, updated_at)
VALUES
(1,'2026-06-15','06:00:00','en_transito','Vehiculo inicia recorrido hacia Medellin',NOW(),NOW()),
(1,'2026-06-15','10:30:00','retrasado','Retraso por trafico en carretera',NOW(),NOW());
