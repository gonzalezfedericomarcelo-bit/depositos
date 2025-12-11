<?php
// Archivo: pedidos_despacho.php
// Propósito: Encargado de Insumos entrega la mercadería.
require 'db.php';
session_start();

// Permisos: Admin o Encargado Depósito Insumos
$roles = $_SESSION['user_roles'] ?? [];
if (!in_array('Encargado Depósito Insumos', $roles) && !in_array('Administrador', $roles)) {
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
            $cant_aprobada = $_POST['aprobada'][$id_item]; // Hidden
            
            // VALIDACIÓN CLAVE
            if ($cant_entregar > $cant_aprobada) {
                throw new Exception("Error: No puedes entregar más de lo que aprobó el Director Médico ($cant_aprobada).");
            }
            if ($cant_entregar < $cant_aprobada) {
                $hubo_recorte = true;
            }

            // Descontar Stock
            // Primero verificamos que haya stock real
            $stmtCheck = $pdo->prepare("SELECT stock_actual, nombre FROM insumos_medicos WHERE id = (SELECT id_insumo FROM pedidos_items WHERE id=:id)");
            $stmtCheck->execute([':id' => $id_item]);
            $insumoData = $stmtCheck->fetch();

            if ($insumoData['stock_actual'] < $cant_entregar) {
                throw new Exception("Stock insuficiente en depósito para: " . $insumoData['nombre']);
            }

            // Update Stock
            $stmtStock = $pdo->prepare("UPDATE insumos_medicos SET stock_actual = stock_actual - :cant WHERE id = (SELECT id_insumo FROM pedidos_items WHERE id=:id)");
            $stmtStock->execute([':cant' => $cant_entregar, ':id' => $id_item]);

            // Update Pedido Item
            $pdo->prepare("UPDATE pedidos_items SET cantidad_entregada = :cant WHERE id = :id")
                ->execute([':cant' => $cant_entregar, ':id' => $id_item]);
        }

        // VALIDACIÓN DE OBSERVACIÓN OBLIGATORIA
        if ($hubo_recorte && empty($observacion)) {
            throw new Exception("⚠️ ALERTA: Estás entregando MENOS cantidad de la aprobada por el Director. Es OBLIGATORIO escribir una observación justificando el recorte.");
        }

        // Finalizar Pedido
        $pdo->prepare("UPDATE pedidos_servicio SET estado = 'entregado', observaciones_entrega = :obs, fecha_entrega_real = NOW(), id_usuario_entrega = :user WHERE id = :id")
            ->execute([':obs' => $observacion, ':user' => $_SESSION['user_id'], ':id' => $id_pedido]);

        $pdo->commit();
        header("Location: insumos_entregas.php?msg=despacho_ok"); // O volver al dashboard
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
$items = $pdo->query("SELECT pi.*, im.nombre, im.stock_actual FROM pedidos_items pi JOIN insumos_medicos im ON pi.id_insumo = im.id WHERE pi.id_pedido = $id_pedido")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Despacho de Pedido #<?php echo $id_pedido; ?></h1>
    <h5 class="text-muted">Destino: <?php echo htmlspecialchars($pedido['servicio_solicitante']); ?></h5>
    <?php echo $mensaje; ?>

    <form method="POST">
        <div class="card mb-4 mt-3 border-success">
            <div class="card-header bg-success text-white fw-bold">
                <i class="fas fa-boxes"></i> Preparación y Entrega
            </div>
            <div class="card-body">
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-triangle"></i> Recuerde: No puede entregar más de lo autorizado por Dirección. Si entrega menos por falta de stock, debe justificarlo abajo.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th class="text-center">Stock Depósito</th>
                                <th class="text-center text-muted">Solicitado</th>
                                <th class="text-center text-primary">Aprobado Director</th>
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
                                           value="<?php echo $it['cantidad_aprobada']; ?>"> </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-danger">Observación de Entrega (Obligatorio si recorta cantidades):</label>
                    <textarea name="observacion_ajuste" class="form-control border-danger" rows="3" placeholder="Ej: No hay stock suficiente de ampollas..."></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success fw-bold btn-lg">CONFIRMAR SALIDA DE STOCK</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>