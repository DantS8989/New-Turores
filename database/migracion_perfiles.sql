-- ============================================================
-- Migración: perfiles, precios, foto, contacto y mensajes
--
-- Úsala SOLO si ya importaste la versión anterior de database.sql.
-- (Si tu base está vacía, importa directamente database.sql, que ya incluye esto.)
-- Importa este archivo con phpMyAdmin → tu base de datos → Importar.
-- Si sale "Duplicate column name", esa columna ya existe: no pasa nada.
-- ============================================================

ALTER TABLE usuarios ADD COLUMN bio VARCHAR(500) NOT NULL DEFAULT '' AFTER specialty;
ALTER TABLE usuarios ADD COLUMN tarifa_hora DECIMAL(10, 2) NULL AFTER bio;
ALTER TABLE usuarios ADD COLUMN foto VARCHAR(40) NULL AFTER tarifa_hora;
ALTER TABLE usuarios ADD COLUMN whatsapp VARCHAR(20) NULL AFTER foto;
ALTER TABLE usuarios ADD COLUMN email_contacto VARCHAR(150) NULL AFTER whatsapp;

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
