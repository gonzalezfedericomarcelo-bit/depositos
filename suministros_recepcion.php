<?php
// Archivo: suministros_recepcion.php
// Propósito: Recepción Suministros con ALTA RÁPIDA EN TABLA

require 'db.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: suministros_compras.php"); exit; }
$id_oc = $_GET['id'];
$roles_usuario = $_SESSION['user_roles'] ?? [];

// Permisos
if (!in_array('Administrador', $roles_usuario) && !in_array('Encargado Depósito Suministros', $roles_usuario)) {
    die("<h1>⛔ Acceso Denegado</h1>");
}

$mensaje = "";

// 1. LÓGICA ALTA RÁPIDA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear_rapido') {
    try {
        $stmtNew = $pdo->prepare("INSERT INTO suministros_generales (codigo, nombre, descripcion, unidad_medida, stock_actual, stock_minimo) VALUES (:c, :n, 'Alta rápida recepción', 'unidades', 0, 5)");
        $stmtNew->execute([':c' => $_POST['nuevo_codigo'], ':n' => $_POST['nuevo_nombre']]);
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ <strong>' . htmlspecialchars($_POST['nuevo_nombre']) . '</strong> agregado al sistema. Ya puedes seleccionarlo.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// 2. LÓGICA RECEPCIÓN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'recibir') {
    try {
        $pdo->beginTransaction();
        $remito = $_POST['numero_remito'];
        $items_recibidos = $_POST['recibido'];
        
        $hubo_faltantes = false;
        $detalle_faltantes = "";

        $stmtOCData = $pdo->prepare("SELECT id_usuario_creador, numero_oc FROM ordenes_compra WHERE id = :id");
        $stmtOCData->execute(['id' => $id_oc]);
        $datosOC = $stmtOCData->fetch();

        foreach ($items_recibidos as $id_item_oc => $datos) {
            $cant_recibida = (int)$datos['cantidad'];
            $cant_pedida   = (int)$datos['cantidad_pedida_hidden'];
            $id_suministro = (int)$datos['id_suministro'];

            if ($id_suministro > 0) {
                // Actualizar Stock y OC
                $stmtStock = $pdo->prepare("UPDATE suministros_generales SET stock_actual = stock_actual + :cant WHERE id = :id");
                $stmtStock->execute([':cant' => $cant_recibida, ':id' => $id_suministro]);

                $stmtItem = $pdo->prepare("UPDATE ordenes_compra_items SET cantidad_recibida = :cant, id_suministro_asociado = :id_sum WHERE id = :id");
                $stmtItem->execute([':cant' => $cant_recibida, ':id_sum' => $id_suministro, ':id' => $id_item_oc]);

                // Detectar Faltante
                if ($cant_recibida < $cant_pedida) {
                    $hubo_faltantes = true;
                    $diff = $cant_pedida - $cant_recibida;
                    $stmtNom = $pdo->prepare("SELECT nombre FROM suministros_generales WHERE id = :id");
                    $stmtNom->execute(['id' => $id_suministro]);
                    $nomProd = $stmtNom->fetchColumn();
                    $detalle_faltantes .= "Faltan $diff de $nomProd. ";
                }
            }
        }

        $estado_final = $hubo_faltantes ? 'recibida_parcial' : 'recibida_total';
        $obs_extra = "\n[RECIBIDO] Fecha: " . date('Y-m-d H:i') . " - Remito: " . $remito . " - Por: " . $_SESSION['user_name'];
        if ($hubo_faltantes) $obs_extra .= "\n⚠️ ALERTA FALTANTES: " . $detalle_faltantes;
        
        $stmtOC = $pdo->prepare("UPDATE ordenes_compra SET estado = :est, observaciones = CONCAT(IFNULL(observaciones, ''), :obs) WHERE id = :id");
        $stmtOC->execute([':est' => $estado_final, ':obs' => $obs_extra, ':id' => $id_oc]);

        // Notificar Faltantes
        if ($hubo_faltantes) {
            $msj = "⚠️ FALTANTE OC #{$datosOC['numero_oc']}: $detalle_faltantes";
            
            // A Compras
            $stmtNoti = $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (:uid, :msj, :url)");
            $stmtNoti->execute([':uid' => $datosOC['id_usuario_creador'], ':msj' => $msj, ':url' => "suministros_oc_ver.php?id=" . $id_oc]);

            // A Logística
            $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Encargado Logística' LIMIT 1");
            $stmtRol->execute();
            $rolLog = $stmtRol->fetchColumn();
            if ($rolLog) {
                $stmtNoti2 = $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (:rol, :msj, :url)");
                $stmtNoti2->execute([':rol' => $rolLog, ':msj' => $msj, ':url' => "suministros_oc_ver.php?id=" . $id_oc]);
            }
        }

        $pdo->commit();
        header("Location: suministros_stock.php?msg=recepcion_ok");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$stmt = $pdo->prepare("SELECT * FROM ordenes_compra WHERE id = :id AND tipo_origen = 'suministros'");
$stmt->execute(['id' => $id_oc]);
$orden = $stmt->fetch();

if ($orden['estado'] != 'aprobada_logistica' && $orden['estado'] != 'recibida_parcial') {
    echo "<div class='container mt-5 alert alert-warning'>Orden no lista para recibir.</div>"; include 'includes/footer.php'; exit;
}

$stmtItems = $pdo->prepare("SELECT * FROM ordenes_compra_items WHERE id_oc = :id");
$stmtItems->execute(['id' => $id_oc]);
$items_orden = $stmtItems->fetchAll();

$lista_suministros = $pdo->query("SELECT id, nombre, codigo FROM suministros_generales ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Recepción Suministros</h1>
    <?php echo $mensaje; ?>

    <form method="POST" action="">
        <input type="hidden" name="accion" value="recibir">
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white fw-bold"><i class="fas fa-dolly"></i> Ingreso de Mercadería</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="fw-bold">N° Remito *</label>
                        <input type="text" name="numero_remito" class="form-control" required placeholder="Ej: REM-001">
                    </div>
                    <div class="col-md-8 d-flex align-items-center">
                        <div class="alert alert-info w-100 mb-0 py-2 small">
                            <i class="fas fa-info-circle"></i> Si el producto no existe en la lista, usa el botón verde <strong>"+ Nuevo"</strong> en la columna "Vincular".
                        </div>
                    </div>
                </div>

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Solicitado (OC)</th>
                            <th class="text-center">Cant. Pedida</th>
                            <th style="min-width: 300px;">
                                Vincular con Inventario 
                                <button type="button" class="btn btn-sm btn-success float-end" data-bs-toggle="modal" data-bs-target="#modalAltaRapida">
                                    <i class="fas fa-plus"></i> Nuevo
                                </button>
                            </th>
                            <th style="width: 120px;">Llegaron</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_orden as $item): ?>
                            <?php if($item['cantidad_recibida'] >= $item['cantidad_solicitada']) continue; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['descripcion_producto']); ?></strong></td>
                                <td class="text-center"><span class="badge bg-secondary"><?php echo $item['cantidad_solicitada']; ?></span></td>
                                <td>
                                    <select name="recibido[<?php echo $item['id']; ?>][id_suministro]" class="form-select select-search" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($lista_suministros as $s): ?>
                                            <?php $selected = (stripos($s['nombre'], $item['descripcion_producto']) !== false) ? 'selected' : ''; ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($s['nombre']) . " (" . $s['codigo'] . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="recibido[<?php echo $item['id']; ?>][cantidad_pedida_hidden]" value="<?php echo $item['cantidad_solicitada']; ?>">
                                    <input type="number" name="recibido[<?php echo $item['id']; ?>][cantidad]" class="form-control fw-bold text-success" value="<?php echo $item['cantidad_solicitada']; ?>" min="0" onchange="verificarFaltante(this, <?php echo $item['cantidad_solicitada']; ?>)">
                                    <div class="msg-faltante text-danger small fw-bold mt-1" style="display:none;">⚠️ Faltan: <span class="diff"></span></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end"><button type="submit" class="btn btn-success btn-lg">Confirmar Recepción</button></div>
        </div>
    </form>
</div>

<div class="modal fade" id="modalAltaRapida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Crear Nuevo Suministro</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_rapido">
                    <div class="mb-3"><label>Nombre *</label><input type="text" name="nuevo_nombre" class="form-control" required placeholder="Ej: Nuevo Suministro"></div>
                    <div class="mb-3"><label>Código</label><input type="text" name="nuevo_codigo" class="form-control" placeholder="SKU"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar y Usar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function verificarFaltante(input, pedido) {
    let recibido = parseInt(input.value);
    let div = input.parentElement.querySelector('.msg-faltante');
    if (recibido < pedido) {
        div.querySelector('.diff').innerText = (pedido - recibido);
        div.style.display = 'block';
        input.classList.add('border-danger', 'text-danger');
    } else {
        div.style.display = 'none';
        input.classList.remove('border-danger', 'text-danger');
    }
}
</script>
<?php include 'includes/footer.php'; ?>