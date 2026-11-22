<?php
require_once "conexion.php";

$sql = "SELECT 
            p.id_pedido,
            p.fecha_pedido,
            c.nombres,
            c.apellidos,
            c.documento,
            c.direccion,
            pr.nombre_producto,
            pr.precio_por_kilo,
            dp.cantidad_kg_productos,
            (pr.precio_por_kilo * dp.cantidad_kg_productos) AS total
        FROM pedidos p
        INNER JOIN cliente c ON p.cliente_id_usuario = c.id_usuario
        INNER JOIN detalle_pedido dp ON p.id_pedido = dp.pedidos_id_pedido
        INNER JOIN productos pr ON dp.productos_id_producto = pr.id_producto
        ORDER BY p.id_pedido DESC";

$resultado = mysqli_query($conexion, $sql);
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listado de Pedidos - Carnicería Digital</title>
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
        <div class="d-flex flex-wrap gap-2">
          <a href="index.html" class="btn btn-outline-light btn-sm">Inicio</a>
          <a href="productos.html" class="btn btn-outline-light btn-sm">Productos</a>
          <a href="tutorial.html" class="btn btn-outline-light btn-sm">Tutorial</a>
          <a href="comprar.php" class="btn btn-warning btn-sm fw-bold text-dark">Nuevo Pedido</a>
        </div>
      </div>
    </nav>

    <main class="container my-5">
      <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
          <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--color-principal);">Pedidos Registrados</h1>
            <p class="text-muted small mb-0">Consulta en tiempo real desde la base de datos <code>digitalcarniceria_db</code>.</p>
          </div>
          <a href="comprar.php" class="btn btn-danger btn-sm fw-bold" style="background-color: var(--color-principal); border-color: var(--color-principal);">
            <i class="bi bi-plus-circle me-1"></i> Registrar Compra
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th># Orden</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Corte</th>
                <th>Peso (Kg)</th>
                <th>Total</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                  <tr>
                    <td class="fw-bold">#<?php echo str_pad($fila['id_pedido'], 4, "0", STR_PAD_LEFT); ?></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($fila['fecha_pedido']); ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($fila['nombres'] . " " . $fila['apellidos']); ?></div>
                      <small class="text-muted">CC: <?php echo htmlspecialchars($fila['documento']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($fila['nombre_producto']); ?></td>
                    <td><?php echo number_format($fila['cantidad_kg_productos'], 2); ?> Kg</td>
                    <td class="fw-bold text-danger">$<?php echo number_format($fila['total'], 0, ',', '.'); ?></td>
                    <td>
                      <a href="confirmacion.php?pedido=<?php echo $fila['id_pedido']; ?>" class="btn btn-outline-secondary btn-sm">Ver Recibo</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Aún no hay pedidos registrados.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <footer class="text-white py-3 text-center" style="background-color: var(--color-secundario);">
      <small>© 2026 Carnicería Digital</small>
    </footer>
  </body>
</html>