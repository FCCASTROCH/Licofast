
-- LicoFast · Base de datos completa
-- Roles: administrador, cajero, cliente, repartidor (delivery)

CREATE DATABASE IF NOT EXISTS licofast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE licofast;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(80) NOT NULL,
 apellido VARCHAR(80) NOT NULL,
 ci VARCHAR(30) NOT NULL UNIQUE,
 fecha_nacimiento DATE NULL,
 edad INT NOT NULL DEFAULT 18,
 usuario VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 rol ENUM('cliente','administrador','cajero','repartidor','empleado') NOT NULL DEFAULT 'cliente',
 cargo VARCHAR(100) NULL,
 telefono VARCHAR(30) NULL,
 acepta_terminos TINYINT(1) NOT NULL DEFAULT 0,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS productos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 descripcion TEXT,
 precio DECIMAL(10,2) NOT NULL DEFAULT 0,
 stock INT NOT NULL DEFAULT 0,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ventas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NULL,                -- cliente que compra
 cajero_id INT NULL,                 -- cajero que registro la venta en mostrador
 repartidor_id INT NULL,             -- repartidor que realizo la entrega
 tipo_venta ENUM('mostrador','delivery') NOT NULL DEFAULT 'delivery',
 total DECIMAL(10,2) NOT NULL DEFAULT 0,
 monto_cobrado DECIMAL(10,2) NOT NULL DEFAULT 0,
 estado ENUM('pendiente','pagada','en_camino','entregada','no_entregado') NOT NULL DEFAULT 'pendiente',
 estado_pago ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
 direccion_entrega VARCHAR(255) NULL,
 referencia_entrega VARCHAR(255) NULL,
 telefono_contacto VARCHAR(30) NULL,
 observacion_entrega VARCHAR(255) NULL,
 fecha_entrega DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
 FOREIGN KEY (cajero_id) REFERENCES usuarios(id) ON DELETE SET NULL,
 FOREIGN KEY (repartidor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS detalle_venta (
 id INT AUTO_INCREMENT PRIMARY KEY,
 venta_id INT NOT NULL,
 producto_id INT NOT NULL,
 cantidad INT NOT NULL,
 precio_unitario DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
 FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
);

INSERT IGNORE INTO productos (id,nombre,descripcion,precio,stock,activo) VALUES
(1,'Ron','Ron anejo botella 750 ml',45.00,20,1),
(2,'Vodka','Vodka premium botella 700 ml',55.00,20,1),
(3,'Whisky','Whisky 12 anos botella 750 ml',90.00,15,1),
(4,'Cerveza (six pack)','Six pack de cerveza nacional 330 ml',36.00,40,1),
(5,'Vino tinto','Vino tinto reserva 750 ml',60.00,18,1);

-- =====================================================================
-- Usuarios de prueba. Clave de TODOS: Admin1234
-- Formato PBKDF2-SHA256 (compatible PHP / Java / Python)
-- =====================================================================
INSERT IGNORE INTO usuarios (id,nombre,apellido,ci,fecha_nacimiento,edad,usuario,password,rol,cargo,telefono,acepta_terminos,activo) VALUES
(1,'Administrador','LicoFast','API-ADMIN','2000-01-01',26,'admin_api','PBKDF2-SHA256$120000$YWRtaW5fYXBpX3NhbHQ=$z72u4rrZIKlp2VK/9Rjsaex1FNxcI4KHM0lv8rWLn6s=','administrador','Administrador general','70000001',1,1),
(2,'Carla','Perez','CI-CAJERO','1998-05-12',28,'cajero1','PBKDF2-SHA256$120000$YWRtaW5fYXBpX3NhbHQ=$z72u4rrZIKlp2VK/9Rjsaex1FNxcI4KHM0lv8rWLn6s=','cajero','Cajero de mostrador','70000002',1,1),
(3,'Luis','Mamani','CI-REPARTO','1997-09-03',29,'repartidor1','PBKDF2-SHA256$120000$YWRtaW5fYXBpX3NhbHQ=$z72u4rrZIKlp2VK/9Rjsaex1FNxcI4KHM0lv8rWLn6s=','repartidor','Delivery / repartidor','70000003',1,1),
(4,'Ana','Flores','CI-CLIENTE','1999-02-20',27,'cliente1','PBKDF2-SHA256$120000$YWRtaW5fYXBpX3NhbHQ=$z72u4rrZIKlp2VK/9Rjsaex1FNxcI4KHM0lv8rWLn6s=','cliente','Cliente','70000004',1,1);
