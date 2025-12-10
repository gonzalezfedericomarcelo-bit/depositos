<?php
// Archivo: admin_sistema.php
// Propósito: Panel técnico para ver estado del servidor y mantenimiento de DB

require 'db.php';
session_start();

// VERIFICACIÓN DE SEGURIDAD (Solo Admin)
$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = "";

// 1. LÓGICA DE MANTENIMIENTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'optimizar') {
    try {
        // Ejecutamos optimización en las tablas principales
        $tablas = ['usuarios', 'insumos_medicos', 'suministros_generales', 'ordenes_compra', 'entregas'];
        foreach ($tablas as $tabla) {
            $pdo->query("OPTIMIZE TABLE $tabla");
        }
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ <strong>Mantenimiento Exitoso:</strong> Las tablas han sido optimizadas y desfragmentadas.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (PDOException $e) {
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Incluir Layout
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// 2. OBTENER ESTADÍSTICAS
// Contar registros
$stats = [];
$stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$stats['insumos'] = $pdo->query("SELECT COUNT(*) FROM insumos_medicos")->fetchColumn();
$stats['suministros'] = $pdo->query("SELECT COUNT(*) FROM suministros_generales")->fetchColumn();
$stats['ordenes'] = $pdo->query("SELECT COUNT(*) FROM ordenes_compra")->fetchColumn();
$stats['entregas'] = $pdo->query("SELECT COUNT(*) FROM entregas")->fetchColumn();

// Información del Servidor
$server_time = date('d/m/Y H:i:s');
$php_version = phpversion();
$db_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Estado del Sistema</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Mantenimiento</li>
    </ol>

    <?php echo $mensaje; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-server me-1"></i> Información del Servidor
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Fecha y Hora del Servidor
                            <span class="badge bg-primary rounded-pill"><?php echo $server_time; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versión de PHP
                            <span class="badge bg-secondary rounded-pill"><?php echo $php_version; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versión de MySQL
                            <span class="badge bg-secondary rounded-pill"><?php echo $db_version; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Estado de Conexión
                            <span class="badge bg-success rounded-pill">Estable</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-tools me-1"></i> Herramientas de Base de Datos
                </div>
                <div class="card-body text-center">
                    <p class="card-text">Ejecutar mantenimiento preventivo para mejorar el rendimiento de las consultas.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="optimizar">
                        <button type="submit" class="btn btn-warning text-dark fw-bold">
                            <i class="fas fa-broom me-2"></i> Optimizar Tablas
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i> Volumen de Datos
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Usuarios</div>
                                            <div class="fs-4 fw-bold"><?php echo $stats['usuarios']; ?></div>
                                        </div>
                                        <i class="fas fa-users fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Órdenes Compra</div>
                                            <div class="fs-4 fw-bold"><?php echo $stats['ordenes']; ?></div>
                                        </div>
                                        <i class="fas fa-file-invoice fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Insumos Médicos</div>
                                            <div class="fs-4 fw-bold"><?php echo $stats['insumos']; ?></div>
                                        </div>
                                        <i class="fas fa-pills fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-secondary text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Entregas Realizadas</div>
                                            <div class="fs-4 fw-bold"><?php echo $stats['entregas']; ?></div>
                                        </div>
                                        <i class="fas fa-truck fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>