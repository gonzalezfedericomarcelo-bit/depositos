<?php
// Archivo: suministros_oc_ver.php
// Propósito: Ver detalles, Aprobar (LOGÍSTICA) y notificar.

require 'db.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: suministros_compras.php");
    exit;
}
$id_oc = $_GET['id'];
$roles_usuario = $_SESSION['user_roles'] ?? [];
$mensaje = "";

// 2. LÓGICA DE APROBACIÓN (LOGÍSTICA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    
    // CAMBIO CLAVE: Solo Logística o Admin
    if (in_array('Encargado Logística', $roles_usuario) || in_array('Administrador', $roles_usuario)) {
        try {
            // Obtener creador
            $stmtOwner = $pdo->prepare("SELECT id_usuario_creador, numero_oc FROM ordenes_compra WHERE id = :id");
            $stmtOwner->execute(['id' => $id_oc]);
            $datosOC = $stmtOwner->fetch();

            if (!$datosOC) throw new Exception("Orden no encontrada.");

            $nuevo_estado = ($_POST['accion'] == 'aprobar') ? 'aprobada_logistica' : 'rechazada';
            
            $pdo->beginTransaction();

            // A. Actualizar Estado
            $stmtUpdate = $pdo->prepare("UPDATE ordenes_compra SET estado = :estado, id_usuario_aprobador = :user, fecha_aprobacion = NOW() WHERE id = :id");
            $stmtUpdate->execute([':estado' => $nuevo_estado, ':user' => $_SESSION['user_id'], ':id' => $id_oc]);

            // B. NOTIFICAR AL CREADOR (COMPRAS)
            $msj_creador = ($nuevo_estado == 'aprobada_logistica') 
                ? "✅ Logística APROBÓ la OC Suministros #{$datosOC['numero_oc']}." 
                : "❌ Logística RECHAZÓ la OC Suministros #{$datosOC['numero_oc']}.";
            
            $stmtNotiUser = $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (:uid, :msj, :url)");
            $stmtNotiUser->execute([
                ':uid' => $datosOC['id_usuario_creador'], 
                ':msj' => $msj_creador,
                ':url' => "suministros_oc_ver.php?id=" . $id_oc
            ]);

            // C. NOTIFICAR AL DEPÓSITO DE SUMINISTROS (Solo si aprueba)
            if ($nuevo_estado == 'aprobada_logistica') {
                $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Encargado Depósito Suministros' LIMIT 1");
                $stmtRol->execute();
                $rolDeposito = $stmtRol->fetchColumn();

                if ($rolDeposito) {
                    $stmtNotiDepo = $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (:rol, :msj, :url)");
                    $stmtNotiDepo->execute([
                        ':rol' => $rolDeposito,
                        ':msj' => "Logística autorizó carga. OC #{$datosOC['numero_oc']}",
                        ':url' => "suministros_recepcion.php?id=" . $id_oc
                    ]);
                }
            }

            $pdo->commit();
            $mensaje = '<div class="alert alert-success">✅ Estado actualizado a: <strong>'.strtoupper($nuevo_estado).'</strong></div>';

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger">⛔ Solo Logística puede aprobar suministros.</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Consultas de Datos
$stmt = $pdo->prepare("SELECT oc.*, u_creador.nombre_completo as creador, u_aprob.nombre_completo as aprobador FROM ordenes_compra oc JOIN usuarios u_creador ON oc.id_usuario_creador = u_creador.id LEFT JOIN usuarios u_aprob ON oc.id_usuario_aprobador = u_aprob.id WHERE oc.id = :id");
$stmt->execute(['id' => $id_oc]);
$orden = $stmt->fetch();

if (!$orden) { echo "<div class='container mt-5 alert alert-danger'>No encontrada.</div>"; include 'includes/footer.php'; exit; }

$stmtItems = $pdo->prepare("SELECT * FROM ordenes_compra_items WHERE id_oc = :id");
$stmtItems->execute(['id' => $id_oc]);
$items = $stmtItems->fetchAll();

$stmtAdj = $pdo->prepare("SELECT * FROM adjuntos WHERE entidad_tipo = 'orden_compra' AND id_entidad = :id");
$stmtAdj->execute(['id' => $id_oc]);
$adjuntos = $stmtAdj->fetchAll();

$total_estimado = 0;
foreach($items as $i) $total_estimado += ($i['cantidad_solicitada'] * $i['precio_estimado']);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h1>OC Suministros #<?php echo htmlspecialchars($orden['numero_oc']); ?></h1>
        <a href="suministros_compras.php" class="btn btn-secondary btn-sm">Volver</a>
    </div>
    
    <?php echo $mensaje; ?>

    <div class="alert <?php echo ($orden['estado'] == 'pendiente_logistica') ? 'alert-warning' : (($orden['estado'] == 'rechazada') ? 'alert-danger' : 'alert-success'); ?> d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <strong>Estado:</strong> <?php echo strtoupper(str_replace('_', ' ', $orden['estado'])); ?>
            <?php if($orden['aprobador']): ?><br><small>Por: <?php echo htmlspecialchars($orden['aprobador']); ?></small><?php endif; ?>
        </div>
        
        <?php if ($orden['estado'] == 'pendiente_logistica' && (in_array('Encargado Logística', $roles_usuario) || in_array('Administrador', $roles_usuario))): ?>
        <div>
            <form method="POST" class="d-inline" onsubmit="return confirm('¿Rechazar?');">
                <input type="hidden" name="accion" value="rechazar">
                <button type="submit" class="btn btn-danger me-2">Rechazar</button>
            </form>
            <form method="POST" class="d-inline" onsubmit="return confirm('¿Aprobar?');">
                <input type="hidden" name="accion" value="aprobar">
                <button type="submit" class="btn btn-success">Aprobar</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (($orden['estado'] == 'aprobada_logistica' || $orden['estado'] == 'recibida_parcial') && (in_array('Encargado Depósito Suministros', $roles_usuario) || in_array('Administrador', $roles_usuario))): ?>
            <a href="suministros_recepcion.php?id=<?php echo $orden['id']; ?>" class="btn btn-success shadow">Recibir Suministros</a>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">Datos</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><small>Solicitante:</small> <?php echo htmlspecialchars($orden['creador']); ?></li>
                    <li class="list-group-item"><small>Fecha:</small> <?php echo date('d/m/Y', strtotime($orden['fecha_creacion'])); ?></li>
                    <li class="list-group-item"><small>Monto Est.:</small> $ <?php echo number_format($total_estimado, 2); ?></li>
                    <li class="list-group-item"><small>Obs:</small> <?php echo nl2br(htmlspecialchars($orden['observaciones'] ?? '')); ?></li>
                </ul>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">Adjuntos</div>
                <div class="card-body">
                    <?php if (count($adjuntos) > 0): ?>
                        <?php foreach ($adjuntos as $adj): ?>
                            <div class="mb-1"><a href="<?php echo $adj['ruta_archivo']; ?>" target="_blank"><i class="fas fa-paperclip me-1"></i> <?php echo $adj['nombre_original']; ?></a></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted small">Sin adjuntos.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">Ítems</div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Precio</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['descripcion_producto']); ?></td>
                                    <td class="text-center"><?php echo $item['cantidad_solicitada']; ?></td>
                                    <td class="text-end">$ <?php echo number_format($item['precio_estimado'], 2); ?></td>
                                    <td class="text-end fw-bold">$ <?php echo number_format($item['cantidad_solicitada'] * $item['precio_estimado'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>