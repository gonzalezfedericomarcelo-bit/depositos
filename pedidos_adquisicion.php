<?php
// Archivo: pedidos_adquisicion.php
require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Validar Responsable
if (($_SESSION['user_data']['rol_en_servicio'] ?? '') != 'Responsable') {
    die("<div class='alert alert-danger m-4'>Acceso denegado.</div>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // 1. BUSCAR EL PRIMER PASO DEL FLUJO DINÁMICO
        // Aquí es donde evitamos el hardcode. Preguntamos a la DB quién es el primero.
        $stmtFlujo = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = 'adquisicion_insumos' ORDER BY paso_orden ASC LIMIT 1");
        $stmtFlujo->execute();
        $primerPaso = $stmtFlujo->fetch();

        if (!$primerPaso) throw new Exception("No hay flujo configurado para Adquisiciones.");

        // 2. Insertar Cabecera
        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, id_usuario_solicitante, servicio_solicitante, estado, prioridad, frecuencia_compra, paso_actual_id) 
                VALUES ('insumos_medicos', :uid, :serv, :estado, :prio, :freq, :paso_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $_SESSION['user_id'], 
            ':serv' => $_SESSION['user_data']['servicio'],
            ':estado' => $primerPaso['nombre_estado'], // Usamos el estado de la DB
            ':prio' => $_POST['prioridad'],
            ':freq' => ($_POST['prioridad'] == 'Normal') ? $_POST['frecuencia'] : null,
            ':paso_id' => $primerPaso['id']
        ]);
        $id_pedido = $pdo->lastInsertId();

        // 3. Insertar Ítems (Lógica JS)
        if (isset($_POST['insumo_id'])) {
            $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_insumo, cantidad_solicitada) VALUES (:idp, :idi, :cant)");
            for ($i = 0; $i < count($_POST['insumo_id']); $i++) {
                $stmtItem->execute([
                    ':idp' => $id_pedido,
                    ':idi' => $_POST['insumo_id'][$i],
                    ':cant' => $_POST['cantidad'][$i]
                ]);
            }
        }

        // 4. Notificar al Rol Responsable del Primer Paso (Dinámico)
        $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$primerPaso['id_rol_responsable'], "Nueva Adquisición Solicitada: " . $_SESSION['user_data']['servicio'], "bandeja_gestion_dinamica.php?id=" . $id_pedido]);

        $pdo->commit();
        echo "<script>window.location='dashboard.php?msg=solicitud_enviada';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-4'>Error: ".$e->getMessage()."</div>";
    }
}

// Cargar insumos para el select
$insumos = $pdo->query("SELECT * FROM insumos_medicos ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Solicitud de Adquisición (Insumos Médicos)</h1>
    
    <form method="POST" id="formAdquisicion">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-info text-white fw-bold">1. Datos de la Solicitud</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold d-block">Prioridad:</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="prioridad" id="prioNormal" value="Normal" checked onchange="toggleFrecuencia()">
                        <label class="form-check-label" for="prioNormal">Normal</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="prioridad" id="prioUrgente" value="Urgente" onchange="toggleFrecuencia()">
                        <label class="form-check-label text-warning fw-bold" for="prioUrgente">Urgente</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="prioridad" id="prioExtra" value="Extraordinaria" onchange="toggleFrecuencia()">
                        <label class="form-check-label text-danger fw-bold" for="prioExtra">Extraordinaria</label>
                    </div>
                </div>

                <div class="mb-3" id="divFrecuencia">
                    <label class="fw-bold">Frecuencia de Uso (Para previsión):</label>
                    <select name="frecuencia" class="form-select w-50">
                        <option value="Mensual">Mensual (1 mes)</option>
                        <option value="Trimestral">Trimestral (3 meses)</option>
                        <option value="Semestral">Semestral (6 meses)</option>
                        <option value="Anual">Anual (1 año)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between">
                <span>2. Detalle de Insumos</span>
                <button type="button" class="btn btn-sm btn-light text-dark" onclick="agregarFila()">+ Agregar Insumo</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0" id="tablaInsumos">
                    <thead><tr><th>Insumo</th><th width="150">Cantidad</th><th width="50"></th></tr></thead>
                    <tbody id="bodyInsumos">
                        </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success btn-lg">Iniciar Proceso de Aprobación</button>
            </div>
        </div>
    </form>
</div>

<div id="insumoOptions" style="display:none;">
    <?php foreach($insumos as $in) { echo "<option value='".$in['id']."'>".htmlspecialchars($in['nombre'])."</option>"; } ?>
</div>

<script>
function toggleFrecuencia() {
    var normal = document.getElementById('prioNormal').checked;
    var div = document.getElementById('divFrecuencia');
    div.style.display = normal ? 'block' : 'none';
}

function agregarFila() {
    var tbody = document.getElementById('bodyInsumos');
    var row = document.createElement('tr');
    var options = document.getElementById('insumoOptions').innerHTML;
    
    row.innerHTML = `
        <td><select name="insumo_id[]" class="form-select select-search">${options}</select></td>
        <td><input type="number" name="cantidad[]" class="form-control" required min="1"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
    `;
    tbody.appendChild(row);
}
// Iniciar con una fila
window.onload = function() { agregarFila(); toggleFrecuencia(); };
</script>
<?php include 'includes/footer.php'; ?>