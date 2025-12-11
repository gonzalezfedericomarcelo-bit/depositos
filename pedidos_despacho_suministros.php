<?php
// Archivo: pedidos_despacho_suministros.php
// Propósito: Encargado de Suministros entrega mercadería.
require 'db.php';
session_start();

$roles = $_SESSION['user_roles'] ?? [];
if (!in_array('Encargado Depósito Suministros', $roles) && !in_array('Administrador', $roles)) {
    die("Acceso denegado.");
}

$id_pedido = $_GET['id'] ?? 0;
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        $observacion = trim($_POST['observacion_ajuste']);
        $hubo_recorte = false;

        foreach ($_POST['entrega'] as $id_item => $cant_entregar) {
            $cant_aprobada = $_POST['aprobada'][$id_item]; 
            
            if ($cant_entregar > $cant_aprobada) throw new Exception("Error: No puedes entregar más de lo aprobado por Logística.");
            if ($cant_entregar < $cant_aprobada) $hubo_recorte = true;

            // Verificar Stock en suministros_generales
            $stmtCheck = $pdo->prepare("SELECT stock_actual, nombre FROM suministros_generales WHERE id = (SELECT id_suministro FROM pedidos_items WHERE id=:id)");
            $stmtCheck->execute([':id' => $id_item]);
            $itemData = $stmtCheck->fetch();

            if ($itemData['stock_actual'] < $cant_entregar) {
                throw new Exception("Stock insuficiente para: " . $itemData['nombre']);
            }

            // Descontar Stock
            $stmtStock = $pdo->prepare("UPDATE suministros_generales SET stock_actual = stock_actual - :cant WHERE id = (SELECT id_suministro FROM pedidos_items WHERE id=:id)");
            $stmtStock->execute([':cant' => $cant_entregar, ':id' => $id_item]);

            // Actualizar Pedido
            $pdo->prepare("UPDATE pedidos_items SET cantidad_entregada = :cant WHERE id = :id")
                ->execute([':cant' => $cant_entregar, ':id' => $id_item]);
        }

        if ($hubo_recorte && empty($observacion)) {
            throw new Exception("⚠️ ALERTA: Entrega parcial detectada. Debes justificar el motivo en la Observación.");
        }

        // Finalizar Pedido
        $pdo->prepare("UPDATE pedidos_servicio SET estado = 'entregado', observaciones_entrega = :obs, fecha_entrega_real = NOW(), id_usuario_entrega = :user WHERE id = :id")
            ->execute([':obs' => $observacion, ':user' => $_SESSION['user_id'], ':id' => $id_pedido]);

        $pdo->commit();
        header("Location: suministros_entregas.php?msg=despacho_ok");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert alert-danger'>".$e->getMessage()."</div>";
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$pedido = $pdo->query("SELECT * FROM pedidos_servicio WHERE id = $id_pedido")->fetch();
$items = $pdo->query("SELECT pi.*, sg.nombre, sg.stock_actual FROM pedidos_items pi JOIN suministros_generales sg ON pi.id_suministro = sg.id WHERE pi.id_pedido = $id_pedido")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Despacho Suministros #<?php echo $id_pedido; ?></h1>
    <h5 class="text-muted">Destino: <?php echo htmlspecialchars($pedido['servicio_solicitante']); ?></h5>
    <?php echo $mensaje; ?>

    <form method="POST">
        <div class="card mb-4 mt-3 border-success">
            <div class="card-header bg-success text-white fw-bold">
                <i class="fas fa-dolly"></i> Preparación y Entrega
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th class="text-center">Stock Depósito</th>
                                <th class="text-center text-muted">Solicitado</th>
                                <th class="text-center text-primary">Aprobado Log.</th>
                                <th class="text-center bg-success bg-opacity-10" width="150">A Entregar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                                <td class="text-center fw-bold"><?php echo $it['stock_actual']; ?></td>
                                <td class="text-center text-muted text-decoration-line-through"><?php echo $it['cantidad_solicitada']; ?></td>
                                <td class="text-center fw-bold text-primary fs-5">
                                    <?php echo $it['cantidad_aprobada']; ?>
                                    <input type="hidden" name="aprobada[<?php echo $it['id']; ?>]" value="<?php echo $it['cantidad_aprobada']; ?>">
                                </td>
                                <td class="bg-success bg-opacity-10">
                                    <input type="number" name="entrega[<?php echo $it['id']; ?>]" 
                                           class="form-control text-center fw-bold border-success text-success" 
                                           max="<?php echo $it['cantidad_aprobada']; ?>" min="0" 
                                           value="<?php echo $it['cantidad_aprobada']; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-danger">Observación (Obligatorio si falta mercadería):</label>
                    <textarea name="observacion_ajuste" class="form-control border-danger" rows="3"></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success fw-bold btn-lg">CONFIRMAR SALIDA</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>