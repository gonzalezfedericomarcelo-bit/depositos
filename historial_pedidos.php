<?php
// Archivo: historial_pedidos.php
// Propósito: Historial con Filtros Dinámicos (Compatible con Dashboard Interactivo)

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// 1. SEGURIDAD DE ACCESO (Permiso nuevo)
if (!tienePermiso('ver_historial_pedidos') && !in_array('Administrador', $_SESSION['user_roles'])) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Acceso denegado.</div></div>";
    include 'includes/footer.php'; exit;
}

// 2. CONFIGURAR FILTROS (Vienen del Dashboard)
$where = [];
$params = [];

// A. Filtro por Usuario (Si NO es global, solo ve lo suyo)
if (!tienePermiso('dash_alcance_global')) {
    $where[] = "p.id_usuario_solicitante = :uid";
    $params[':uid'] = $_SESSION['user_id'];
}

// B. Filtro por Estado (Clic en Torta o Tarjeta)
if (isset($_GET['estado']) && !empty($_GET['estado'])) {
    $estado_busqueda = $_GET['estado'];
    // Búsqueda flexible (ej: 'pendiente' busca 'pendiente_logistica', 'pendiente_director')
    $where[] = "p.estado LIKE :est";
    $params[':est'] = "%$estado_busqueda%";
}

// C. Filtro por Mes (Clic en Barras)
if (isset($_GET['mes']) && !empty($_GET['mes'])) {
    // Formato esperado: YYYY-MM
    $where[] = "DATE_FORMAT(p.fecha_solicitud, '%Y-%m') = :mes";
    $params[':mes'] = $_GET['mes'];
}

// Construir SQL
$sql = "SELECT p.*, u.nombre_completo as solicitante 
        FROM pedidos_servicio p 
        LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.fecha_solicitud DESC";

// Ejecutar
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1><i class="fas fa-list-alt"></i> Historial de Pedidos</h1>
        
        <?php if (!empty($_GET)): ?>
            <a href="historial_pedidos.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i> Quitar Filtros
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['estado'])): ?>
        <div class="alert alert-info py-2"><i class="fas fa-filter"></i> Filtrando por estado: <strong><?php echo htmlspecialchars($_GET['estado']); ?></strong></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['mes'])): ?>
        <div class="alert alert-info py-2"><i class="fas fa-calendar-alt"></i> Filtrando por mes: <strong><?php echo htmlspecialchars($_GET['mes']); ?></strong></div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Solicitante</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pedidos) > 0): ?>
                            <?php foreach($pedidos as $p): ?>
                            <tr>
                                <td class="fw-bold">#<?php echo $p['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_solicitud'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($p['servicio_solicitante']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['solicitante']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        if($p['tipo_insumo'] == 'insumos_medicos') echo '<span class="badge bg-primary">Insumo</span>';
                                        else echo '<span class="badge bg-success">Suministro</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $e = $p['estado'];
                                        $bg = 'secondary';
                                        if(strpos($e, 'pendiente') !== false) $bg = 'warning text-dark';
                                        if(strpos($e, 'aprobado') !== false) $bg = 'info text-dark';
                                        if($e == 'entregado' || $e == 'finalizado_proceso') $bg = 'success';
                                        if($e == 'rechazado') $bg = 'danger';
                                        
                                        echo "<span class='badge bg-$bg'>".strtoupper(str_replace('_', ' ', $e))."</span>";
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="pedidos_ver.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i><br>
                                    No se encontraron pedidos con estos filtros.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>