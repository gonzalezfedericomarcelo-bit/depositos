<?php
// Archivo: monitoreo_servicios.php
// Propósito: Directorio de servicios para auditar su consumo

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Validar Permiso
if (!tienePermiso('ver_monitoreo_consumo')) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>⛔ Acceso Denegado.</div></div>";
    include 'includes/footer.php'; exit;
}

// Obtener usuarios que sean "Servicio" o tengan actividad
// Filtramos usuarios activos y ordenamos alfabéticamente
$sql = "SELECT id, nombre_completo, servicio, email 
        FROM usuarios 
        WHERE activo = 1 
        AND (servicio IS NOT NULL AND servicio != '')
        ORDER BY servicio ASC, nombre_completo ASC";
$usuarios = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><i class="fas fa-search-dollar me-2"></i> Monitor de Consumo</h1>
    <p class="text-muted">Seleccione un servicio para auditar su historial de pedidos y entregas.</p>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">
            Directorio de Servicios
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Servicio / Área</th>
                            <th>Responsable (Usuario)</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <strong class="text-primary fs-5"><?php echo htmlspecialchars($u['servicio']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($u['nombre_completo']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                            </td>
                            <td class="text-center">
                                <a href="monitoreo_perfil.php?id=<?php echo $u['id']; ?>" class="btn btn-outline-primary fw-bold">
                                    <i class="fas fa-eye me-1"></i> Ver Historial
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>