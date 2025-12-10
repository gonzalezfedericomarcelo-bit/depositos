<?php
// Archivo: insumos_compras.php
// Propósito: Listado de Órdenes de Compra de Insumos Médicos

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// VERIFICACIÓN DE PERMISOS
// Solo Compras y Admin pueden crear. Logística solo aprueba.
// Usamos la función hasRole que definimos en sidebar.php (necesitamos volver a declararla o incluirla si no es global, 
// pero como sidebar se incluye, la lógica está disponible visualmente, pero para lógica de PHP pura
// es mejor re-verificar o usar una clase helper. Por simplicidad en este paso a paso, repetimos la lógica simple).
$roles_usuario = $_SESSION['user_roles'] ?? [];
$puede_crear = in_array('Administrador', $roles_usuario) || in_array('Compras', $roles_usuario);

// CONSULTA
// Traemos las OCs solo de tipo 'insumos', uniendo con la tabla de usuarios para saber quién la creó
$sql = "SELECT oc.*, u.nombre_completo as creador 
        FROM ordenes_compra oc 
        JOIN usuarios u ON oc.id_usuario_creador = u.id 
        WHERE oc.tipo_origen = 'insumos' 
        ORDER BY oc.fecha_creacion DESC";
$stmt = $pdo->query($sql);
$ordenes = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Órdenes de Compra (Insumos)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Compras</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-file-invoice-dollar me-1"></i> Historial de Órdenes</div>
            
            <?php if ($puede_crear): ?>
                <a href="insumos_oc_crear.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva Orden
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>N° OC (Papel)</th>
                            <th>Fecha</th>
                            <th>Creado Por</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ordenes) > 0): ?>
                            <?php foreach ($ordenes as $oc): ?>
                                <?php 
                                    // Colores según estado
                                    $badge_class = 'bg-secondary';
                                    $estado_texto = ucfirst(str_replace('_', ' ', $oc['estado']));
                                    
                                    switch($oc['estado']) {
                                        case 'pendiente_logistica': $badge_class = 'bg-warning text-dark'; break;
                                        case 'aprobada_logistica': $badge_class = 'bg-info text-dark'; break;
                                        case 'rechazada': $badge_class = 'bg-danger'; break;
                                        case 'recibida_total': $badge_class = 'bg-success'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $oc['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($oc['numero_oc']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($oc['fecha_creacion'])); ?></td>
                                    <td><?php echo htmlspecialchars($oc['creador']); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $estado_texto; ?></span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="insumos_oc_ver.php?id=<?php echo $oc['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                                    No hay órdenes de compra registradas.
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