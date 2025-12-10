<?php
// Archivo: admin_auditoria.php
// Propósito: Ver quién modificó el stock manualmente y generar reporte

require 'db.php';
session_start();

// VERIFICACIÓN DE SEGURIDAD (Solo Admin)
$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    die("<h1>⛔ Acceso Restringido</h1><p>Solo el Administrador puede ver la auditoría.</p>");
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// CONSULTA MAESTRA
// Unimos la tabla de historial con Usuarios, Insumos y Suministros para obtener los nombres reales
$sql = "
    SELECT h.*, u.nombre_completo as usuario,
           im.nombre as nombre_insumo,
           sg.nombre as nombre_suministro
    FROM historial_ajustes h
    JOIN usuarios u ON h.id_usuario = u.id
    LEFT JOIN insumos_medicos im ON h.tipo_origen = 'insumo' AND h.id_item = im.id
    LEFT JOIN suministros_generales sg ON h.tipo_origen = 'suministro' AND h.id_item = sg.id
    ORDER BY h.fecha_cambio DESC
";
$stmt = $pdo->query($sql);
$historial = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Auditoría de Stock</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Seguridad</li>
    </ol>

    <div class="alert alert-warning">
        <i class="fas fa-shield-alt"></i> <strong>Registro de Seguridad:</strong> Aquí aparecen todas las modificaciones manuales de stock (correcciones de conteo) realizadas por los usuarios.
    </div>

    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <div><i class="fas fa-history me-1"></i> Historial de Cambios</div>
            <a href="generar_pdf_auditoria.php" target="_blank" class="btn btn-light btn-sm text-danger fw-bold">
                <i class="fas fa-file-pdf me-2"></i> Descargar Reporte PDF
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Usuario Responsable</th>
                            <th>Producto Afectado</th>
                            <th>Tipo</th>
                            <th class="text-center">Antes</th>
                            <th class="text-center">Después</th>
                            <th class="text-center">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historial) > 0): ?>
                            <?php foreach ($historial as $row): ?>
                                <?php 
                                    // Determinar nombre del producto y tipo visual
                                    $producto = ($row['tipo_origen'] == 'insumo') ? $row['nombre_insumo'] : $row['nombre_suministro'];
                                    $etiqueta = ($row['tipo_origen'] == 'insumo') ? '<span class="badge bg-primary">Médico</span>' : '<span class="badge bg-success">Suministro</span>';
                                    
                                    // Calcular diferencia
                                    $diferencia = $row['stock_nuevo'] - $row['stock_anterior'];
                                    $color_diff = ($diferencia > 0) ? 'text-success' : 'text-danger';
                                    $signo = ($diferencia > 0) ? '+' : '';
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_cambio'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($producto); ?></td>
                                    <td class="text-center"><?php echo $etiqueta; ?></td>
                                    <td class="text-center text-muted"><?php echo $row['stock_anterior']; ?></td>
                                    <td class="text-center fw-bold"><?php echo $row['stock_nuevo']; ?></td>
                                    <td class="text-center fw-bold <?php echo $color_diff; ?>">
                                        <?php echo $signo . $diferencia; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay incidentes registrados. El stock no ha sido modificado manualmente.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>