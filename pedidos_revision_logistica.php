<?php
// Archivo: pedidos_revision_logistica.php
// Propósito: Logística revisa pedidos internos de suministros
require 'db.php';
session_start();

$roles = $_SESSION['user_roles'] ?? [];
if (!in_array('Encargado Logística', $roles) && !in_array('Administrador', $roles)) {
    die("Acceso denegado.");
}

$id_pedido = $_GET['id'] ?? 0;
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        $obs = $_POST['observaciones_logistica'];
        
        // 1. Actualizar cantidades aprobadas
        foreach ($_POST['aprobado'] as $id_item => $cant_aprob) {
            $pdo->prepare("UPDATE pedidos_items SET cantidad_aprobada = :cant WHERE id = :id")
                ->execute([':cant' => $cant_aprob, ':id' => $id_item]);
        }

        // 2. Actualizar Cabecera (Estado: aprobado_logistica)
        $sql = "UPDATE pedidos_servicio SET estado = 'aprobado_logistica', fecha_aprobacion_logistica = NOW(), id_logistica_aprobador = :log, observaciones_logistica = :obs WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':log' => $_SESSION['user_id'], ':obs' => $obs, ':id' => $id_pedido]);

        // 3. Notificar al Depósito de Suministros
        $stmtRol = $pdo->query("SELECT id FROM roles WHERE nombre = 'Encargado Depósito Suministros' LIMIT 1");
        $rolDepo = $stmtRol->fetchColumn();
        if ($rolDepo) {
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
                ->execute([$rolDepo, "Pedido suministros aprobado (ID #$id_pedido). Listo para despachar.", "pedidos_despacho_suministros.php?id=" . $id_pedido]);
        }

        $pdo->commit();
        header("Location: dashboard.php?msg=pedido_aprobado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert alert-danger'>Error: ".$e->getMessage()."</div>";
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$pedido = $pdo->query("SELECT p.*, u.nombre_completo FROM pedidos_servicio p JOIN usuarios u ON p.id_usuario_solicitante = u.id WHERE p.id = $id_pedido")->fetch();
// JOIN con suministros_generales
$items = $pdo->query("SELECT pi.*, sg.nombre, sg.stock_actual FROM pedidos_items pi JOIN suministros_generales sg ON pi.id_suministro = sg.id WHERE pi.id_pedido = $id_pedido")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Revisión Logística #<?php echo $id_pedido; ?></h1>
    <h4 class="text-success"><?php echo htmlspecialchars($pedido['servicio_solicitante']); ?> <small class="text-muted fs-6">(Solicitante: <?php echo $pedido['nombre_completo']; ?>)</small></h4>
    <?php echo $mensaje; ?>

    <form method="POST">
        <div class="card mb-4 mt-3 shadow border-warning">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="fas fa-clipboard-check"></i> Autorización de Cantidades
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Artículo</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center text-primary">Solicitado</th>
                                <th class="text-center bg-warning bg-opacity-10" width="150">Aprobado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                                <td class="text-center"><?php echo $it['stock_actual']; ?></td>
                                <td class="text-center fw-bold text-primary fs-5"><?php echo $it['cantidad_solicitada']; ?></td>
                                <td class="bg-warning bg-opacity-10">
                                    <input type="number" name="aprobado[<?php echo $it['id']; ?>]" 
                                           class="form-control text-center fw-bold border-warning" 
                                           value="<?php echo $it['cantidad_solicitada']; ?>" min="0">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Observaciones Logística (Opcional)</label>
                    <textarea name="observaciones_logistica" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success fw-bold">AUTORIZAR ENVÍO A DEPÓSITO</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>