<?php
// Datos del servidor MySQL/MariaDB.
$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "";
$base_datos = "digitalcarniceria_db";
$puerto = 3308; // Cambia a 3306 si tu MySQL usa el puerto por defecto

// Crear la conexión.
$conexion = mysqli_connect(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos,
    $puerto
);

// Detener el programa si la conexión falla.
if (!$conexion) {
    die("No fue posible conectarse con la base de datos: " . mysqli_connect_error());
}

// Permitir tildes, la letra ñ y otros caracteres.
mysqli_set_charset($conexion, "utf8mb4");
?>