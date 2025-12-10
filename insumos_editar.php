<?php
// Archivo: insumos_editar.php
// Propósito: Editar insumos con AUDITORÍA DE CAMBIOS DE STOCK

require 'db.php';
session_start();

if (!isset($_GET['id'])) { header("Location: insumos_stock.php"); exit; }
$id = $_GET['id'];
$mensaje = "";

// GUARDAR CAMBIOS
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 1. OBTENER STOCK ANTERIOR (Para comparar)
        $stmtOld = $pdo->prepare("SELECT stock_actual, nombre FROM insumos_medicos WHERE id = :id");
        $stmtOld->execute(['id' => $id]);
        $datosAnt = $stmtOld->fetch();
        $stockAnterior = $datosAnt['stock_actual'];
        $stockNuevo = $_POST['stock_actual'];

        $pdo->beginTransaction();

        // 2. ACTUALIZAR EL INSUMO
        $sql = "UPDATE insumos_medicos SET codigo=:cod, nombre=:nom, descripcion=:desc, unidad_medida=:uni, stock_actual=:stock, stock_minimo=:min, lote=:lote, fecha_vencimiento=:venc WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cod' => $_POST['codigo'], ':nom' => $_POST['nombre'], ':desc' => $_POST['descripcion'],
            ':uni' => $_POST['unidad_medida'], ':stock' => $stockNuevo, ':min' => $_POST['stock_minimo'],
            ':lote' => $_POST['lote'], ':venc' => !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null,
            ':id' => $id
        ]);

        // 3. AUDITORÍA: SI HUBO CAMBIO DE STOCK, LO REGISTRAMOS
        if ($stockAnterior != $stockNuevo) {
            $stmtAudit = $pdo->prepare("INSERT INTO historial_ajustes (id_usuario, tipo_origen, id_item, stock_anterior, stock_nuevo) VALUES (:user, 'insumo', :item, :old, :new)");
            $stmtAudit->execute([
                ':user' => $_SESSION['user_id'],
                ':item' => $id,
                ':old'  => $stockAnterior,
                ':new'  => $stockNuevo
            ]);
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success">✅ Actualizado correctamente. <a href="insumos_stock.php">Volver</a></div>';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Obtener datos actuales para mostrar en el formulario
$stmt = $pdo->prepare("SELECT * FROM insumos_medicos WHERE id = :id");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editar: <?php echo htmlspecialchars($item['nombre']); ?></h1>
    <?php echo $mensaje; ?>
    
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> <strong>Atención:</strong> Cualquier modificación manual del stock quedará registrada en el historial de auditoría con su usuario y fecha.
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-4"><label>Código</label><input type="text" name="codigo" class="form-control" value="<?php echo $item['codigo']; ?>"></div>
                    <div class="col-md-8"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo $item['nombre']; ?>" required></div>
                </div>
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion" class="form-control"><?php echo $item['descripcion']; ?></textarea></div>
                <div class="row mb-3">
                    <div class="col"><label>Unidad</label><input type="text" name="unidad_medida" class="form-control" value="<?php echo $item['unidad_medida']; ?>"></div>
                    
                    <div class="col">
                        <label class="text-danger fw-bold">Stock Real (Auditable)</label>
                        <input type="number" name="stock_actual" class="form-control border-danger fw-bold" value="<?php echo $item['stock_actual']; ?>">
                        <div class="form-text text-danger">Modificar solo por error de conteo.</div>
                    </div>
                    
                    <div class="col"><label>Mínimo</label><input type="number" name="stock_minimo" class="form-control" value="<?php echo $item['stock_minimo']; ?>"></div>
                </div>
                <div class="row mb-3">
                    <div class="col"><label>Lote</label><input type="text" name="lote" class="form-control" value="<?php echo $item['lote']; ?>"></div>
                    <div class="col"><label>Vencimiento</label><input type="date" name="fecha_vencimiento" class="form-control" value="<?php echo $item['fecha_vencimiento']; ?>"></div>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="insumos_stock.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>