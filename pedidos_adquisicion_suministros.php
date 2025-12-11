<?php
// Archivo: pedidos_adquisicion_suministros.php
// Propósito: Planificación de Compras Suministros -> Encargado -> Dir. Operativo -> Compras
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Validar Responsable
if (($_SESSION['user_data']['rol_en_servicio'] ?? '') != 'Responsable') {
    die("<div class='alert alert-danger m-4'>Acceso denegado. Solo Responsables.</div>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // 1. Buscar primer paso de 'adquisicion_suministros'
        $stmtFlujo = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = 'adquisicion_suministros' ORDER BY paso_orden ASC LIMIT 1");
        $stmtFlujo->execute();
        $primerPaso = $stmtFlujo->fetch();

        if (!$primerPaso) throw new Exception("Error: Flujo de Adquisición Suministros no configurado.");

        // 2. Insertar Cabecera
        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, proceso_origen, id_usuario_solicitante, servicio_solicitante, estado, prioridad, frecuencia_compra, paso_actual_id) 
                VALUES ('suministros', 'adquisicion_suministros', :uid, :serv, :estado, :prio, :freq, :paso_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $_SESSION['user_id'], 
            ':serv' => $_SESSION['user_data']['servicio'],
            ':estado' => $primerPaso['nombre_estado'], 
            ':prio' => $_POST['prioridad'],
            ':freq' => ($_POST['prioridad'] == 'Normal') ? $_POST['frecuencia'] : null,
            ':paso_id' => $primerPaso['id']
        ]);
        $id_pedido = $pdo->lastInsertId();

        // 3. Insertar Ítems
        if (isset($_POST['suministro_id'])) {
            $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_suministro, cantidad_solicitada) VALUES (:idp, :ids, :cant)");
            for ($i = 0; $i < count($_POST['suministro_id']); $i++) {
                if ($_POST['cantidad'][$i] > 0) {
                    $stmtItem->execute([
                        ':idp' => $id_pedido,
                        ':ids' => $_POST['suministro_id'][$i],
                        ':cant' => $_POST['cantidad'][$i]
                    ]);
                }
            }
        }

        // 4. Notificar al Encargado de Suministros
        $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$primerPaso['id_rol_responsable'], "Nueva Planificación de Compra: " . $_SESSION['user_data']['servicio'], "bandeja_gestion_dinamica.php?id=" . $id_pedido]);

        $pdo->commit();
        echo "<script>window.location='dashboard.php?msg=planificacion_enviada';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-4'>Error: ".$e->getMessage()."</div>";
    }
}

$suministros = $pdo->query("SELECT * FROM suministros_generales WHERE stock_actual > 0 ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Planificación de Adquisición (Suministros)</h1>
    <div class="alert alert-warning">
        <i class="fas fa-calendar-alt"></i> Use este formulario para solicitar stock periódico (Ej: Resmas para todo el año). Para urgencias diarias, use "Solicitud Interna".
    </div>
    
    <form method="POST" id="formAdquisicion">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white fw-bold">1. Datos de la Planificación</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold d-block">Tipo de Pedido:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="prioridad" id="prioNormal" value="Normal" checked onchange="toggleFrecuencia()">
                        <label class="form-check-label" for="prioNormal">Normal (Planificado)</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="prioridad" id="prioExtra" value="Extraordinaria" onchange="toggleFrecuencia()">
                        <label class="form-check-label text-danger fw-bold" for="prioExtra">Extraordinaria</label>
                    </div>
                </div>

                <div class="mb-3" id="divFrecuencia">
                    <label class="fw-bold">Cobertura Temporal (¿Para cuánto tiempo es este pedido?):</label>
                    <select name="frecuencia" class="form-select w-50">
                        <option value="Trimestral">Trimestral (3 meses)</option>
                        <option value="Semestral">Semestral (6 meses)</option>
                        <option value="Anual">Anual (1 año)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between">
                <span>2. Listado de Artículos</span>
                <button type="button" class="btn btn-sm btn-light text-dark" onclick="agregarFila()">+ Agregar</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Artículo</th><th width="150">Cantidad</th><th width="50"></th></tr></thead>
                    <tbody id="bodyItems"></tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success btn-lg">Enviar Planificación</button>
            </div>
        </div>
    </form>
</div>

<div id="itemOptions" style="display:none;">
    <?php foreach($suministros as $s) { echo "<option value='".$s['id']."'>".htmlspecialchars($s['nombre'])."</option>"; } ?>
</div>

<script>
function toggleFrecuencia() {
    var normal = document.getElementById('prioNormal').checked;
    document.getElementById('divFrecuencia').style.display = normal ? 'block' : 'none';
}
function agregarFila() {
    var tbody = document.getElementById('bodyItems');
    var row = document.createElement('tr');
    row.innerHTML = `<td><select name="suministro_id[]" class="form-select">${document.getElementById('itemOptions').innerHTML}</select></td><td><input type="number" name="cantidad[]" class="form-control" required min="1"></td><td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>`;
    tbody.appendChild(row);
}
window.onload = function() { agregarFila(); toggleFrecuencia(); };
</script>
<?php include 'includes/footer.php'; ?>