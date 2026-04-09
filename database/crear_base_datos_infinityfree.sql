-- =========================================
-- SISTEMA WEB DE GESTION HOTELERA
-- SQL COMPATIBLE CON INFINITYFREE
-- =========================================
-- IMPORTANTE:
-- 1) En phpMyAdmin, primero selecciona tu base if0_xxxxxxx_...
-- 2) Luego importa este archivo
-- =========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================
-- TABLA: USUARIO
-- =========================================
CREATE TABLE IF NOT EXISTS USUARIO (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Contrasena encriptada con bcrypt',
    rol ENUM('cliente', 'recepcionista', 'administrador') NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_rol (rol)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: CLIENTE
-- =========================================
CREATE TABLE IF NOT EXISTS CLIENTE (
    cliente_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    direccion TEXT NULL,
    fecha_nacimiento DATE NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO(usuario_id) ON DELETE CASCADE,
    INDEX idx_cedula (cedula),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: EMPLEADO
-- =========================================
CREATE TABLE IF NOT EXISTS EMPLEADO (
    empleado_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    departamento VARCHAR(50) NOT NULL,
    salario DECIMAL(10,2) NOT NULL,
    fecha_contratacion DATE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO(usuario_id) ON DELETE CASCADE,
    INDEX idx_departamento (departamento),
    INDEX idx_cedula_emp (cedula)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: RECEPCIONISTA
-- =========================================
CREATE TABLE IF NOT EXISTS RECEPCIONISTA (
    recepcionista_id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNIQUE NOT NULL,
    turno ENUM('manana', 'tarde', 'noche') NOT NULL,
    area_asignada VARCHAR(50) DEFAULT 'Recepcion general',
    FOREIGN KEY (empleado_id) REFERENCES EMPLEADO(empleado_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: ADMINISTRADOR
-- =========================================
CREATE TABLE IF NOT EXISTS ADMINISTRADOR (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNIQUE NOT NULL,
    nivel_acceso ENUM('supervisor', 'gerente', 'director') NOT NULL,
    permisos_especiales TEXT NULL,
    FOREIGN KEY (empleado_id) REFERENCES EMPLEADO(empleado_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABLA: HABITACION
-- =========================================
CREATE TABLE IF NOT EXISTS HABITACION (
    habitacion_id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL UNIQUE,
    tipo ENUM('simple', 'doble', 'suite', 'presidencial') NOT NULL,
    precio_noche DECIMAL(10,2) NOT NULL,
    capacidad INT NOT NULL,
    estado ENUM('disponible', 'ocupada', 'mantenimiento', 'fuera_servicio') DEFAULT 'disponible',
    piso INT NOT NULL,
    descripcion TEXT NULL,
    amenidades TEXT NULL,
    imagen_url VARCHAR(255) NULL,
    fecha_ultima_limpieza DATETIME NULL,
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo),
    INDEX idx_numero (numero)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: RESERVA
-- =========================================
CREATE TABLE IF NOT EXISTS RESERVA (
    reserva_id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    habitacion_id INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    numero_personas INT DEFAULT 1,
    precio_total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada', 'no_show') DEFAULT 'pendiente',
    codigo_confirmacion VARCHAR(20) UNIQUE NOT NULL,
    fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_checkin DATETIME NULL,
    fecha_checkout DATETIME NULL,
    notas_especiales TEXT NULL,
    FOREIGN KEY (cliente_id) REFERENCES CLIENTE(cliente_id),
    FOREIGN KEY (habitacion_id) REFERENCES HABITACION(habitacion_id),
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_codigo (codigo_confirmacion),
    INDEX idx_cliente (cliente_id)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: SERVICIO
-- =========================================
CREATE TABLE IF NOT EXISTS SERVICIO (
    servicio_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(50) NULL,
    disponible BOOLEAN DEFAULT TRUE,
    imagen_url VARCHAR(255) NULL,
    INDEX idx_categoria (categoria),
    INDEX idx_disponible (disponible)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: RESERVA_SERVICIO
-- =========================================
CREATE TABLE IF NOT EXISTS RESERVA_SERVICIO (
    reserva_id INT NOT NULL,
    servicio_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
    PRIMARY KEY (reserva_id, servicio_id),
    FOREIGN KEY (reserva_id) REFERENCES RESERVA(reserva_id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES SERVICIO(servicio_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: PAGO
-- =========================================
CREATE TABLE IF NOT EXISTS PAGO (
    pago_id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 'otro') NOT NULL,
    referencia VARCHAR(100) NULL,
    estado ENUM('pendiente', 'completado', 'rechazado', 'reembolsado') DEFAULT 'completado',
    procesado_por INT NULL,
    FOREIGN KEY (reserva_id) REFERENCES RESERVA(reserva_id),
    INDEX idx_reserva (reserva_id),
    INDEX idx_fecha (fecha_pago),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: COMENTARIO
-- =========================================
CREATE TABLE IF NOT EXISTS COMENTARIO (
    comentario_id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    reserva_id INT NULL,
    calificacion INT,
    titulo VARCHAR(200) NULL,
    comentario TEXT NOT NULL,
    fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    aprobado BOOLEAN DEFAULT FALSE,
    respuesta TEXT NULL,
    fecha_respuesta DATETIME NULL,
    FOREIGN KEY (cliente_id) REFERENCES CLIENTE(cliente_id),
    FOREIGN KEY (reserva_id) REFERENCES RESERVA(reserva_id),
    INDEX idx_calificacion (calificacion),
    INDEX idx_aprobado (aprobado)
) ENGINE=InnoDB;

-- =========================================
-- TABLA: AUDITORIA
-- =========================================
CREATE TABLE IF NOT EXISTS AUDITORIA (
    auditoria_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    registro_id INT NULL,
    valores_anteriores TEXT NULL,
    valores_nuevos TEXT NULL,
    ip_address VARCHAR(45) NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO(usuario_id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_hora),
    INDEX idx_tabla (tabla_afectada),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB;

-- =========================================
-- TRIGGERS
-- =========================================
DROP TRIGGER IF EXISTS before_insert_reserva;
DROP TRIGGER IF EXISTS after_checkin_update;
DROP TRIGGER IF EXISTS after_checkout_update;

DELIMITER $$
CREATE TRIGGER before_insert_reserva
BEFORE INSERT ON RESERVA
FOR EACH ROW
BEGIN
    IF NEW.codigo_confirmacion IS NULL OR NEW.codigo_confirmacion = '' THEN
        SET NEW.codigo_confirmacion = CONCAT(
            'RES',
            YEAR(CURRENT_DATE()),
            LPAD(NEW.habitacion_id, 3, '0'),
            LPAD(FLOOR(RAND() * 9999), 4, '0')
        );
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER after_checkin_update
AFTER UPDATE ON RESERVA
FOR EACH ROW
BEGIN
    IF NEW.fecha_checkin IS NOT NULL AND OLD.fecha_checkin IS NULL THEN
        UPDATE HABITACION
        SET estado = 'ocupada'
        WHERE habitacion_id = NEW.habitacion_id;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER after_checkout_update
AFTER UPDATE ON RESERVA
FOR EACH ROW
BEGIN
    IF NEW.fecha_checkout IS NOT NULL AND OLD.fecha_checkout IS NULL THEN
        UPDATE HABITACION
        SET estado = 'disponible'
        WHERE habitacion_id = NEW.habitacion_id;

        UPDATE RESERVA
        SET estado = 'completada'
        WHERE reserva_id = NEW.reserva_id;
    END IF;
END$$
DELIMITER ;

-- =========================================
-- VISTAS
-- =========================================
DROP VIEW IF EXISTS v_estado_habitaciones;
DROP VIEW IF EXISTS v_reservas_completas;
DROP VIEW IF EXISTS v_ingresos_mensuales;

CREATE VIEW v_estado_habitaciones AS
SELECT
    h.habitacion_id,
    h.numero,
    h.tipo,
    h.precio_noche,
    h.capacidad,
    h.piso,
    h.estado,
    r.reserva_id,
    r.fecha_entrada,
    r.fecha_salida,
    CONCAT(c.nombre, ' ', c.apellido) AS huesped_actual
FROM HABITACION h
LEFT JOIN RESERVA r ON h.habitacion_id = r.habitacion_id
    AND r.estado = 'confirmada'
    AND CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida
LEFT JOIN CLIENTE c ON r.cliente_id = c.cliente_id;

CREATE VIEW v_reservas_completas AS
SELECT
    r.reserva_id,
    r.codigo_confirmacion,
    r.fecha_entrada,
    r.fecha_salida,
    r.numero_personas,
    r.precio_total,
    r.estado,
    r.fecha_reserva,
    CONCAT(c.nombre, ' ', c.apellido) AS nombre_cliente,
    c.email,
    c.telefono,
    h.numero AS numero_habitacion,
    h.tipo AS tipo_habitacion,
    DATEDIFF(r.fecha_salida, r.fecha_entrada) AS noches
FROM RESERVA r
JOIN CLIENTE c ON r.cliente_id = c.cliente_id
JOIN HABITACION h ON r.habitacion_id = h.habitacion_id;

CREATE VIEW v_ingresos_mensuales AS
SELECT
    YEAR(fecha_reserva) AS anio,
    MONTH(fecha_reserva) AS mes,
    COUNT(*) AS total_reservas,
    SUM(precio_total) AS ingreso_total,
    AVG(precio_total) AS ingreso_promedio
FROM RESERVA
WHERE estado IN ('confirmada', 'completada')
GROUP BY YEAR(fecha_reserva), MONTH(fecha_reserva);

-- =========================================
-- DATOS BASE
-- =========================================
INSERT INTO USUARIO (username, password, rol, activo)
VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 1),
('recepcion', '$2y$10$Q9wty/n/NXO2lH1iikB/A.QkHV5eVsi4YQn5Y2V24.95tusGNP2OW', 'recepcionista', 1)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    rol = VALUES(rol),
    activo = 1;

INSERT INTO EMPLEADO (usuario_id, nombre, apellido, cedula, departamento, salario, fecha_contratacion, telefono, email, activo)
SELECT u.usuario_id, 'Administrador', 'Sistema', '000-0000000-0', 'Administracion', 50000.00, '2026-01-01', '809-555-0000', 'admin@hotel.com', 1
FROM USUARIO u
WHERE u.username = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM EMPLEADO e WHERE e.cedula = '000-0000000-0'
  );

INSERT INTO EMPLEADO (usuario_id, nombre, apellido, cedula, departamento, salario, fecha_contratacion, telefono, email, activo)
SELECT u.usuario_id, 'Recepcion', 'Sistema', '000-0000000-1', 'Recepcion', 28000.00, '2026-01-01', '809-555-0001', 'recepcion@hotel.com', 1
FROM USUARIO u
WHERE u.username = 'recepcion'
  AND NOT EXISTS (
      SELECT 1 FROM EMPLEADO e WHERE e.cedula = '000-0000000-1'
  );

INSERT INTO ADMINISTRADOR (empleado_id, nivel_acceso)
SELECT e.empleado_id, 'director'
FROM EMPLEADO e
JOIN USUARIO u ON u.usuario_id = e.usuario_id
WHERE u.username = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM ADMINISTRADOR a WHERE a.empleado_id = e.empleado_id
  );

INSERT INTO RECEPCIONISTA (empleado_id, turno, area_asignada)
SELECT e.empleado_id, 'manana', 'Recepcion general'
FROM EMPLEADO e
JOIN USUARIO u ON u.usuario_id = e.usuario_id
WHERE u.username = 'recepcion'
  AND NOT EXISTS (
      SELECT 1 FROM RECEPCIONISTA r WHERE r.empleado_id = e.empleado_id
  );

INSERT INTO HABITACION (numero, tipo, precio_noche, capacidad, piso, estado, descripcion)
VALUES
('101', 'simple', 1500.00, 1, 1, 'disponible', 'Habitacion simple con cama individual y bano privado'),
('102', 'simple', 1500.00, 1, 1, 'disponible', 'Habitacion simple con cama individual y bano privado'),
('201', 'doble', 2500.00, 2, 2, 'disponible', 'Habitacion doble con cama matrimonial y bano privado'),
('202', 'doble', 2500.00, 2, 2, 'disponible', 'Habitacion doble con cama matrimonial y bano privado'),
('301', 'suite', 5000.00, 4, 3, 'disponible', 'Suite de lujo con sala de estar y vista panoramica'),
('302', 'suite', 5000.00, 4, 3, 'disponible', 'Suite de lujo con sala de estar y vista panoramica'),
('401', 'presidencial', 10000.00, 6, 4, 'disponible', 'Suite presidencial con todas las comodidades')
ON DUPLICATE KEY UPDATE
    tipo = VALUES(tipo),
    precio_noche = VALUES(precio_noche),
    capacidad = VALUES(capacidad),
    piso = VALUES(piso),
    estado = VALUES(estado),
    descripcion = VALUES(descripcion);

INSERT INTO SERVICIO (nombre, descripcion, precio, categoria, disponible)
VALUES
('Desayuno buffet', 'Desayuno continental con variedad de opciones', 500.00, 'restaurant', TRUE),
('Servicio a la habitacion', 'Entrega de comida a la habitacion', 300.00, 'restaurant', TRUE),
('Spa y masajes', 'Sesion de spa de 1 hora', 2000.00, 'spa', TRUE),
('Lavanderia', 'Servicio de lavado y planchado', 400.00, 'lavanderia', TRUE),
('Transporte aeropuerto', 'Traslado desde y hacia el aeropuerto', 1500.00, 'transporte', TRUE),
('Tour ciudad', 'Recorrido guiado por la ciudad', 2500.00, 'entretenimiento', TRUE)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    precio = VALUES(precio),
    categoria = VALUES(categoria),
    disponible = VALUES(disponible);

-- =========================================
-- INDICES ADICIONALES
-- =========================================
CREATE INDEX idx_reserva_fecha_estado ON RESERVA(fecha_entrada, estado);
CREATE INDEX idx_reserva_habitacion_fecha ON RESERVA(habitacion_id, fecha_entrada, fecha_salida);
CREATE INDEX idx_pago_fecha_metodo ON PAGO(fecha_pago, metodo_pago);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Base compatible con InfinityFree creada correctamente' AS Mensaje;
