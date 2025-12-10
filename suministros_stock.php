<?php
// Archivo: suministros_stock.php
// CORREGIDO: Rol COMPRAS solo ve, no edita ni crea suministros.

require 'db.php';

// DEFINICIÓN DE PERMISOS
session_start();
$roles = $_SESSION['user_roles'] ?? [];

// ¿Quién puede TOCAR el stock de suministros?
// Solo Admin y el Encargado de ese depósito específico.
$puede_editar = in_array('Administrador', $roles) || in_array('Encargado Depósito Suministros', $roles);

// Lógica de Guardado (Solo si tiene permiso)
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    if (!$puede_editar) {
        $mensaje = '<div class="alert alert-danger">⛔ No tienes permiso para crear suministros.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO suministros_generales (codigo, nombre, descripcion, unidad_medida, stock_actual, stock_minimo) VALUES (:c, :n, :d, :u, :s, :m)");
            $stmt->execute([':c'=>$_POST['codigo'], ':n'=>$_POST['nombre'], ':d'=>$_POST['descripcion'], ':u'=>$_POST['unidad_medida'], ':s'=>$_POST['stock_actual'], ':m'=>$_POST['stock_minimo']]);
            $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Suministro creado.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } catch (PDOException $e) { 
            $mensaje = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>'; 
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$stmt = $pdo->query("SELECT * FROM suministros_generales ORDER BY nombre ASC");
$items = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Stock Suministros Generales</h1>
    <?php echo $mensaje; ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Inventario Actual</span>
            
            <?php if ($puede_editar): ?>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">+ Nuevo Artículo</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr><th>Cód</th><th>Nombre</th><th>Stock</th><th>Mín</th><th>Estado</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <?php 
                            $estado = '<span class="badge bg-success">OK</span>';
                            if ($it['stock_actual'] <= $it['stock_minimo']) $estado = '<span class="badge bg-danger">BAJO</span>';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($it['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                            <td class="fw-bold text-center"><?php echo $it['stock_actual']; ?></td>
                            <td class="text-center"><?php echo $it['stock_minimo']; ?></td>
                            <td class="text-center"><?php echo $estado; ?></td>
                            <td class="text-center">
                                <?php if ($puede_editar): ?>
                                    <a href="suministros_editar.php?id=<?php echo $it['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small" title="Solo lectura"><i class="fas fa-eye"></i></span>
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
<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white"><h5 class="modal-title">Nuevo Suministro</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row mb-2"><div class="col"><input type="text" name="codigo" class="form-control" placeholder="Código"></div><div class="col"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div></div>
                    <div class="mb-2"><textarea name="descripcion" class="form-control" placeholder="Descripción"></textarea></div>
                    <div class="row mb-2">
                        <div class="col"><select name="unidad_medida" class="form-select"><option>unidades</option><option>cajas</option><option>litros</option></select></div>
                        <div class="col"><input type="number" name="stock_actual" class="form-control" placeholder="Stock" required></div>
                        <div class="col"><input type="number" name="stock_minimo" class="form-control" placeholder="Mínimo" required></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>