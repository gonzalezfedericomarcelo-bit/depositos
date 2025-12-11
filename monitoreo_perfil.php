<?php
// Archivo: monitoreo_perfil.php
// Propósito: Ver historial unificado (Pedidos + Entregas Manuales) de un servicio

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

if (!tienePermiso('ver_monitoreo_consumo')) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Acceso Denegado.</div></div>";
    include 'includes/footer.php'; exit;
}

$id_usuario = $_GET['id'] ?? 0;
$usuario = $pdo->query("SELECT * FROM usuarios WHERE id = $id_usuario")->fetch();

if (!$usuario) die("Usuario no encontrado");

// --- CONSULTA UNIFICADA INTELIGENTE ---
// Buscamos ítems de PEDIDOS (Sistematizados) Y de ENTREGAS (Manuales)
// Usamos UNION ALL para juntar ambos mundos

$nombre_servicio = $usuario['servicio']; // Para buscar en entregas manuales

$sql = "
    /* 1. PEDIDOS DE SISTEMA (Insumos) */
    SELECT 
        im.nombre as producto, 
        'Insumo Médico' as tipo,
        pi.cantidad_entregada as cantidad,
        p.fecha_entrega_real as fecha,
        p.frecuencia_compra as frecuencia,
        CONCAT('Pedido #', p.id) as origen
    FROM pedidos_items pi
    JOIN pedidos_servicio p ON pi.id_pedido = p.id
    JOIN insumos_medicos im ON pi.id_insumo = im.id
    WHERE p.id_usuario_solicitante = :uid AND p.estado = 'entregado'

    UNION ALL

    /* 2. PEDIDOS DE SISTEMA (Suministros) */
    SELECT 
        sg.nombre as producto, 
        'Suministro' as tipo,
        pi.cantidad_entregada as cantidad,
        p.fecha_entrega_real as fecha,
        p.frecuencia_compra as frecuencia,
        CONCAT('Pedido #', p.id) as origen
    FROM pedidos_items pi
    JOIN pedidos_servicio p ON pi.id_pedido = p.id
    JOIN suministros_generales sg ON pi.id_suministro = sg.id
    WHERE p.id_usuario_solicitante = :uid AND p.estado = 'entregado'

    UNION ALL

    /* 3. ENTREGAS MANUALES (Insumos) - Buscamos por nombre de servicio */
    SELECT 
        im.nombre as producto,
        'Insumo Médico' as tipo,
        ei.cantidad as cantidad,
        e.fecha_entrega as fecha,
        '---' as frecuencia,
        CONCAT('Entrega Manual #', e.id) as origen
    FROM entregas_items ei
    JOIN entregas e ON ei.id_entrega = e.id
    JOIN insumos_medicos im ON ei.id_insumo = im.id
    WHERE e.tipo_origen = 'insumos' AND e.solicitante_area = :serv

    UNION ALL

    /* 4. ENTREGAS MANUALES (Suministros) */
    SELECT 
        sg.nombre as producto,
        'Suministro' as tipo,
        ei.cantidad as cantidad,
        e.fecha_entrega as fecha,
        '---' as frecuencia,
        CONCAT('Entrega Manual #', e.id) as origen
    FROM entregas_items ei
    JOIN entregas e ON ei.id_entrega = e.id
    JOIN suministros_generales sg ON ei.id_suministro = sg.id
    WHERE e.tipo_origen = 'suministros' AND e.solicitante_area = :serv

    ORDER BY fecha DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $id_usuario, ':serv' => $nombre_servicio]);
$historial = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <div>
            <h1 class="text-primary"><?php echo htmlspecialchars($usuario['servicio']); ?></h1>
            <p class="text-muted mb-0">Responsable: <?php echo htmlspecialchars($usuario['nombre_completo']); ?></p>
        </div>
        <a href="monitoreo_servicios.php" class="btn btn-secondary">Volver al Listado</a>
    </div>

    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-dark fw-bold">
            <i class="fas fa-history me-2"></i> Historial de Consumo (Últimos movimientos)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha Entrega</th>
                            <th>Días Transcurridos</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad Entregada</th>
                            <th>Frecuencia (Promesa)</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($historial) > 0): ?>
                            <?php foreach ($historial as $row): ?>
                                <?php
                                    // Calcular días transcurridos
                                    $fecha_entrega = new DateTime($row['fecha']);
                                    $hoy = new DateTime();
                                    $dias = $fecha_entrega->diff($hoy)->days;
                                    
                                    // Alerta visual si pidió hace poco (ej: menos de 30 días)
                                    $badge_dias = ($dias < 30) ? 'bg-success' : 'bg-secondary';
                                    
                                    // Icono tipo
                                    $icono = ($row['tipo'] == 'Insumo Médico') 
                                        ? '<i class="fas fa-pills text-primary" title="Insumo"></i>' 
                                        : '<i class="fas fa-boxes text-success" title="Suministro"></i>';
                                ?>
                                <tr>
                                    <td><?php echo $fecha_entrega->format('d/m/Y'); ?></td>
                                    <td><span class="badge <?php echo $badge_dias; ?>"><?php echo $dias; ?> días atrás</span></td>
                                    <td><?php echo $icono . ' ' . htmlspecialchars($row['producto']); ?></td>
                                    <td class="text-center fw-bold fs-5"><?php echo $row['cantidad']; ?></td>
                                    <td>
                                        <?php if($row['frecuencia'] && $row['frecuencia'] != '---'): ?>
                                            <span class="badge bg-warning text-dark border border-dark"><?php echo $row['frecuencia']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">No especificada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo $row['origen']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                    Este servicio no tiene historial de entregas registrado.
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