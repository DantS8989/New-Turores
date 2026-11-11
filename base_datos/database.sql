
-- CREATE DATABASE IF NOT EXISTS tutores_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE tutores_online;

-- 1. Usuarios (estudiantes, tutores y administradores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,                  -- hash de password_hash(), nunca texto plano
    rol ENUM('student', 'tutor', 'admin') NOT NULL DEFAULT 'student',
    role_label VARCHAR(50) NOT NULL DEFAULT 'Estudiante',
    specialty VARCHAR(255) NOT NULL DEFAULT '',
    bio VARCHAR(500) NOT NULL DEFAULT '',            -- descripción del perfil
    tarifa_hora DECIMAL(10, 2) NULL,                 -- precio por hora (solo tutores)
    foto VARCHAR(40) NULL,                           -- nombre del archivo en subidas/fotos/
    whatsapp VARCHAR(20) NULL,                       -- contacto del tutor (solo se ve con sesión)
    email_contacto VARCHAR(150) NULL,                -- contacto del tutor (solo se ve con sesión)
    remember_token_hash VARCHAR(255) NULL,           -- hash SHA-256 del token "Recordarme"
    remember_expires INT UNSIGNED NULL,
    created_at DATETIME NULL,
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Intentos fallidos de inicio de sesión (límite anti fuerza bruta)
CREATE TABLE IF NOT EXISTS intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    intentado_en DATETIME NOT NULL,
    INDEX idx_intentos_email (email, intentado_en),
    INDEX idx_intentos_ip (ip, intentado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Mensajes entre usuarios (estudiante ↔ tutor)
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    cuerpo VARCHAR(1000) NOT NULL,
    creado_en DATETIME NOT NULL,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_mensajes_destino (destinatario_id, creado_en),
    INDEX idx_mensajes_origen (remitente_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Materias (diseño para una siguiente fase del proyecto)
CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tutores y materias que dictan, con su tarifa por hora
CREATE TABLE IF NOT EXISTS tutores_materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    materia_id INT NOT NULL,
    tarifa_hora DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (tutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE (tutor_id, materia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Sesiones de tutoría programadas (diseño para una siguiente fase)
CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    tutor_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    duracion_minutos INT NOT NULL DEFAULT 60,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    enlace_reunion VARCHAR(255) NULL,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
