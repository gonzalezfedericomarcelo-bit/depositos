<?php
// Archivo: insumos_oc_crear.php
// Propósito: Crear OC Insumos (Ahora con selector de Servicio Destino)

require 'db.php';
session_start();

// VERIFICACIÓN DE PERMISOS
// Usamos el permiso de creación existente
$roles_usuario = $_SESSION['user_roles'] ?? [];
include 'includes/header.php'; // Para cargar tienePermiso()

if (!tienePermiso('crear_oc_insumos') && !in_array('Administrador', $roles_usuario)) {
    header("Location: insumos_compras.php?error=acceso_denegado");
    exit;
}

$mensaje = "";

// Obtener lista de servicios existentes (Agrupando desde usuarios)
$servicios = $pdo->query("SELECT DISTINCT servicio FROM usuarios WHERE servicio IS NOT NULL AND servicio != '' ORDER BY servicio ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty($_POST['numero_oc']) || empty($_POST['items'])) {
            throw new Exception("Faltan datos obligatorios.");
        }

        $pdo->beginTransaction();

        // 1. Insertar Cabecera (AHORA CON SERVICIO_DESTINO)
        $stmt = $pdo->prepare("INSERT INTO ordenes_compra (numero_oc, servicio_destino, tipo_origen, id_usuario_creador, estado, observaciones) VALUES (:num, :serv, 'insumos', :user, 'pendiente_logistica', :obs)");
        $stmt->execute([
            ':num' => $_POST['numero_oc'],
            ':serv' => $_POST['servicio_destino'], // Guardamos el destino
            ':user' => $_SESSION['user_id'],
            ':obs' => $_POST['observaciones']
        ]);
        $id_oc = $pdo->lastInsertId();

        // 2. Insertar Ítems
        $stmtItem = $pdo->prepare("INSERT INTO ordenes_compra_items (id_oc, descripcion_producto, cantidad_solicitada, precio_estimado) VALUES (:id_oc, :desc, :cant, :precio)");
        foreach ($_POST['items'] as $item) {
            if (!empty($item['descripcion']) && !empty($item['cantidad'])) {
                $stmtItem->execute([
                    ':id_oc' => $id_oc,
                    ':desc'  => $item['descripcion'],
                    ':cant'  => $item['cantidad'],
                    ':precio'=> !empty($item['precio']) ? $item['precio'] : 0
                ]);
            }
        }

        // 3. Adjuntos
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }
        if (!empty($_FILES['adjuntos']['name'][0])) {
            $stmtAdjunto = $pdo->prepare("INSERT INTO adjuntos (entidad_tipo, id_entidad, ruta_archivo, nombre_original) VALUES ('orden_compra', :id, :ruta, :nombre)");
            for ($i = 0; $i < count($_FILES['adjuntos']['name']); $i++) {
                if ($_FILES['adjuntos']['tmp_name'][$i] != "") {
                    $originalName = $_FILES['adjuntos']['name'][$i];
                    $newFileName = uniqid() . '_' . $originalName;
                    $destPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($_FILES['adjuntos']['tmp_name'][$i], $destPath)) {
                        $stmtAdjunto->execute([':id' => $id_oc, ':ruta' => $destPath, ':nombre' => $originalName]);
                    }
                }
            }
        }

        // 4. Notificar al Director Médico
        $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Director Médico' LIMIT 1");
        $stmtRol->execute();
        $rolDestino = $stmtRol->fetchColumn();
        if ($rolDestino) {
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
                ->execute([$rolDestino, "Nueva OC Médica #{$_POST['numero_oc']} para {$_POST['servicio_destino']}", "insumos_oc_ver.php?id=" . $id_oc]);
        }

        $pdo->commit();
        header("Location: insumos_compras.php?msg=creada");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/sidebar.php';
include 'includes/navbar.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Nueva Orden (Insumos Médicos)</h1>
    <?php echo $mensaje; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">Datos Cabecera</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold">Número OC *</label>
                            <input type="text" name="numero_oc" class="form-control" required placeholder="Ej: MED-2025-001">
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-primary">Servicio Destino *</label>
                            <select name="servicio_destino" class="form-select fw-bold" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Stock Central">Stock Central (Depósito)</option>
                                <?php foreach($servicios as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">¿Para quién es esta compra?</div>
                        </div>
                        <div class="mb-3"><label>Observaciones</label><textarea name="observaciones" class="form-control" rows="4"></textarea></div>
                        <div class="mb-3"><label class="fw-bold">Adjuntos</label><input type="file" name="adjuntos[]" class="form-control" multiple></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between"><span>Ítems</span><button type="button" class="btn btn-light btn-sm text-dark" onclick="agregarItem()">+ Agregar</button></div>
                    <div class="card-body p-0"><table class="table table-striped mb-0"><thead class="table-light"><tr><th width="50%">Producto</th><th width="20%">Cant</th><th width="20%">$ Est.</th><th width="10%"></th></tr></thead><tbody id="contenedorItems"><tr class="item-row"><td><input type="text" name="items[0][descripcion]" class="form-control" required placeholder="Ej: Gasas"></td><td><input type="number" name="items[0][cantidad]" class="form-control" required min="1" value="1"></td><td><input type="number" name="items[0][precio]" class="form-control" step="0.01"></td><td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" disabled><i class="fas fa-trash"></i></button></td></tr></tbody></table></div>
                    <div class="card-footer bg-white text-end"><button type="submit" class="btn btn-primary btn-lg">Guardar</button></div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    let contadorItems = 1;
    function agregarItem() {
        const tr = document.createElement('tr'); tr.classList.add('item-row');
        tr.innerHTML = `<td><input type="text" name="items[${contadorItems}][descripcion]" class="form-control" required></td><td><input type="number" name="items[${contadorItems}][cantidad]" class="form-control" required min="1" value="1"></td><td><input type="number" name="items[${contadorItems}][precio]" class="form-control" step="0.01"></td><td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila(this)"><i class="fas fa-trash"></i></button></td>`;
        document.getElementById('contenedorItems').appendChild(tr); contadorItems++;
    }
    function eliminarFila(btn) { btn.closest('tr').remove(); }
</script>
<?php include 'includes/footer.php'; ?>