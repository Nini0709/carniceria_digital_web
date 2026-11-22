<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    $nombres     = trim($_POST['nombres'] ?? '');
    $apellidos   = trim($_POST['apellidos'] ?? '');
    $documento   = trim($_POST['documento'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $correo      = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
    $direccion   = trim($_POST['direccion'] ?? '');
    $cantidad_kg = filter_input(INPUT_POST, 'cantidad_kg', FILTER_VALIDATE_FLOAT);
    $acepta      = filter_input(INPUT_POST, 'acepta', FILTER_VALIDATE_INT);

    if (!$id_producto || empty($nombres) || empty($apellidos) || empty($documento) || empty($telefono) || !$correo || empty($direccion) || !$cantidad_kg || $cantidad_kg <= 0 || $acepta !== 1) {
        header("Location: comprar.php?error=datos_invalidos");
        exit;
    }

    // 1. Consultar producto y stock en digitalcarniceria_db
    $sql_prod = "SELECT nombre_producto, precio_por_kilo, stock_disponible FROM productos WHERE id_producto = ?";
    $stmt_prod = mysqli_prepare($conexion, $sql_prod);
    mysqli_stmt_bind_param($stmt_prod, "i", $id_producto);
    mysqli_stmt_execute($stmt_prod);
    $producto_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_prod));

    if (!$producto_db || $cantidad_kg > $producto_db['stock_disponible']) {
        header("Location: comprar.php?error=stock_insuficiente");
        exit;
    }

    // 2. Buscar o registrar cliente por documento
    $sql_cli = "SELECT id_usuario FROM cliente WHERE documento = ?";
    $stmt_cli = mysqli_prepare($conexion, $sql_cli);
    mysqli_stmt_bind_param($stmt_cli, "s", $documento);
    mysqli_stmt_execute($stmt_cli);
    $cliente_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cli));

    if ($cliente_db) {
        $id_usuario = $cliente_db['id_usuario'];
    } else {
        $sql_ins_cli = "INSERT INTO cliente (nombres, apellidos, documento, telefono, correo, direccion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_ins_cli = mysqli_prepare($conexion, $sql_ins_cli);
        mysqli_stmt_bind_param($stmt_ins_cli, "ssssss", $nombres, $apellidos, $documento, $telefono, $correo, $direccion);
        mysqli_stmt_execute($stmt_ins_cli);
        $id_usuario = mysqli_insert_id($conexion);
    }

    // 3. Registrar pedido en `pedidos`
    $sql_ped = "INSERT INTO pedidos (fecha_pedido, cliente_id_usuario) VALUES (NOW(), ?)";
    $stmt_ped = mysqli_prepare($conexion, $sql_ped);
    mysqli_stmt_bind_param($stmt_ped, "i", $id_usuario);
    mysqli_stmt_execute($stmt_ped);
    $id_pedido = mysqli_insert_id($conexion);

    // 4. Registrar detalle en `detalle_pedido`
    $sql_det = "INSERT INTO detalle_pedido (cantidad_kg_productos, pedidos_id_pedido, productos_id_producto) VALUES (?, ?, ?)";
    $stmt_det = mysqli_prepare($conexion, $sql_det);
    mysqli_stmt_bind_param($stmt_det, "dii", $cantidad_kg, $id_pedido, $id_producto);
    mysqli_stmt_execute($stmt_det);

    // 5. Actualizar stock disponible
    $sql_stock = "UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id_producto = ?";
    $stmt_stock = mysqli_prepare($conexion, $sql_stock);
    mysqli_stmt_bind_param($stmt_stock, "di", $cantidad_kg, $id_producto);
    mysqli_stmt_execute($stmt_stock);

    header("Location: confirmacion.php?exito=1&pedido=" . $id_pedido);
    exit; 
} else {
    header("Location: comprar.php");
    exit;
}
?>