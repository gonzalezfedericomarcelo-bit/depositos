<?php
// Archivo: pedidos_crear.php
// Propósito: El Responsable de Servicio pide Insumos Médicos -> Va al Director Médico
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Validar que sea Responsable de Servicio
$rol_servicio = $_SESSION['user_data']['rol_en_servicio'] ?? '';
$mi_servicio = $_SESSION['user_data']['servicio'] ?? '';

if ($rol_servicio != 'Responsable') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>⛔ ACCESO DENEGADO: Solo el usuario RESPONSABLE del servicio puede realizar pedidos. Tu rol es: $rol_servicio</div></div>";
    include 'includes/footer.php'; exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty($_POST['items'])) throw new Exception("No seleccionaste ningún insumo.");

        $pdo->beginTransaction();
        
        // 1. Crear Cabecera
        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, id_usuario_solicitante, servicio_solicitante, estado) 
                VALUES ('insumos_medicos', :uid, :serv, 'pendiente_director')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $_SESSION['user_id'], ':serv' => $mi_servicio]);
        $id_pedido = $pdo->lastInsertId();
        
        // 2. Insertar Ítems
        $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_insumo, cantidad_solicitada) VALUES (:idp, :idi, :cant)");
        $countItems = 0;
        foreach ($_POST['items'] as $it) {
            if ($it['cantidad'] > 0) {
                $stmtItem->execute([':idp' => $id_pedido, ':idi' => $it['id'], ':cant' => $it['cantidad']]);
                $countItems++;
            }
        }

        if ($countItems == 0) throw new Exception("Debes pedir al menos una cantidad mayor a 0.");
        
        // 3. Notificar al Director Médico
        $stmtRol = $pdo->query("SELECT id FROM roles WHERE nombre = 'Director Médico' LIMIT 1");
        $rolDir = $stmtRol->fetchColumn();
        if ($rolDir) {
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
                ->execute([$rolDir, "Nuevo pedido de Insumos: $mi_servicio", "pedidos_revision_director.php?id=" . $id_pedido]);
        }
        
        $pdo->commit();
        echo "<script>alert('Pedido enviado al Director Médico.'); window.location='dashboard.php';</script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='container mt-3'><div class='alert alert-danger'>Error: " . $e->getMessage() . "</div></div>";
    }
}

// Cargar insumos disponibles
$insumos = $pdo->query("SELECT * FROM insumos_medicos WHERE stock_actual > 0 ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Solicitud de Insumos Médicos</h1>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Estás realizando un pedido para el servicio: <strong><?php echo htmlspecialchars($mi_servicio); ?></strong>
    </div>
    
    <form method="POST">
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <span>Selección de Insumos</span>
                <span class="badge bg-white text-primary">Responsable: <?php echo $_SESSION['user_name']; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Insumo</th>
                                <th>Código</th>
                                <th class="text-center">Stock Central</th>
                                <th width="150">Cantidad a Pedir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insumos as $ins): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ins['nombre']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($ins['codigo']); ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?php echo $ins['stock_actual']; ?></span></td>
                                <td>
                                    <input type="hidden" name="items[<?php echo $ins['id']; ?>][id]" value="<?php echo $ins['id']; ?>">
                                    <input type="number" name="items[<?php echo $ins['id']; ?>][cantidad]" class="form-control form-control-sm border-primary fw-bold" min="0" placeholder="0">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end bg-white">
                <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i> Enviar Solicitud</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>