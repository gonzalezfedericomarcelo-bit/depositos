<?php
// Archivo: pedidos_crear_suministros.php
// Propósito: El Responsable de Servicio pide Suministros Generales -> Va a Logística
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Validar que sea Responsable de Servicio
$rol_servicio = $_SESSION['user_data']['rol_en_servicio'] ?? '';
$mi_servicio = $_SESSION['user_data']['servicio'] ?? '';

if ($rol_servicio != 'Responsable') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>⛔ ACCESO DENEGADO: Solo el RESPONSABLE del servicio puede realizar pedidos.</div></div>";
    include 'includes/footer.php'; exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty($_POST['items'])) throw new Exception("No seleccionaste ningún suministro.");

        $pdo->beginTransaction();
        
        // 1. Crear Cabecera (Estado: pendiente_logistica)
        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, id_usuario_solicitante, servicio_solicitante, estado) 
                VALUES ('suministros', :uid, :serv, 'pendiente_logistica')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $_SESSION['user_id'], ':serv' => $mi_servicio]);
        $id_pedido = $pdo->lastInsertId();
        
        // 2. Insertar Ítems (Usamos campo id_suministro)
        $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_suministro, cantidad_solicitada) VALUES (:idp, :ids, :cant)");
        $countItems = 0;
        foreach ($_POST['items'] as $it) {
            if ($it['cantidad'] > 0) {
                $stmtItem->execute([':idp' => $id_pedido, ':ids' => $it['id'], ':cant' => $it['cantidad']]);
                $countItems++;
            }
        }

        if ($countItems == 0) throw new Exception("Debes pedir al menos una cantidad mayor a 0.");
        
        // 3. Notificar al Encargado de Logística
        $stmtRol = $pdo->query("SELECT id FROM roles WHERE nombre = 'Encargado Logística' LIMIT 1");
        $rolLog = $stmtRol->fetchColumn();
        if ($rolLog) {
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
                ->execute([$rolLog, "Nuevo pedido Suministros: $mi_servicio", "pedidos_revision_logistica.php?id=" . $id_pedido]);
        }
        
        $pdo->commit();
        echo "<script>alert('Pedido enviado a Logística.'); window.location='dashboard.php';</script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='container mt-3'><div class='alert alert-danger'>Error: " . $e->getMessage() . "</div></div>";
    }
}

// Cargar suministros disponibles
$suministros = $pdo->query("SELECT * FROM suministros_generales WHERE stock_actual > 0 ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Solicitud de Suministros Generales</h1>
    <div class="alert alert-success">
        <i class="fas fa-boxes"></i> Estás realizando un pedido para el servicio: <strong><?php echo htmlspecialchars($mi_servicio); ?></strong>
    </div>
    
    <form method="POST">
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white d-flex justify-content-between">
                <span>Selección de Artículos</span>
                <span class="badge bg-white text-success">Responsable: <?php echo $_SESSION['user_name']; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Artículo</th>
                                <th>Código</th>
                                <th class="text-center">Stock Disp.</th>
                                <th width="150">Cantidad a Pedir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suministros as $sum): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sum['nombre']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($sum['codigo']); ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?php echo $sum['stock_actual']; ?></span></td>
                                <td>
                                    <input type="hidden" name="items[<?php echo $sum['id']; ?>][id]" value="<?php echo $sum['id']; ?>">
                                    <input type="number" name="items[<?php echo $sum['id']; ?>][cantidad]" class="form-control form-control-sm border-success fw-bold" min="0" placeholder="0">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end bg-white">
                <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-paper-plane me-2"></i> Enviar a Logística</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>