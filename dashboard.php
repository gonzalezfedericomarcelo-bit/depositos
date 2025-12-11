<?php
// Archivo: dashboard.php
// Propósito: Pantalla principal con ALERTAS REALES y KPI (Indicadores Clave)

require 'db.php';

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// --- LÓGICA KPI ORIGINAL ---
$stmtIns = $pdo->query("SELECT COUNT(*) FROM insumos_medicos WHERE stock_actual <= stock_minimo");
$alerta_insumos = $stmtIns->fetchColumn();

$stmtSum = $pdo->query("SELECT COUNT(*) FROM suministros_generales WHERE stock_actual <= stock_minimo");
$alerta_suministros = $stmtSum->fetchColumn();

$stmtOC = $pdo->query("SELECT COUNT(*) FROM ordenes_compra WHERE estado = 'pendiente_logistica'");
$pendientes_oc = $stmtOC->fetchColumn();

$sqlCriticos = "
    (SELECT 'Insumo Médico' as tipo, nombre, stock_actual, stock_minimo FROM insumos_medicos WHERE stock_actual <= stock_minimo)
    UNION
    (SELECT 'Suministro' as tipo, nombre, stock_actual, stock_minimo FROM suministros_generales WHERE stock_actual <= stock_minimo)
    ORDER BY stock_actual ASC LIMIT 5
";
$stmtCriticos = $pdo->query($sqlCriticos);
$criticos = $stmtCriticos->fetchAll();

$rol_user = $_SESSION['user_roles'][0] ?? 'Usuario';
// --- INICIO CÓDIGO NUEVO: LÓGICA DE BANDEJA DE ENTRADA ---
$tareas_pendientes = [];
$user_id = $_SESSION['user_id'];

// 1. Obtener mis Roles
$stmtMisRoles = $pdo->prepare("SELECT id_rol FROM usuario_roles WHERE id_usuario = :uid");
$stmtMisRoles->execute([':uid' => $user_id]);
$mis_roles_ids = $stmtMisRoles->fetchAll(PDO::FETCH_COLUMN);

// 2. Si soy Admin, agrego todos para ver todo (Debug)
if (in_array(1, $mis_roles_ids)) { 
    $mis_roles_ids = array_merge($mis_roles_ids, [2,3,4,5,6,7,8]); 
}

// 3. Buscar tareas donde el responsable sea MI ROL
if (!empty($mis_roles_ids)) {
    $in  = str_repeat('?,', count($mis_roles_ids) - 1) . '?';
    // Esta consulta busca pedidos que estén en un paso cuyo responsable sea UNO DE TUS ROLES
    $sqlTareas = "SELECT p.*, u.nombre_completo, cf.etiqueta_estado, cf.nombre_proceso 
                  FROM pedidos_servicio p 
                  JOIN config_flujos cf ON p.paso_actual_id = cf.id 
                  JOIN usuarios u ON p.id_usuario_solicitante = u.id 
                  WHERE cf.id_rol_responsable IN ($in) 
                  AND p.estado NOT IN ('finalizado_proceso', 'rechazado')
                  ORDER BY p.fecha_solicitud ASC";
    $stmt = $pdo->prepare($sqlTareas);
    $stmt->execute($mis_roles_ids);
    $tareas_pendientes = $stmt->fetchAll();
}

// 4. Buscar tareas donde el responsable sea YO ESPECÍFICAMENTE (Caso: Confirmación de Servicio)
$sqlTareasMias = "SELECT p.*, u.nombre_completo, cf.etiqueta_estado, cf.nombre_proceso 
                  FROM pedidos_servicio p 
                  JOIN config_flujos cf ON p.paso_actual_id = cf.id 
                  JOIN usuarios u ON p.id_usuario_solicitante = u.id 
                  WHERE cf.id_rol_responsable = 0 
                  AND p.id_usuario_solicitante = :uid
                  AND p.estado NOT IN ('finalizado_proceso', 'rechazado')";
$stmtMias = $pdo->prepare($sqlTareasMias);
$stmtMias->execute([':uid' => $user_id]);
$mis_confirmaciones = $stmtMias->fetchAll();

$tareas_pendientes = array_merge($tareas_pendientes, $mis_confirmaciones);
// --- FIN CÓDIGO NUEVO ---
// --- LÓGICA DE BANDEJA DE ENTRADA (NUEVO BLOQUE) ---
$tareas_pendientes = [];
$user_id = $_SESSION['user_id'];

// 1. Obtener mis Roles
$stmtMisRoles = $pdo->prepare("SELECT id_rol FROM usuario_roles WHERE id_usuario = :uid");
$stmtMisRoles->execute([':uid' => $user_id]);
$mis_roles_ids = $stmtMisRoles->fetchAll(PDO::FETCH_COLUMN);

// 2. Si soy Admin, agrego todos para ver todo (Debug)
if (in_array(1, $mis_roles_ids)) { 
    $mis_roles_ids = array_merge($mis_roles_ids, [2,3,4,5,6,7,8]); 
}

// 3. Buscar tareas donde el responsable sea MI ROL
if (!empty($mis_roles_ids)) {
    $in  = str_repeat('?,', count($mis_roles_ids) - 1) . '?';
    $sqlTareas = "SELECT p.*, u.nombre_completo, cf.etiqueta_estado, cf.nombre_proceso 
                  FROM pedidos_servicio p 
                  JOIN config_flujos cf ON p.paso_actual_id = cf.id 
                  JOIN usuarios u ON p.id_usuario_solicitante = u.id 
                  WHERE cf.id_rol_responsable IN ($in) 
                  AND p.estado NOT IN ('finalizado_proceso', 'rechazado')
                  ORDER BY p.fecha_solicitud ASC";
    $stmt = $pdo->prepare($sqlTareas);
    $stmt->execute($mis_roles_ids);
    $tareas_pendientes = $stmt->fetchAll();
}

// 4. Buscar tareas donde el responsable sea YO ESPECÍFICAMENTE (Caso: Confirmación de Servicio)
// Cuando id_rol_responsable en config_flujos es 0, significa "El Solicitante"
$sqlTareasMias = "SELECT p.*, u.nombre_completo, cf.etiqueta_estado, cf.nombre_proceso 
                  FROM pedidos_servicio p 
                  JOIN config_flujos cf ON p.paso_actual_id = cf.id 
                  JOIN usuarios u ON p.id_usuario_solicitante = u.id 
                  WHERE cf.id_rol_responsable = 0 
                  AND p.id_usuario_solicitante = :uid
                  AND p.estado NOT IN ('finalizado_proceso', 'rechazado')";
$stmtMias = $pdo->prepare($sqlTareasMias);
$stmtMias->execute([':uid' => $user_id]);
$mis_confirmaciones = $stmtMias->fetchAll();

$tareas_pendientes = array_merge($tareas_pendientes, $mis_confirmaciones);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="fw-bold text-primary mb-0">Panel de Control</h1>
            <p class="text-muted mb-0">Bienvenido de nuevo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
        <div>
            <span class="badge bg-light text-dark border p-2">
                <i class="fas fa-user-shield me-1"></i> <?php echo $rol_user; ?>
            </span>
        </div>
    </div>
<?php if (count($tareas_pendientes) > 0): ?>
    <div class="card mb-4 border-warning shadow-sm">
        <div class="card-header bg-warning text-dark fw-bold">
            <i class="fas fa-bell me-2"></i> Bandeja de Entrada: Tienes <?php echo count($tareas_pendientes); ?> tareas pendientes
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Solicitante</th>
                            <th>Proceso</th>
                            <th>Estado Actual</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas_pendientes as $t): ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($t['fecha_solicitud'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['servicio_solicitante']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($t['nombre_completo']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $proc = $t['nombre_proceso'];
                                        $bg = (strpos($proc, 'movimiento') !== false) ? 'bg-info text-dark' : 'bg-success';
                                        echo "<span class='badge $bg'>".ucfirst(str_replace('_', ' ', $proc))."</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($t['etiqueta_estado']); ?></td>
                                <td class="text-center">
                                    <a href="bandeja_gestion_dinamica.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-dark fw-bold">
                                        Gestionar <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($tareas_pendientes) > 0): ?>
    <div class="card mb-4 border-warning shadow-sm">
        <div class="card-header bg-warning text-dark fw-bold">
            <i class="fas fa-bell me-2"></i> Bandeja de Entrada: Tienes <?php echo count($tareas_pendientes); ?> tareas pendientes
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Solicitante</th>
                            <th>Proceso</th>
                            <th>Estado Actual</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas_pendientes as $t): ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($t['fecha_solicitud'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['servicio_solicitante']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($t['nombre_completo']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $proc = $t['nombre_proceso'];
                                        $bg = (strpos($proc, 'movimiento') !== false) ? 'bg-info text-dark' : 'bg-success';
                                        echo "<span class='badge $bg'>".ucfirst(str_replace('_', ' ', $proc))."</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($t['etiqueta_estado']); ?></td>
                                <td class="text-center">
                                    <a href="bandeja_gestion_dinamica.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-dark fw-bold">
                                        Gestionar <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-4 <?php echo ($alerta_insumos > 0) ? 'border-danger' : 'border-success'; ?> h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small fw-bold <?php echo ($alerta_insumos > 0) ? 'text-danger' : 'text-success'; ?> mb-1">
                                Insumos (Stock Bajo)
                            </div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $alerta_insumos; ?></div>
                        </div>
                        <div class="fs-1 <?php echo ($alerta_insumos > 0) ? 'text-danger opacity-25' : 'text-success opacity-25'; ?>">
                            <i class="fas fa-briefcase-medical"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-decoration-none stretched-link <?php echo ($alerta_insumos > 0) ? 'text-danger fw-bold' : 'text-muted'; ?>" href="insumos_stock.php">
                        Ver Inventario
                    </a>
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-4 <?php echo ($alerta_suministros > 0) ? 'border-danger' : 'border-success'; ?> h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small fw-bold <?php echo ($alerta_suministros > 0) ? 'text-danger' : 'text-success'; ?> mb-1">
                                Suministros (Bajo)
                            </div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $alerta_suministros; ?></div>
                        </div>
                        <div class="fs-1 <?php echo ($alerta_suministros > 0) ? 'text-danger opacity-25' : 'text-success opacity-25'; ?>">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-decoration-none stretched-link <?php echo ($alerta_suministros > 0) ? 'text-danger fw-bold' : 'text-muted'; ?>" href="suministros_stock.php">
                        Ver Inventario
                    </a>
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-4 <?php echo ($pendientes_oc > 0) ? 'border-warning' : 'border-primary'; ?> h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small fw-bold <?php echo ($pendientes_oc > 0) ? 'text-warning' : 'text-primary'; ?> mb-1">
                                Compras Pendientes
                            </div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $pendientes_oc; ?></div>
                        </div>
                        <div class="fs-1 text-warning opacity-25">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-decoration-none stretched-link text-muted" href="insumos_compras.php">Revisar Órdenes</a>
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase small fw-bold text-info mb-1">Nueva Salida</div>
                            <div class="small text-muted">Registrar Entrega</div>
                        </div>
                        <div class="fs-1 text-info opacity-25">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between small">
                    <a class="text-decoration-none stretched-link text-info fw-bold" href="insumos_entregas.php">Ir a Entregas</a>
                    <i class="fas fa-angle-right"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <i class="fas fa-exclamation-circle text-danger me-1"></i> 
                    <strong>Reposición Urgente</strong> (Top 5 Críticos)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Producto</th>
                                    <th class="text-center">Stock Real</th>
                                    <th class="text-center">Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($criticos) > 0): ?>
                                    <?php foreach ($criticos as $c): ?>
                                        <tr>
                                            <td>
                                                <?php if($c['tipo'] == 'Insumo Médico'): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">Insumo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success">Suministro</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-500"><?php echo htmlspecialchars($c['nombre']); ?></td>
                                            <td class="text-center fw-bold text-danger"><?php echo $c['stock_actual']; ?></td>
                                            <td class="text-center text-muted"><?php echo $c['stock_minimo']; ?></td>
                                            <td><span class="badge bg-danger">Crítico</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-success">
                                            <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                            ¡Todo en orden! No hay alertas de stock.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <i class="fas fa-bolt text-warning me-1"></i> Acciones Rápidas
                </div>
                <div class="list-group list-group-flush">
                    <a href="insumos_stock.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-search me-2 text-muted"></i> Consultar Stock Médico</div>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>
                    <a href="suministros_oc_crear.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-cart-plus me-2 text-muted"></i> Comprar Suministros</div>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-signature me-2 text-muted"></i> Actualizar mi Firma</div>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>
                    <?php if ($rol_user == 'Administrador'): ?>
                    <a href="admin_usuarios.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center bg-light">
                        <div class="text-primary"><i class="fas fa-users-cog me-2"></i> Gestión Usuarios</div>
                        <i class="fas fa-chevron-right small text-primary"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>