-- ===================================================
-- Base de Datos para el Consultorio Médico (SOAP Web Service)
-- ===================================================

CREATE DATABASE IF NOT EXISTS `consultorio_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `consultorio_db`;

-- ---------------------------------------------------
-- Tabla: profesionales
-- ---------------------------------------------------
DROP TABLE IF EXISTS `citas`;
DROP TABLE IF EXISTS `profesionales`;
DROP TABLE IF EXISTS `pacientes`;

CREATE TABLE `profesionales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `documento` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `especialidad` VARCHAR(100) NOT NULL,
  `estado` ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabla: pacientes
-- ---------------------------------------------------
CREATE TABLE `pacientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `documento` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Tabla: citas
-- ---------------------------------------------------
CREATE TABLE `citas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `profesional_id` INT NOT NULL,
  `paciente_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `estado` ENUM('Programada', 'Completada', 'Cancelada') NOT NULL DEFAULT 'Programada',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `profesional_activo` INT GENERATED ALWAYS AS (IF(`estado` <> 'Cancelada', `profesional_id`, NULL)) VIRTUAL,
  CONSTRAINT `fk_cita_profesional` FOREIGN KEY (`profesional_id`) REFERENCES `profesionales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cita_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uk_franja_activa` UNIQUE (`profesional_activo`, `fecha`, `hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---------------------------------------------------
-- Datos de prueba (Seeds)
-- ---------------------------------------------------
INSERT INTO `profesionales` (`id`, `documento`, `nombre`, `especialidad`, `estado`) VALUES
(1, '1098765432', 'Dr. Carlos Mendoza', 'Cardiología', 'Activo'),
(2, '1087654321', 'Dra. Elena Rostova', 'Pediatría', 'Activo'),
(3, '1076543210', 'Dr. Roberto Gómez', 'Dermatología', 'Inactivo');

INSERT INTO `pacientes` (`id`, `documento`, `nombre`, `telefono`) VALUES
(1, '1234567890', 'Juan Pérez', '3001234567'),
(2, '2345678901', 'María Rodríguez', '3109876543'),
(3, '3456789012', 'Andrés López', '3205554433');

INSERT INTO `citas` (`id`, `profesional_id`, `paciente_id`, `fecha`, `hora`, `estado`) VALUES
(1, 1, 1, '2026-09-10', '09:00:00', 'Programada'),
(2, 2, 2, '2026-09-10', '10:30:00', 'Programada');
