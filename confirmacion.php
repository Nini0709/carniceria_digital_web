<?php
require_once "conexion.php";

$id_pedido = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
$pedido_info = null;

if ($id_pedido) {
    $sql = "SELECT 
                p.id_pedido,
                p.fecha_pedido,
                c.nombres,
                c.apellidos,
                c.documento,
                c.telefono,
                c.correo,
                c.direccion,
                pr.nombre_producto,
                pr.precio_por_kilo,
                dp.cantidad_kg_productos,
                (pr.precio_por_kilo * dp.cantidad_kg_productos) AS total_pagar
            FROM pedidos p
            INNER JOIN cliente c ON p.cliente_id_usuario = c.id_usuario
            INNER JOIN detalle_pedido dp ON p.id_pedido = dp.pedidos_id_pedido
            INNER JOIN productos pr ON dp.productos_id_producto = pr.id_producto
            WHERE p.id_pedido = ?";

    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_pedido);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $pedido_info = mysqli_fetch_assoc($resultado);
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de Pedido - Carnicería Digital</title>
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
          <a href="comprar.php" class="btn btn-warning btn-sm fw-bold text-dark">Nuevo Pedido</a>
        </div>
      </div>
    </nav>

    <main class="container my-5">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">

            <?php if ($pedido_info): ?>
              <div class="text-center mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
                <h1 class="h3 fw-bold mt-2" style="color: var(--color-principal);">¡Pedido Confirmado!</h1>
                <p class="text-muted small mb-0">Orden #<?php echo str_pad($pedido_info['id_pedido'], 4, "0", STR_PAD_LEFT); ?> • <?php echo $pedido_info['fecha_pedido']; ?></p>
              </div>

              <div class="bg-light p-3 rounded-3 mb-4">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Datos de Entrega</h6>
                <p class="mb-1"><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido_info['nombres'] . " " . $pedido_info['apellidos']); ?></p>
                <p class="mb-1"><strong>Documento:</strong> <?php echo htmlspecialchars($pedido_info['documento']); ?></p>
                <p class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido_info['telefono']); ?></p>
                <p class="mb-0"><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido_info['direccion']); ?></p>
              </div>

              <div class="border rounded-3 p-3 mb-4">
                <h6 class="fw-bold text-uppercase text-muted small mb-3">Detalle del Corte</h6>
                <div class="d-flex justify-content-between mb-2">
                  <span><?php echo htmlspecialchars($pedido_info['nombre_producto']); ?></span>
                  <strong><?php echo number_format($pedido_info['cantidad_kg_productos'], 2); ?> Kg</strong>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-2">
                  <span>Precio por Kg:</span>
                  <span>$<?php echo number_format($pedido_info['precio_por_kilo'], 0, ',', '.'); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fs-5 fw-bold">
                  <span>Total Estimado:</span>
                  <span class="text-danger">$<?php echo number_format($pedido_info['total_pagar'], 0, ',', '.'); ?></span>
                </div>
              </div>

              <div class="d-grid gap-2">
                <a href="index.html" class="btn btn-danger fw-bold" style="background-color: var(--color-principal); border-color: var(--color-principal);">Volver al Inicio</a>
                <a href="tutorial.html" class="btn btn-outline-secondary">Ver Tutorial de Preparación</a>
              </div>

            <?php else: ?>
              <div class="text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <h2 class="h4 fw-bold mt-3">No se encontró el pedido</h2>
                <p class="text-muted small">Parece que entraste directamente sin registrar una compra.</p>
                <a href="comprar.php" class="btn btn-danger mt-2" style="background-color: var(--color-principal); border-color: var(--color-principal);">Ir a Comprar</a>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </main>

    <footer class="text-white py-3 text-center" style="background-color: var(--color-secundario);">
      <small>© 2026 Carnicería Digital</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>