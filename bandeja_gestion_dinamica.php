<?php
// Archivo: bandeja_gestion_dinamica.php
// Propósito: Motor único que gestiona CUALQUIER paso de CUALQUIER flujo (Con carga de OC y notificaciones masivas)
require 'db.php';
session_start();
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$id_pedido = $_GET['id'];
$user_id = $_SESSION['user_id'];
$roles_usuario = $_SESSION['user_roles'];

// 1. Obtener Pedido y Paso Actual
$sql = "SELECT p.*, cf.nombre_estado, cf.etiqueta_estado, cf.id_rol_responsable, cf.paso_orden, cf.requiere_firma, cf.nombre_proceso 
        FROM pedidos_servicio p 
        JOIN config_flujos cf ON p.paso_actual_id = cf.id 
        WHERE p.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) die("<div class='alert alert-danger m-4'>Error: El pedido no tiene un flujo activo o no existe.</div>");

// 2. Verificar Permisos
$puede_gestionar = false;
$stmtCheckRol = $pdo->prepare("SELECT * FROM usuario_roles WHERE id_usuario = :uid AND id_rol = :rid");
$stmtCheckRol->execute([':uid'=>$user_id, ':rid'=>$pedido['id_rol_responsable']]);
if ($stmtCheckRol->fetch() || in_array('Administrador', $roles_usuario)) {
    $puede_gestionar = true;
}

// Verificar si es el PASO FINAL DE ADQUISICIÓN (Compras)
// (Para mostrar el input de archivo)
$es_fin_adquisicion = false;
// Chequeamos si hay un paso siguiente
$stmtNextCheck = $pdo->prepare("SELECT id FROM config_flujos WHERE nombre_proceso = :proc AND paso_orden > :ord");
$stmtNextCheck->execute([':proc'=>$pedido['nombre_proceso'], ':ord'=>$pedido['paso_orden']]);
if ($stmtNextCheck->rowCount() == 0 && strpos($pedido['nombre_proceso'], 'adquisicion') !== false) {
    $es_fin_adquisicion = true;
}

// 3. PROCESAR ACCIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $puede_gestionar) {
    try {
        $pdo->beginTransaction();

        // A. Guardar cambios en cantidades (Si aplica)
        if (isset($_POST['cant_aprobada'])) {
            foreach ($_POST['cant_aprobada'] as $id_item => $cant) {
                $pdo->prepare("UPDATE pedidos_items SET cantidad_aprobada = :c WHERE id = :id")->execute([':c'=>$cant, ':id'=>$id_item]);
            }
        }

        // B. Buscar SIGUIENTE PASO en la DB
        $stmtNext = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = :proc AND paso_orden > :ord ORDER BY paso_orden ASC LIMIT 1");
        $stmtNext->execute([':proc'=>$pedido['nombre_proceso'], ':ord'=>$pedido['paso_orden']]);
        $siguiente = $stmtNext->fetch();
        
        if ($siguiente) {
            // ---> HAY UN PASO SIGUIENTE (Avanza el flujo)
            $sqlUpd = "UPDATE pedidos_servicio SET estado = :est, paso_actual_id = :pid WHERE id = :id";
            $pdo->prepare($sqlUpd)->execute([':est'=>$siguiente['nombre_estado'], ':pid'=>$siguiente['id'], ':id'=>$id_pedido]);
            
            // Notificar al siguiente responsable
            $msj = "Solicitud #$id_pedido requiere tu revisión (" . $siguiente['etiqueta_estado'] . ")";
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?,?,?)")
                ->execute([$siguiente['id_rol_responsable'], $msj, "bandeja_gestion_dinamica.php?id=$id_pedido"]);
            
            // Caso especial: Si vuelve del Director al Encargado (en movimientos)
            if ($pedido['nombre_estado'] == 'aprobacion_director' && $pedido['nombre_proceso'] == 'movimiento_insumos') {
                $rolEncargado = 4; 
                $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?,?,?)")
                    ->execute([$rolEncargado, "El Director aprobó el pedido #$id_pedido. Vuelve a ti.", "bandeja_gestion_dinamica.php?id=$id_pedido"]);
            }
            
            $msg_final = "Aprobado. El expediente avanzó a: " . $siguiente['etiqueta_estado'];

        } else {
            // ---> NO HAY PASO SIGUIENTE (FIN DEL PROCESO)
            
            if ($pedido['nombre_proceso'] == 'movimiento_insumos' || $pedido['nombre_proceso'] == 'movimiento_suministros') {
                // ... (Lógica de Movimiento Interno - Igual que antes) ...
                $sqlUpd = "UPDATE pedidos_servicio SET estado = 'listo_para_retirar', paso_actual_id = NULL WHERE id = :id";
                $pdo->prepare($sqlUpd)->execute([':id'=>$id_pedido]);
                
                $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (?,?,?)")
                    ->execute([$pedido['id_usuario_solicitante'], "✅ Tus insumos (Pedido #$id_pedido) están LISTOS PARA RETIRAR.", "dashboard.php"]);
                
                $msg_final = "Proceso finalizado. Se notificó al servicio para retirar.";
                
            } else {
                // ---> FINAL DE ADQUISICIÓN (COMPRAS) <---
                
                // 1. Validar Archivo
                if (empty($_FILES['orden_compra']['name'])) {
                    throw new Exception("Es OBLIGATORIO adjuntar la Orden de Compra para finalizar.");
                }

                // 2. Subir Archivo
                $uploadDir = 'uploads/ordenes_compra/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = uniqid() . '_' . basename($_FILES['orden_compra']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($_FILES['orden_compra']['tmp_name'], $targetPath)) {
                    throw new Exception("Error al subir el archivo.");
                }

                // 3. Guardar en tabla Adjuntos
                $stmtAdj = $pdo->prepare("INSERT INTO adjuntos (entidad_tipo, id_entidad, ruta_archivo, nombre_original) VALUES ('pedido_servicio', :id, :ruta, :nom)");
                $stmtAdj->execute([
                    ':id' => $id_pedido,
                    ':ruta' => $targetPath,
                    ':nom' => $_FILES['orden_compra']['name']
                ]);

                // 4. Actualizar Estado a 'esperando_entrega' y limpiar paso_actual (para que desaparezca de la bandeja)
                $sqlUpd = "UPDATE pedidos_servicio SET estado = 'esperando_entrega', paso_actual_id = NULL WHERE id = :id";
                $pdo->prepare($sqlUpd)->execute([':id'=>$id_pedido]);

                // 5. NOTIFICACIÓN MASIVA (A todos los involucrados)
                $mensaje_notificacion = "📢 OC Generada para Pedido #$id_pedido. En espera de proveedor.";
                
                // A. Solicitante
                $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (?,?,?)")
                    ->execute([$pedido['id_usuario_solicitante'], $mensaje_notificacion, "dashboard.php"]);

                // B. Director Médico (Si intervino)
                if ($pedido['id_director_aprobador']) {
                    $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje) VALUES (?,?)")
                        ->execute([$pedido['id_director_aprobador'], $mensaje_notificacion]);
                }

                // C. Logística (Si intervino)
                if ($pedido['id_logistica_aprobador']) {
                    $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje) VALUES (?,?)")
                        ->execute([$pedido['id_logistica_aprobador'], $mensaje_notificacion]);
                }
                
                // D. Encargado de Insumos/Suministros (Roles 4 y 5) - Avisamos a los roles directamente
                $rolEncargado = ($pedido['tipo_insumo'] == 'insumos_medicos') ? 4 : 5;
                $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje) VALUES (?,?)")
                    ->execute([$rolEncargado, $mensaje_notificacion]);

                $msg_final = "Orden de Compra adjuntada y proceso notificado a todos los involucrados.";
            }
        }
        
        $pdo->commit();
        echo "<script>alert('$msg_final'); window.location='dashboard.php';</script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-3'>Error: " . $e->getMessage() . "</div>";
    }
}

$items = $pdo->query("SELECT pi.*, im.nombre FROM pedidos_items pi JOIN insumos_medicos im ON pi.id_insumo = im.id WHERE pi.id_pedido = $id_pedido")->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h2>Gestión Dinámica: <?php echo $pedido['etiqueta_estado']; ?></h2>
        <span class="badge bg-dark text-white p-2">Proceso: <?php echo strtoupper(str_replace('_',' ',$pedido['nombre_proceso'])); ?></span>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-light fw-bold">Detalle del Pedido #<?php echo $id_pedido; ?></div>
                    <div class="card-body">
                        <p><strong>Servicio:</strong> <?php echo htmlspecialchars($pedido['servicio_solicitante']); ?></p>
                        <p><strong>Prioridad:</strong> <?php echo htmlspecialchars($pedido['prioridad']); ?></p>
                        <table class="table table-striped table-hover align-middle">
                            <thead><tr><th>Insumo</th><th width="120">Solicitado</th><th width="120">Aprobado</th></tr></thead>
                            <tbody>
                                <?php foreach($items as $i): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($i['nombre']); ?></td>
                                    <td class="text-center text-muted"><?php echo $i['cantidad_solicitada']; ?></td>
                                    <td>
                                        <?php if($puede_gestionar && !$es_fin_adquisicion): ?>
                                            <input type="number" name="cant_aprobada[<?php echo $i['id']; ?>]" class="form-control text-center fw-bold text-primary" value="<?php echo ($i['cantidad_aprobada'] ?? $i['cantidad_solicitada']); ?>">
                                        <?php else: ?>
                                            <span class="fw-bold"><?php echo ($i['cantidad_aprobada'] ?? $i['cantidad_solicitada']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-primary shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">Panel de Acción</div>
                    <div class="card-body text-center">
                        <?php if($puede_gestionar): ?>
                            
                            <?php if ($es_fin_adquisicion): ?>
                                <div class="alert alert-warning text-start small">
                                    <i class="fas fa-file-invoice-dollar"></i> <strong>Paso Final (Compras):</strong><br>
                                    Debe adjuntar la Orden de Compra generada para finalizar el proceso y notificar a los involucrados.
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="form-label fw-bold">Adjuntar Orden de Compra (PDF/Img)</label>
                                    <input type="file" name="orden_compra" class="form-control" accept=".pdf,.jpg,.png,.jpeg" required>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3">
                                    <i class="fas fa-check-double me-2"></i> FINALIZAR Y NOTIFICAR
                                </button>

                            <?php elseif($pedido['nombre_proceso'] == 'movimiento_insumos' && $pedido['nombre_estado'] == 'preparacion_retiro'): ?>
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3">
                                    <i class="fas fa-box-open me-2"></i> INSUMOS LISTOS / NOTIFICAR
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                                    <i class="fas fa-check-circle me-2"></i> APROBAR Y AVANZAR
                                </button>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-clock me-2"></i> Esperando gestión de otro rol.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>