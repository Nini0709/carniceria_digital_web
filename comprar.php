<?php
require_once "conexion.php";

// Si viene desde registro.html, precargamos los datos del cliente
$nombres_pre   = trim($_POST['nombres'] ?? '');
$apellidos_pre = trim($_POST['apellidos'] ?? '');
$documento_pre = trim($_POST['documento'] ?? '');
$telefono_pre  = trim($_POST['telefono'] ?? '');
$correo_pre    = trim($_POST['correo'] ?? '');
$direccion_pre = trim($_POST['direccion'] ?? '');
$mensaje_error = "";

// Procesar la compra cuando se envía el formulario de pedido
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirmar_pedido'])) {

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
        $mensaje_error = "Por favor completa todos los campos obligatorios y acepta los términos.";
    } else {
        // 1. Verificar producto, precio_por_kilo y stock en digitalcarniceria_db
        $sql_prod = "SELECT nombre_producto, precio_por_kilo, stock_disponible FROM productos WHERE id_producto = ?";
        $stmt_prod = mysqli_prepare($conexion, $sql_prod);
        mysqli_stmt_bind_param($stmt_prod, "i", $id_producto);
        mysqli_stmt_execute($stmt_prod);
        $producto_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_prod));

        if (!$producto_db) {
            $mensaje_error = "El corte seleccionado no está disponible.";
        } elseif ($cantidad_kg > $producto_db['stock_disponible']) {
            $mensaje_error = "Stock insuficiente. Disponible: " . $producto_db['stock_disponible'] . " Kg.";
        } else {
            
            // Iniciamos la transacción de seguridad
            mysqli_begin_transaction($conexion);

            try {
                // 2. Buscar si el cliente ya existe por documento o registrarlo en tabla `cliente`
                $sql_cli = "SELECT id_usuario FROM cliente WHERE documento = ?";
                $stmt_cli = mysqli_prepare($conexion, $sql_cli);
                mysqli_stmt_bind_param($stmt_cli, "s", $documento);
                mysqli_stmt_execute($stmt_cli);
                $res_cli = mysqli_stmt_get_result($stmt_cli);
                $cliente_db = mysqli_fetch_assoc($res_cli);

                if ($cliente_db) {
                    $id_usuario = (int) $cliente_db['id_usuario'];
                } else {
                    $sql_ins_cli = "INSERT INTO cliente (nombres, apellidos, documento, telefono, correo, direccion) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_ins_cli = mysqli_prepare($conexion, $sql_ins_cli);
                    mysqli_stmt_bind_param($stmt_ins_cli, "ssssss", $nombres, $apellidos, $documento, $telefono, $correo, $direccion);
                    if (!mysqli_stmt_execute($stmt_ins_cli)) {
                        throw new Exception("No se pudo registrar el cliente.");
                    }
                    $id_usuario = mysqli_insert_id($conexion);
                }

                // 3. Insertar cabecera en tabla `pedidos`
                $sql_ped = "INSERT INTO pedidos (fecha_pedido, cliente_id_usuario) VALUES (NOW(), ?)";
                $stmt_ped = mysqli_prepare($conexion, $sql_ped);
                mysqli_stmt_bind_param($stmt_ped, "i", $id_usuario);
                if (!mysqli_stmt_execute($stmt_ped)) {
                    throw new Exception("No se pudo crear el pedido.");
                }
                $id_pedido = mysqli_insert_id($conexion);

                // 4. Insertar detalle en `detalle_pedido`
                $sql_det = "INSERT INTO detalle_pedido (cantidad_kg_productos, pedidos_id_pedido, productos_id_producto) VALUES (?, ?, ?)";
                $stmt_det = mysqli_prepare($conexion, $sql_det);
                mysqli_stmt_bind_param($stmt_det, "dii", $cantidad_kg, $id_pedido, $id_producto);
                if (!mysqli_stmt_execute($stmt_det)) {
                    throw new Exception("No se pudo guardar el detalle del pedido.");
                }

                // 5. Descontar stock en `productos` de forma segura
                $sql_stock = "UPDATE productos SET stock_disponible = stock_disponible - ? WHERE id_producto = ? AND stock_disponible >= ?";
                $stmt_stock = mysqli_prepare($conexion, $sql_stock);
                mysqli_stmt_bind_param($stmt_stock, "did", $cantidad_kg, $id_producto, $cantidad_kg);
                if (!mysqli_stmt_execute($stmt_stock) || mysqli_stmt_affected_rows($stmt_stock) === 0) {
                    throw new Exception("Stock insuficiente al procesar la compra.");
                }

                // Si todo salió bien, guardamos los cambios definitivamente
                mysqli_commit($conexion);

                // Redirigir a confirmacion.php con el número de pedido
                header("Location: confirmacion.php?pedido=" . $id_pedido);
                exit;

            } catch (Throwable $e) {
                // Si algo falló, deshacemos todos los cambios en la base de datos
                mysqli_rollback($conexion);
                $mensaje_error = "Ocurrió un error al registrar el pedido: " . $e->getMessage();
            }
        }
    }
}

// Consultar lista de productos disponibles para mostrarlos en el select
$productos_res = mysqli_query($conexion, "SELECT id_producto, nombre_producto, precio_por_kilo, stock_disponible FROM productos WHERE stock_disponible > 0");
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Realizar Compra - Carnicería Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilos.css">
  </head>
  <body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-carniceria sticky-top shadow-sm">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.html">
          <img src="img/Logo.png" alt="Logo Carnicería Digital" width="45" height="45" class="me-2 rounded-circle border border-2 border-white">
          <span>Carnicería Digital</span>
        </a>
        <div class="d-flex gap-2">
          <a href="index.html" class="btn btn-outline-light btn-sm">Inicio</a>
          <a href="tutorial.html" class="btn btn-warning btn-sm fw-bold text-dark">Tutorial</a>
        </div>
      </div>
    </nav>

    <main class="container my-5">
      <div class="row justify-content-center">
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <h1 class="h3 fw-bold mb-2 text-center" style="color: var(--color-principal);">Finalizar Pedido de Cortes</h1>
            <p class="text-muted text-center small mb-4">Selecciona tu corte, indica el peso en kilogramos y confirma tus datos de entrega.</p>

            <?php if (!empty($mensaje_error)): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
            <?php endif; ?>

            <form action="comprar.php" method="POST">
              <div class="mb-3">
                <label for="id_producto" class="form-label fw-semibold">Selecciona el Corte</label>
                <select class="form-select" id="id_producto" name="id_producto" required>
                  <option value="">-- Elige un producto --</option>
                  <?php while ($prod = mysqli_fetch_assoc($productos_res)): ?>
                    <option value="<?php echo $prod['id_producto']; ?>">
                      <?php echo htmlspecialchars($prod['nombre_producto']) . " - $" . number_format($prod['precio_por_kilo'], 0, ',', '.') . " / Kg (Disp: " . $prod['stock_disponible'] . " Kg)"; ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="cantidad_kg" class="form-label fw-semibold">Cantidad en Kilogramos (Ej: 0.5, 1, 2.5)</label>
                <!-- Aquí cambié el step a 0.001 para que permita gramos con precisión -->
                <input type="number" step="0.001" min="0.5" class="form-control" id="cantidad_kg" name="cantidad_kg" value="1" required>
              </div>

              <hr class="my-4">
              <h5 class="fw-bold mb-3" style="color: var(--color-principal);">Datos del Cliente</h5>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Nombres</label>
                  <input type="text" class="form-control" name="nombres" value="<?php echo htmlspecialchars($nombres_pre); ?>" maxlength="45" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Apellidos</label>
                  <input type="text" class="form-control" name="apellidos" value="<?php echo htmlspecialchars($apellidos_pre); ?>" maxlength="45" required>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Documento</label>
                  <input type="text" class="form-control" name="documento" value="<?php echo htmlspecialchars($documento_pre); ?>" maxlength="20" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Teléfono / WhatsApp</label>
                  <input type="tel" class="form-control" name="telefono" value="<?php echo htmlspecialchars($telefono_pre); ?>" maxlength="20" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <input type="email" class="form-control" name="correo" value="<?php echo htmlspecialchars($correo_pre); ?>" maxlength="50" required>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Dirección de entrega</label>
                <input type="text" class="form-control" name="direccion" value="<?php echo htmlspecialchars($direccion_pre); ?>" maxlength="100" required>
              </div>

              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="1" id="acepta" name="acepta" required>
                <label class="form-check-label small" for="acepta">
                  Confirmo que los datos de entrega y cadena de frío son correctos.
                </label>
              </div>

              <div class="d-grid">
                <button type="submit" name="confirmar_pedido" class="btn btn-danger fw-bold py-2" style="background-color: var(--color-principal); border-color: var(--color-principal);">
                  Confirmar Pedido
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>

    <footer class="text-white py-3 text-center" style="background-color: var(--color-secundario);">
      <small>© 2026 Carnicería Digital</small>
    </footer>
  </body>
</html>