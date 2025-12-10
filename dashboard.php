<?php
// Archivo: dashboard.php
// Propósito: Pantalla principal con ALERTAS REALES y KPI (Indicadores Clave)

require 'db.php';

// Incluimos la estructura visual (que ya tiene la sesión iniciada)
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// --- LÓGICA: OBTENER DATOS REALES ---

// 1. Contar Insumos Médicos con Stock Bajo o Crítico
$stmtIns = $pdo->query("SELECT COUNT(*) FROM insumos_medicos WHERE stock_actual <= stock_minimo");
$alerta_insumos = $stmtIns->fetchColumn();

// 2. Contar Suministros con Stock Bajo
$stmtSum = $pdo->query("SELECT COUNT(*) FROM suministros_generales WHERE stock_actual <= stock_minimo");
$alerta_suministros = $stmtSum->fetchColumn();

// 3. Contar Órdenes Pendientes de Aprobación (Logística)
$stmtOC = $pdo->query("SELECT COUNT(*) FROM ordenes_compra WHERE estado = 'pendiente_logistica'");
$pendientes_oc = $stmtOC->fetchColumn();

// 4. Obtener listado rápido de artículos críticos (Mix de ambos mundos)
// Usamos UNION para juntar insumos y suministros en una sola tablita de alerta
$sqlCriticos = "
    (SELECT 'Insumo Médico' as tipo, nombre, stock_actual, stock_minimo FROM insumos_medicos WHERE stock_actual <= stock_minimo)
    UNION
    (SELECT 'Suministro' as tipo, nombre, stock_actual, stock_minimo FROM suministros_generales WHERE stock_actual <= stock_minimo)
    ORDER BY stock_actual ASC LIMIT 5
";
$stmtCriticos = $pdo->query($sqlCriticos);
$criticos = $stmtCriticos->fetchAll();

// Roles para personalizar mensaje
$rol_user = $_SESSION['user_roles'][0] ?? 'Usuario';
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