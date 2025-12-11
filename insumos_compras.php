<?php
// Archivo: insumos_compras.php
// Propósito: Listado INTELIGENTE de OC Insumos

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// DETECCIÓN DE PERMISOS
$ver_todas = tienePermiso('ver_oc_insumos_todas');
$ver_propias = tienePermiso('ver_oc_insumos_propias');

if (!$ver_todas && !$ver_propias) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>⛔ Acceso Denegado a Órdenes de Insumos.</div></div>";
    include 'includes/footer.php'; exit;
}

// CONSTRUCCIÓN DE CONSULTA SEGÚN PERMISO
$sql = "SELECT oc.*, u.nombre_completo as creador 
        FROM ordenes_compra oc 
        JOIN usuarios u ON oc.id_usuario_creador = u.id 
        WHERE oc.tipo_origen = 'insumos'";

// Si NO puede ver todas, filtramos por su servicio
if (!$ver_todas && $ver_propias) {
    $mi_servicio = $_SESSION['user_data']['servicio'] ?? '---';
    $sql .= " AND oc.servicio_destino = '$mi_servicio'";
}

$sql .= " ORDER BY oc.fecha_creacion DESC";

$ordenes = $pdo->query($sql)->fetchAll();
$puede_crear = tienePermiso('crear_oc_insumos');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Órdenes de Compra (Insumos)</h1>
    
    <?php if (!$ver_todas): ?>
        <div class="alert alert-info py-2 small"><i class="fas fa-filter"></i> Mostrando solo órdenes para: <strong><?php echo $_SESSION['user_data']['servicio']; ?></strong></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-file-medical me-1"></i> Historial</div>
            <?php if ($puede_crear): ?>
                <a href="insumos_oc_crear.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nueva Orden</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>N° OC</th>
                            <th>Destino</th> <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ordenes) > 0): ?>
                            <?php foreach ($ordenes as $oc): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($oc['numero_oc']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($oc['servicio_destino'] ?? 'General'); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($oc['fecha_creacion'])); ?></td>
                                    <td><?php echo $oc['estado']; ?></td>
                                    <td>
                                        <a href="insumos_oc_ver.php?id=<?php echo $oc['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No hay órdenes visibles.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>