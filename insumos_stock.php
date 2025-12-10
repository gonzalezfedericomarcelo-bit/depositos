<?php
// Archivo: insumos_stock.php
// CORREGIDO: Rol COMPRAS solo ve, no edita ni crea.

require 'db.php';

// DEFINICIÓN DE PERMISOS ESPECÍFICOS
session_start();
$roles = $_SESSION['user_roles'] ?? [];

// ¿Quién puede TOCAR el stock? (Crear, Editar) -> Admin y Encargado Depósito
$puede_editar = in_array('Administrador', $roles) || in_array('Encargado Depósito Insumos', $roles);

// Lógica de Guardado (Solo si tiene permiso)
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    if (!$puede_editar) {
        $mensaje = '<div class="alert alert-danger">⛔ No tienes permiso para crear insumos.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO insumos_medicos (codigo, nombre, descripcion, unidad_medida, stock_actual, stock_minimo, fecha_vencimiento, lote) VALUES (:codigo, :nombre, :descripcion, :unidad, :stock, :minimo, :vencimiento, :lote)");
            $stmt->execute([
                ':codigo' => $_POST['codigo'], ':nombre' => $_POST['nombre'], ':descripcion' => $_POST['descripcion'],
                ':unidad' => $_POST['unidad_medida'], ':stock' => $_POST['stock_actual'], ':minimo' => $_POST['stock_minimo'],
                ':vencimiento' => !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null, ':lote' => $_POST['lote']
            ]);
            $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Insumo creado.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } catch (PDOException $e) {
            $mensaje = '<div class="alert alert-danger">❌ Error: ' . $e->getMessage() . '</div>';
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$stmt = $pdo->query("SELECT * FROM insumos_medicos ORDER BY nombre ASC");
$insumos = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Stock Insumos Médicos</h1>
    <?php echo $mensaje; ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between">
            <span>Inventario Actual</span>
            
            <?php if ($puede_editar): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoInsumo">+ Nuevo Insumo</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr><th>Cód</th><th>Nombre</th><th>Stock</th><th>Mín</th><th>Vence</th><th>Estado</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($insumos as $item): ?>
                        <?php 
                            $estado = '<span class="badge bg-success">OK</span>';
                            if ($item['stock_actual'] <= $item['stock_minimo']) $estado = '<span class="badge bg-danger">BAJO</span>';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                            <td class="fw-bold text-center"><?php echo $item['stock_actual']; ?></td>
                            <td class="text-center"><?php echo $item['stock_minimo']; ?></td>
                            <td><?php echo $item['fecha_vencimiento']; ?></td>
                            <td class="text-center"><?php echo $estado; ?></td>
                            <td class="text-center">
                                <?php if ($puede_editar): ?>
                                <a href="insumos_editar.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small"><i class="fas fa-eye"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($puede_editar): ?>
<div class="modal fade" id="modalNuevoInsumo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nuevo Insumo</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row mb-2"><div class="col"><input type="text" name="codigo" class="form-control" placeholder="Código"></div><div class="col"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div></div>
                    <div class="mb-2"><textarea name="descripcion" class="form-control" placeholder="Descripción"></textarea></div>
                    <div class="row mb-2">
                        <div class="col"><select name="unidad_medida" class="form-select"><option>unidades</option><option>cajas</option><option>litros</option></select></div>
                        <div class="col"><input type="number" name="stock_actual" class="form-control" placeholder="Stock Inicial" required></div>
                        <div class="col"><input type="number" name="stock_minimo" class="form-control" placeholder="Mínimo" required></div>
                    </div>
                    <div class="row"><div class="col"><input type="text" name="lote" class="form-control" placeholder="Lote"></div><div class="col"><input type="date" name="fecha_vencimiento" class="form-control"></div></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>