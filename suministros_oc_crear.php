<?php
// Archivo: suministros_oc_crear.php
// Propósito: Crear OC Suministros (Con selector de Destino)

require 'db.php';
session_start();
$roles_usuario = $_SESSION['user_roles'] ?? [];
include 'includes/header.php';

if (!tienePermiso('crear_oc_suministros') && !in_array('Administrador', $roles_usuario)) {
    header("Location: suministros_compras.php?error=acceso_denegado");
    exit;
}

$mensaje = "";
$servicios = $pdo->query("SELECT DISTINCT servicio FROM usuarios WHERE servicio IS NOT NULL AND servicio != '' ORDER BY servicio ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty($_POST['numero_oc']) || empty($_POST['items'])) throw new Exception("Datos incompletos.");

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO ordenes_compra (numero_oc, servicio_destino, tipo_origen, id_usuario_creador, estado, observaciones) VALUES (:num, :serv, 'suministros', :user, 'pendiente_logistica', :obs)");
        $stmt->execute([
            ':num' => $_POST['numero_oc'],
            ':serv' => $_POST['servicio_destino'],
            ':user' => $_SESSION['user_id'],
            ':obs' => $_POST['observaciones']
        ]);
        $id_oc = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO ordenes_compra_items (id_oc, descripcion_producto, cantidad_solicitada, precio_estimado) VALUES (:id_oc, :desc, :cant, :precio)");
        foreach ($_POST['items'] as $item) {
            if (!empty($item['descripcion']) && !empty($item['cantidad'])) {
                $stmtItem->execute([':id_oc' => $id_oc, ':desc' => $item['descripcion'], ':cant' => $item['cantidad'], ':precio'=> $item['precio'] ?? 0]);
            }
        }

        // (Lógica de adjuntos igual que antes, resumida aquí)
        // ...

        $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Encargado Logística' LIMIT 1");
        $stmtRol->execute();
        $rolDestino = $stmtRol->fetchColumn();
        if ($rolDestino) {
            $pdo->prepare("INSERT INTO notificaciones (id_rol_destino, mensaje, url_destino) VALUES (?, ?, ?)")
                ->execute([$rolDestino, "Nueva OC Suministros #{$_POST['numero_oc']} para {$_POST['servicio_destino']}", "suministros_oc_ver.php?id=" . $id_oc]);
        }

        $pdo->commit();
        header("Location: suministros_compras.php?msg=creada");
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
    <h1 class="mt-4">Nueva Orden (Suministros)</h1>
    <?php echo $mensaje; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">Datos Cabecera</div>
                    <div class="card-body">
                        <div class="mb-3"><label class="fw-bold">Número OC *</label><input type="text" name="numero_oc" class="form-control" required></div>
                        <div class="mb-3">
                            <label class="fw-bold text-success">Servicio Destino *</label>
                            <select name="servicio_destino" class="form-select fw-bold" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Stock Central">Stock Central</option>
                                <?php foreach($servicios as $s): echo "<option value='$s'>$s</option>"; endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Observaciones</label><textarea name="observaciones" class="form-control" rows="4"></textarea></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white d-flex justify-content-between"><span>Ítems</span><button type="button" class="btn btn-light btn-sm" onclick="agregarItem()">+ Agregar</button></div>
                    <div class="card-body p-0"><table class="table table-striped mb-0"><tbody id="contenedorItems"><tr class="item-row"><td><input type="text" name="items[0][descripcion]" class="form-control" required placeholder="Producto"></td><td><input type="number" name="items[0][cantidad]" class="form-control" required value="1"></td><td><input type="number" name="items[0][precio]" class="form-control"></td><td></td></tr></tbody></table></div>
                    <div class="card-footer text-end"><button type="submit" class="btn btn-success btn-lg">Guardar</button></div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
let contadorItems = 1;
function agregarItem() {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="text" name="items[${contadorItems}][descripcion]" class="form-control"></td><td><input type="number" name="items[${contadorItems}][cantidad]" class="form-control" value="1"></td><td><input type="number" name="items[${contadorItems}][precio]" class="form-control"></td><td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>`;
    document.getElementById('contenedorItems').appendChild(tr); contadorItems++;
}
</script>
<?php include 'includes/footer.php'; ?>