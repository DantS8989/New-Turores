-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS tutores_online;
USE tutores_online;

-- 1. Tabla de Usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('student', 'tutor', 'admin') DEFAULT 'student',
    role_label VARCHAR(50) DEFAULT 'Estudiante',
    specialty VARCHAR(255) DEFAULT '',
    remember_token_hash VARCHAR(255) NULL,
    remember_expires INT NULL,
    created_at DATETIME NULL
);

-- 2. Tabla de Materias
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- 3. Tabla Intermedia: Tutores y Materias
CREATE TABLE tutores_materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    materia_id INT NOT NULL,
    tarifa_hora DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (tutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE(tutor_id, materia_id)
);

-- 4. Tabla de Sesiones (Tutorías programadas)
CREATE TABLE sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    tutor_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    duracion_minutos INT DEFAULT 60,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
    enlace_reunion VARCHAR(255) NULL,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
);

-- Datos iniciales de prueba
INSERT INTO usuarios (nombre, email, password, rol, role_label, specialty, created_at) VALUES 
('Carlos Gómez', 'estudiante@ejemplo.com', '$2y$10$1234567890123456789012345678901234567890123456789012', 'student', 'Estudiante', '', NOW()),
('Dra. Ana Martínez', 'tutor@ejemplo.com', '$2y$10$1234567890123456789012345678901234567890123456789012', 'tutor', 'Tutor', 'Matemáticas & Física', NOW()),
('Prof. Roberto Silva', 'roberto@ejemplo.com', '$2y$10$1234567890123456789012345678901234567890123456789012', 'tutor', 'Tutor', 'Programación Web', NOW());
