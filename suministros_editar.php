<?php
// Archivo: suministros_editar.php
// Propósito: Editar suministros con AUDITORÍA DE CAMBIOS DE STOCK

require 'db.php';
session_start();

if (!isset($_GET['id'])) { header("Location: suministros_stock.php"); exit; }
$id = $_GET['id'];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 1. OBTENER STOCK ANTERIOR
        $stmtOld = $pdo->prepare("SELECT stock_actual FROM suministros_generales WHERE id = :id");
        $stmtOld->execute(['id' => $id]);
        $stockAnterior = $stmtOld->fetchColumn();
        $stockNuevo = $_POST['stock_actual'];

        $pdo->beginTransaction();

        // 2. ACTUALIZAR
        $sql = "UPDATE suministros_generales SET codigo=:c, nombre=:n, descripcion=:d, unidad_medida=:u, stock_actual=:s, stock_minimo=:m WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':c'=>$_POST['codigo'], ':n'=>$_POST['nombre'], ':d'=>$_POST['descripcion'], ':u'=>$_POST['unidad_medida'], ':s'=>$stockNuevo, ':m'=>$_POST['stock_minimo'], ':id'=>$id]);

        // 3. AUDITORÍA
        if ($stockAnterior != $stockNuevo) {
            $stmtAudit = $pdo->prepare("INSERT INTO historial_ajustes (id_usuario, tipo_origen, id_item, stock_anterior, stock_nuevo) VALUES (:user, 'suministro', :item, :old, :new)");
            $stmtAudit->execute([
                ':user' => $_SESSION['user_id'],
                ':item' => $id,
                ':old'  => $stockAnterior,
                ':new'  => $stockNuevo
            ]);
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success">✅ Actualizado. <a href="suministros_stock.php">Volver</a></div>';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$stmt = $pdo->prepare("SELECT * FROM suministros_generales WHERE id = :id");
$stmt->execute(['id' => $id]);
$it = $stmt->fetch();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editar: <?php echo htmlspecialchars($it['nombre']); ?></h1>
    <?php echo $mensaje; ?>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> <strong>Seguridad:</strong> Las modificaciones de stock quedan registradas en el sistema de auditoría.
    </div>

    <div class="card mb-4 border-success">
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-4"><label>Código</label><input type="text" name="codigo" class="form-control" value="<?php echo $it['codigo']; ?>"></div>
                    <div class="col-md-8"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo $it['nombre']; ?>" required></div>
                </div>
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion" class="form-control"><?php echo $it['descripcion']; ?></textarea></div>
                <div class="row mb-3">
                    <div class="col"><label>Unidad</label><input type="text" name="unidad_medida" class="form-control" value="<?php echo $it['unidad_medida']; ?>"></div>
                    
                    <div class="col">
                        <label class="text-danger fw-bold">Stock Real</label>
                        <input type="number" name="stock_actual" class="form-control border-danger fw-bold" value="<?php echo $it['stock_actual']; ?>">
                    </div>
                    
                    <div class="col"><label>Mínimo</label><input type="number" name="stock_minimo" class="form-control" value="<?php echo $it['stock_minimo']; ?>"></div>
                </div>
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                <a href="suministros_stock.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>