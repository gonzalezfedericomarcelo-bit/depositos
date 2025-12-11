<?php
// Archivo: dashboard.php
// Propósito: Panel de Control Interactivo (Gráficos Clickeables)

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// 1. CONFIGURACIÓN DE ALCANCE
$ver_global = tienePermiso('dash_alcance_global');
$user_id = $_SESSION['user_id'];
$sql_filtro = $ver_global ? "1=1" : "p.id_usuario_solicitante = $user_id";

// 2. DATOS
// A. KPIs
$kpis = ['total'=>0, 'pendientes'=>0, 'aprobados'=>0, 'rechazados'=>0];
if (tienePermiso('dash_kpis_resumen')) {
    $sqlKPI = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado LIKE '%pendiente%' OR estado LIKE '%revision%' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado LIKE '%aprobado%' OR estado = 'esperando_entrega' THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados
               FROM pedidos_servicio p WHERE $sql_filtro";
    $kpis = $pdo->query($sqlKPI)->fetch(PDO::FETCH_ASSOC);
}

// B. Gráficos
$datos_torta = [];
$datos_barras = [];
if (tienePermiso('dash_grafico_torta') || tienePermiso('dash_grafico_barras')) {
    // Torta (Estados)
    $sqlTorta = "SELECT estado, COUNT(*) as cant FROM pedidos_servicio p WHERE $sql_filtro GROUP BY estado";
    $datos_torta = $pdo->query($sqlTorta)->fetchAll(PDO::FETCH_KEY_PAIR);

    // Barras (Meses)
    $sqlBarras = "SELECT DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes, COUNT(*) as cant 
                  FROM pedidos_servicio p 
                  WHERE $sql_filtro AND fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                  GROUP BY mes ORDER BY mes ASC";
    $datos_barras = $pdo->query($sqlBarras)->fetchAll(PDO::FETCH_ASSOC);
}

// C. Recientes
$recientes = [];
if (tienePermiso('dash_tabla_recientes')) {
    $sqlRecientes = "SELECT p.*, u.nombre_completo 
                     FROM pedidos_servicio p 
                     JOIN usuarios u ON p.id_usuario_solicitante = u.id 
                     WHERE $sql_filtro 
                     ORDER BY p.fecha_solicitud DESC LIMIT 5";
    $recientes = $pdo->query($sqlRecientes)->fetchAll();
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="fw-bold text-primary mb-0">Panel de Control</h1>
            <p class="text-muted mb-0">
                Vista: <?php echo $ver_global ? '<span class="badge bg-danger">GLOBAL</span>' : '<span class="badge bg-success">MI SERVICIO</span>'; ?>
            </p>
        </div>
    </div>

    <?php if (tienePermiso('dash_kpis_resumen')): ?>
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100 shadow-sm border-0 clickable-card" onclick="window.location='historial_pedidos.php'">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-white-50 small fw-bold">TOTAL</div><div class="display-6 fw-bold"><?php echo $kpis['total']; ?></div></div>
                    <i class="fas fa-folder-open fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark h-100 shadow-sm border-0 clickable-card" onclick="window.location='historial_pedidos.php?estado=pendiente'">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-dark-50 small fw-bold">EN PROCESO</div><div class="display-6 fw-bold"><?php echo $kpis['pendientes']; ?></div></div>
                    <i class="fas fa-clock fa-3x text-dark-50 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100 shadow-sm border-0 clickable-card" onclick="window.location='historial_pedidos.php?estado=aprobado'">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-white-50 small fw-bold">APROBADOS</div><div class="display-6 fw-bold"><?php echo $kpis['aprobados']; ?></div></div>
                    <i class="fas fa-check-circle fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100 shadow-sm border-0 clickable-card" onclick="window.location='historial_pedidos.php?estado=rechazado'">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><div class="text-white-50 small fw-bold">RECHAZADOS</div><div class="display-6 fw-bold"><?php echo $kpis['rechazados']; ?></div></div>
                    <i class="fas fa-times-circle fa-3x text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <?php if (tienePermiso('dash_grafico_barras')): ?>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Evolución Mensual (Clickeable)</div>
                <div class="card-body">
                    <canvas id="chartBarras" height="100"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (tienePermiso('dash_grafico_torta')): ?>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Estado Actual (Clickeable)</div>
                <div class="card-body">
                    <canvas id="chartTorta" height="200"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (tienePermiso('dash_tabla_recientes')): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Últimos Movimientos</span>
            <a href="historial_pedidos.php" class="btn btn-sm btn-light text-primary fw-bold">Ver Historial Completo</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr><th>ID</th><th>Fecha</th><th>Solicitante</th><th>Estado</th><th class="text-end"></th></tr>
                </thead>
                <tbody>
                    <?php if(count($recientes)>0): ?>
                        <?php foreach($recientes as $r): ?>
                        <tr class="clickable-row" onclick="window.location='bandeja_gestion_dinamica.php?id=<?php echo $r['id']; ?>'" style="cursor: pointer;">
                            <td><span class="badge bg-light text-dark border">#<?php echo $r['id']; ?></span></td>
                            <td><?php echo date('d/m H:i', strtotime($r['fecha_solicitud'])); ?></td>
                            <td><?php echo htmlspecialchars($r['nombre_completo']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $r['estado']; ?></span></td>
                            <td class="text-end"><i class="fas fa-chevron-right text-muted small"></i></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Sin movimientos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .clickable-card { cursor: pointer; transition: transform 0.2s; }
    .clickable-card:hover { transform: translateY(-5px); }
    .clickable-row:hover { background-color: #f8f9fa; }
</style>

<script>
// --- DATOS PHP A JS ---
const dataTorta = <?php echo json_encode($datos_torta); ?>;
const dataBarras = <?php echo json_encode($datos_barras); ?>;

// --- CONFIGURACIÓN DE GRÁFICOS ---

// 1. TORTA
if (document.getElementById('chartTorta')) {
    const ctxTorta = document.getElementById('chartTorta').getContext('2d');
    new Chart(ctxTorta, {
        type: 'doughnut',
        data: {
            labels: Object.keys(dataTorta).map(s => s.toUpperCase().replace(/_/g, ' ')),
            datasets: [{
                data: Object.values(dataTorta),
                backgroundColor: ['#ffc107', '#198754', '#0dcaf0', '#dc3545', '#6c757d'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    // Obtener etiqueta clicada (ej: 'PENDIENTE')
                    const index = elements[0].index;
                    const label = Object.keys(dataTorta)[index]; // Clave original (ej: 'pendiente_logistica')
                    // Redirigir al historial filtrado
                    window.location.href = `historial_pedidos.php?estado=${label}`;
                }
            }
        }
    });
}

// 2. BARRAS
if (document.getElementById('chartBarras')) {
    const ctxBarras = document.getElementById('chartBarras').getContext('2d');
    new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: dataBarras.map(d => d.mes),
            datasets: [{
                label: 'Pedidos',
                data: dataBarras.map(d => d.cant),
                backgroundColor: '#0d6efd',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } },
            onHover: (event, chartElement) => {
                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const mes = dataBarras[index].mes; // '2025-12'
                    window.location.href = `historial_pedidos.php?mes=${mes}`;
                }
            }
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>