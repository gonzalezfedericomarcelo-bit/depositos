<?php
// Archivo: historial_pedidos.php
// Propósito: Historial completo y transparente de solicitudes
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$user_id = $_SESSION['user_id'];
$roles = $_SESSION['user_roles']; // Array de nombres de roles
$rol_servicio = $_SESSION['user_data']['rol_en_servicio'] ?? '';

// LOGICA DE PERMISOS CORREGIDA
// ¿Quién puede ver TODO el historial?
$ver_todo = false;
$roles_jerarquicos = ['Administrador', 'Director Médico', 'Director Operativo', 'Encargado Logística', 'Compras', 'Encargado Depósito Insumos', 'Encargado Depósito Suministros'];

foreach ($roles_jerarquicos as $r) {
    if (in_array($r, $roles)) {
        $ver_todo = true;
        break;
    }
}

// Filtro SQL
$where = "";
if (!$ver_todo) {
    // Si es un usuario normal de servicio, solo ve lo que él pidió
    $where = "WHERE p.id_usuario_solicitante = $user_id";
}

// Consulta blindada (LEFT JOIN para que no desaparezcan si se borra un paso de config)
$sql = "SELECT p.*, u.nombre_completo as solicitante 
        FROM pedidos_servicio p 
        LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id 
        $where 
        ORDER BY p.id DESC";

$pedidos = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1><i class="fas fa-history"></i> Historial de Pedidos</h1>
        <?php if($ver_todo): ?>
            <span class="badge bg-dark">Vista Gerencial (Todos los pedidos)</span>
        <?php else: ?>
            <span class="badge bg-secondary">Mis Solicitudes</span>
        <?php endif; ?>
    </div>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Solicitante / Servicio</th>
                            <th>Tipo</th>
                            <th>Estado Actual</th>
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
                                    <strong><?php echo htmlspecialchars($p['servicio_solicitante']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['solicitante']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        if($p['tipo_insumo'] == 'insumos_medicos') echo '<span class="badge bg-primary">Médico</span>';
                                        else echo '<span class="badge bg-success">Suministros</span>';
                                        
                                        // Diferenciar Compra vs Interno
                                        if(strpos($p['proceso_origen'], 'adquisicion') !== false) {
                                            echo ' <span class="badge border text-dark ms-1">Compra</span>';
                                        } else {
                                            echo ' <span class="badge border text-dark ms-1">Interno</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $e = $p['estado'];
                                        $color = 'secondary';
                                        if(strpos($e, 'pendiente') !== false) $color = 'warning text-dark';
                                        if(strpos($e, 'aprobado') !== false) $color = 'info text-dark';
                                        if($e == 'entregado' || $e == 'finalizado_proceso' || $e == 'esperando_entrega') $color = 'success';
                                        if($e == 'rechazado') $color = 'danger';
                                        
                                        echo "<span class='badge bg-$color'>".strtoupper(str_replace('_', ' ', $e))."</span>";
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="pedidos_ver.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No se encontraron registros en el historial.
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