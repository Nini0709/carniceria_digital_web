DROP DATABASE IF EXISTS digitalcarniceria_db;
CREATE DATABASE digitalcarniceria_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digitalcarniceria_db;

CREATE TABLE IF NOT EXISTS cliente (
  id_usuario INT NOT NULL AUTO_INCREMENT,
  nombres VARCHAR(45) NOT NULL,
  apellidos VARCHAR(45) NOT NULL,
  documento VARCHAR(20) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  correo VARCHAR(50) NOT NULL,
  direccion VARCHAR(100) NOT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE INDEX documento_UNIQUE (documento ASC)
);

CREATE TABLE IF NOT EXISTS empleado (
  id_empleado INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(45) NOT NULL,
  rol VARCHAR(45) NOT NULL,
  PRIMARY KEY (id_empleado)
);

CREATE TABLE IF NOT EXISTS categoria (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  nombre_categoria VARCHAR(45) NOT NULL,
  descripcion VARCHAR(100) NULL,
  PRIMARY KEY (id_categoria)
);

CREATE TABLE IF NOT EXISTS productos (
  id_producto INT NOT NULL AUTO_INCREMENT,
  nombre_producto VARCHAR(45) NOT NULL,
  precio_por_kilo DECIMAL(10,2) NOT NULL,
  stock_disponible DECIMAL(10,3) NOT NULL,
  id_categoria INT NOT NULL,
  PRIMARY KEY (id_producto),
  INDEX fk_productos_categoria_idx (id_categoria ASC),
  CONSTRAINT fk_productos_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES categoria (id_categoria)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS metodo_pago (
  id_metodo_pago INT NOT NULL AUTO_INCREMENT,
  nombre_metodo_pago VARCHAR(45) NOT NULL,
  PRIMARY KEY (id_metodo_pago)
);

CREATE TABLE IF NOT EXISTS venta (
  id_venta INT NOT NULL AUTO_INCREMENT,
  fecha DATETIME NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  cliente_id_usuario INT NOT NULL,
  empleado_id_empleado INT NOT NULL,
  metodo_pago_id_metodo_pago INT NOT NULL,
  PRIMARY KEY (id_venta),
  INDEX fk_venta_cliente_idx (cliente_id_usuario ASC),
  INDEX fk_venta_empleado_idx (empleado_id_empleado ASC),
  INDEX fk_venta_metodo_pago_idx (metodo_pago_id_metodo_pago ASC),
  CONSTRAINT fk_venta_cliente
    FOREIGN KEY (cliente_id_usuario)
    REFERENCES cliente (id_usuario)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_venta_empleado
    FOREIGN KEY (empleado_id_empleado)
    REFERENCES empleado (id_empleado)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_venta_metodo_pago
    FOREIGN KEY (metodo_pago_id_metodo_pago)
    REFERENCES metodo_pago (id_metodo_pago)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS detalle_venta (
  id_detalle_venta INT NOT NULL AUTO_INCREMENT,
  cantidad_kilos DECIMAL(10,3) NOT NULL,
  precio_subtotal DECIMAL(10,2) NOT NULL,
  venta_id_venta INT NOT NULL,
  productos_id_producto INT NOT NULL,
  PRIMARY KEY (id_detalle_venta),
  INDEX fk_detalle_venta_venta_idx (venta_id_venta ASC),
  INDEX fk_detalle_venta_productos_idx (productos_id_producto ASC),
  CONSTRAINT fk_detalle_venta_venta
    FOREIGN KEY (venta_id_venta)
    REFERENCES venta (id_venta)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_detalle_venta_productos
    FOREIGN KEY (productos_id_producto)
    REFERENCES productos (id_producto)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS pedidos (
  id_pedido INT NOT NULL AUTO_INCREMENT,
  fecha_pedido DATETIME NOT NULL,
  cliente_id_usuario INT NOT NULL,
  PRIMARY KEY (id_pedido),
  INDEX fk_pedidos_cliente_idx (cliente_id_usuario ASC),
  CONSTRAINT fk_pedidos_cliente
    FOREIGN KEY (cliente_id_usuario)
    REFERENCES cliente (id_usuario)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS detalle_pedido (
  id_detalle INT NOT NULL AUTO_INCREMENT,
  cantidad_kg_productos DECIMAL(10,3) NOT NULL,
  pedidos_id_pedido INT NOT NULL,
  productos_id_producto INT NOT NULL,
  PRIMARY KEY (id_detalle),
  INDEX fk_detalle_pedido_pedidos_idx (pedidos_id_pedido ASC),
  INDEX fk_detalle_pedido_productos_idx (productos_id_producto ASC),
  CONSTRAINT fk_detalle_pedido_pedidos
    FOREIGN KEY (pedidos_id_pedido)
    REFERENCES pedidos (id_pedido)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT fk_detalle_pedido_productos
    FOREIGN KEY (productos_id_producto)
    REFERENCES productos (id_producto)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS pesaje_bascula (
  id_pesaje INT NOT NULL AUTO_INCREMENT,
  peso_bruto DECIMAL(10,3) NOT NULL,
  tara DECIMAL(10,3) NOT NULL,
  peso_neto DECIMAL(10,3) GENERATED ALWAYS AS (peso_bruto - tara) VIRTUAL,
  empleado_id_empleado INT NOT NULL,
  PRIMARY KEY (id_pesaje),
  INDEX fk_pesaje_bascula_empleado_idx (empleado_id_empleado ASC),
  CONSTRAINT fk_pesaje_bascula_empleado
    FOREIGN KEY (empleado_id_empleado)
    REFERENCES empleado (id_empleado)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);