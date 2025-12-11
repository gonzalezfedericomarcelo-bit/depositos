<?php
// Archivo: pedidos_ver.php
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

if (!isset($_GET['id'])) die("Falta ID");
$id = $_GET['id'];

// CONSULTA ROBUSTA (A prueba de fallos de configuración)
$sql = "SELECT p.*, u.nombre_completo as solicitante, cf.etiqueta_estado as nombre_paso_actual
        FROM pedidos_servicio p 
        LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id 
        LEFT JOIN config_flujos cf ON p.paso_actual_id = cf.id 
        WHERE p.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$pedido = $stmt->fetch();

if (!$pedido) die("<div class='alert alert-danger m-4'>El pedido no existe o fue eliminado.</div>");

// Items
$sqlItems = "SELECT pi.*, 
            COALESCE(im.nombre, sg.nombre) as producto
            FROM pedidos_items pi 
            LEFT JOIN insumos_medicos im ON pi.id_insumo = im.id 
            LEFT JOIN suministros_generales sg ON pi.id_suministro = sg.id 
            WHERE pi.id_pedido = :id";
$stmtItems = $pdo->prepare($sqlItems);
$stmtItems->execute([':id' => $id]);
$items = $stmtItems->fetchAll();

// Adjuntos
$adjuntos = $pdo->query("SELECT * FROM adjuntos WHERE entidad_tipo='pedido_servicio' AND id_entidad=$id")->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h3>Detalle Solicitud #<?php echo $pedido['id']; ?></h3>
        <a href="historial_pedidos.php" class="btn btn-secondary">Volver</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">Estado</div>
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo strtoupper(str_replace('_',' ',$pedido['estado'])); ?></h5>
                    <?php if($pedido['nombre_paso_actual']): ?>
                        <p class="card-text text-muted small">Paso actual: <?php echo $pedido['nombre_paso_actual']; ?></p>
                    <?php endif; ?>
                    <hr>
                    <p class="mb-1"><strong>Solicitante:</strong> <?php echo htmlspecialchars($pedido['solicitante']); ?></p>
                    <p class="mb-1"><strong>Servicio:</strong> <?php echo htmlspecialchars($pedido['servicio_solicitante']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($pedido['fecha_solicitud'])); ?></p>
                </div>
            </div>
            
            <?php if(count($adjuntos) > 0): ?>
            <div class="card mb-3 border-success">
                <div class="card-header bg-success text-white">Documentos</div>
                <div class="card-body">
                    <?php foreach($adjuntos as $a): ?>
                        <a href="<?php echo $a['ruta_archivo']; ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 mb-1">
                            <i class="fas fa-download"></i> <?php echo $a['nombre_original']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Ítems</div>
                <table class="table mb-0">
                    <thead><tr><th>Producto</th><th>Cant. Pedida</th><th>Cant. Aprobada</th></tr></thead>
                    <tbody>
                        <?php foreach($items as $i): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($i['producto']); ?></td>
                            <td><?php echo $i['cantidad_solicitada']; ?></td>
                            <td class="fw-bold"><?php echo $i['cantidad_aprobada'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>