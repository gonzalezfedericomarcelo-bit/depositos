<?php
// Archivo: pedidos_adquisicion.php
// Propósito: Solicitud de Compra de Insumos Médicos (Controlado por Permisos de DB)

require 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php'; // Aquí se carga la función tienePermiso()
include 'includes/navbar.php';

// --- NUEVA VALIDACIÓN POR PERMISO (Base de Datos) ---
// Ya no depende del rol "Responsable", sino del checkbox en Admin Roles
if (!tienePermiso('hacer_compra_insumos')) {
    echo "<div class='container-fluid px-4 mt-4'><div class='alert alert-danger shadow-sm'>
            <h4 class='alert-heading'><i class='fas fa-lock'></i> Acceso Restringido</h4>
            <p>No tienes permiso para solicitar compras de <strong>Insumos Médicos</strong>.</p>
            <hr>
            <p class='mb-0 small'>Si necesitas realizar esta acción, contacta al Administrador para que habilite el permiso 'Solicitar COMPRA Insumos Médicos' en tu rol.</p>
          </div></div>";
    include 'includes/footer.php';
    exit;
}
// ----------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // 1. BUSCAR EL PRIMER PASO DEL FLUJO
        $stmtFlujo = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = 'adquisicion_insumos' ORDER BY paso_orden ASC LIMIT 1");
        $stmtFlujo->execute();
        $primerPaso = $stmtFlujo->fetch();

        if (!$primerPaso) throw new Exception("No hay flujo configurado para Adquisiciones.");

        // 2. Insertar Cabecera
        $sql = "INSERT INTO pedidos_servicio (tipo_insumo, id_usuario_solicitante, servicio_solicitante, estado, prioridad, frecuencia_compra, paso_actual_id, proceso_origen) 
                VALUES ('insumos_medicos', :uid, :serv, :estado, :prio, :freq, :paso_id, 'adquisicion_insumos')";
        
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
        if (isset($_POST['insumo_id'])) {
            $stmtItem = $pdo->prepare("INSERT INTO pedidos_items (id_pedido, id_insumo, cantidad_solicitada) VALUES (:idp, :idi, :cant)");
            for ($i = 0; $i < count($_POST['insumo_id']); $i++) {
                if ($_POST['cantidad'][$i] > 0) {
                    $stmtItem->execute([
                        ':idp' => $id_pedido,
                        ':idi' => $_POST['insumo_id'][$i],
                        ':cant' => $_POST['cantidad'][$i]
                    ]);
                }
            }
        }

        // 4. Notificar
        $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$primerPaso['id_rol_responsable'], "Nueva Adquisición Solicitada: " . $_SESSION['user_data']['servicio'], "bandeja_gestion_dinamica.php?id=" . $id_pedido]);

        $pdo->commit();
        echo "<script>window.location='dashboard.php?msg=solicitud_enviada';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger m-4'>Error: ".$e->getMessage()."</div>";
    }
}

// Cargar insumos
$insumos = $pdo->query("SELECT * FROM insumos_medicos ORDER BY nombre ASC")->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Solicitud de Adquisición (Insumos Médicos)</h1>
    
    <form method="POST" id="formAdquisicion">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">1. Datos de la Solicitud</div>
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
                    <label class="fw-bold">Frecuencia de Uso:</label>
                    <select name="frecuencia" class="form-select w-50">
                        <option value="Mensual">Mensual</option>
                        <option value="Trimestral">Trimestral</option>
                        <option value="Semestral">Semestral</option>
                        <option value="Anual">Anual</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light fw-bold d-flex justify-content-between">
                <span>2. Detalle de Insumos</span>
                <button type="button" class="btn btn-sm btn-dark" onclick="agregarFila()">+ Agregar</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Insumo</th><th width="150">Cantidad</th><th width="50"></th></tr></thead>
                    <tbody id="bodyInsumos"></tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success btn-lg">Iniciar Solicitud</button>
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
    document.getElementById('divFrecuencia').style.display = normal ? 'block' : 'none';
}
function agregarFila() {
    var tbody = document.getElementById('bodyInsumos');
    var row = document.createElement('tr');
    row.innerHTML = `
        <td><select name="insumo_id[]" class="form-select">${document.getElementById('insumoOptions').innerHTML}</select></td>
        <td><input type="number" name="cantidad[]" class="form-control" required min="1"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
    `;
    tbody.appendChild(row);
}
window.onload = function() { agregarFila(); toggleFrecuencia(); };
</script>
<?php include 'includes/footer.php'; ?>