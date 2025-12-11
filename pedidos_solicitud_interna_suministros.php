<?php
// Archivo: pedidos_solicitud_interna_suministros.php
// Propósito: Pedido Ad-hoc (Urgencias/Consumo Diario) -> Logística -> Depósito
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

if (($_SESSION['user_data']['rol_en_servicio'] ?? '') != 'Responsable') {
    die("<div class='alert alert-danger m-4'>Acceso denegado.</div>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $stmtFlujo = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = 'movimiento_suministros' ORDER BY paso_orden ASC LIMIT 1");
        $stmtFlujo->execute();
        $primerPaso = $stmtFlujo->fetch();

        if (!$primerPaso) throw new Exception("Error: Flujo 'movimiento_suministros' no configurado.");

        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, proceso_origen, id_usuario_solicitante, servicio_solicitante, estado, paso_actual_id) 
                VALUES ('suministros', 'movimiento_suministros', :uid, :serv, :estado, :paso_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $_SESSION['user_id'], 
            ':serv' => $_SESSION['user_data']['servicio'],
            ':estado' => $primerPaso['nombre_estado'], 
            ':paso_id' => $primerPaso['id']
        ]);
        $id_pedido = $pdo->lastInsertId();

        if (isset($_POST['suministro_id'])) {
            $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_suministro, cantidad_solicitada) VALUES (:idp, :ids, :cant)");
            for ($i = 0; $i < count($_POST['suministro_id']); $i++) {
                if ($_POST['cantidad'][$i] > 0) {
                    $stmtItem->execute([':idp' => $id_pedido, ':ids' => $_POST['suministro_id'][$i], ':cant' => $_POST['cantidad'][$i]]);
                }
            }
        }

        $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$primerPaso['id_rol_responsable'], "Solicitud Interna Suministros: " . $_SESSION['user_data']['servicio'], "bandeja_gestion_dinamica.php?id=" . $id_pedido]);

        $pdo->commit();
        echo "<script>window.location='dashboard.php?msg=solicitud_enviada';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-4'>Error: ".$e->getMessage()."</div>";
    }
}

$suministros = $pdo->query("SELECT * FROM suministros_generales WHERE stock_actual > 0 ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Solicitud Interna (Suministros)</h1>
    <div class="alert alert-info">
        <i class="fas fa-tools"></i> Use este formulario para reposiciones rápidas o urgencias (Ej: Plomería, Limpieza urgente).
    </div>
    
    <form method="POST">
        <div class="card mb-4 shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between">
                <span>Artículos a Retirar</span>
                <button type="button" class="btn btn-sm btn-dark" onclick="agregarFila()">+ Agregar</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Artículo</th><th width="150">Cantidad</th><th width="50"></th></tr></thead>
                    <tbody id="bodyItems"></tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-warning fw-bold">Solicitar a Logística</button>
            </div>
        </div>
    </form>
</div>

<div id="itemOptions" style="display:none;">
    <?php foreach($suministros as $s) { echo "<option value='".$s['id']."'>".htmlspecialchars($s['nombre'])." (Disp: ".$s['stock_actual'].")</option>"; } ?>
</div>

<script>
function agregarFila() {
    var tbody = document.getElementById('bodyItems');
    var row = document.createElement('tr');
    row.innerHTML = `<td><select name="suministro_id[]" class="form-select">${document.getElementById('itemOptions').innerHTML}</select></td><td><input type="number" name="cantidad[]" class="form-control" required min="1"></td><td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>`;
    tbody.appendChild(row);
}
window.onload = agregarFila;
</script>
<?php include 'includes/footer.php'; ?>