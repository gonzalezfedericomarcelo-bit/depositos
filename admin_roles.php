<?php
// Archivo: admin_roles.php
require 'db.php';
session_start();

if (!in_array('Administrador', $_SESSION['user_roles'] ?? [])) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = "";

// 1. GUARDAR / EDITAR / ELIMINAR ROL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    try {
        $pdo->beginTransaction();

        if ($_POST['accion'] == 'guardar') {
            $nombre = trim($_POST['nombre']);
            $id_rol = $_POST['id_rol'] ?? null;

            if ($id_rol) {
                // Editar existente
                $pdo->prepare("UPDATE roles SET nombre = :nom WHERE id = :id")->execute([':nom'=>$nombre, ':id'=>$id_rol]);
            } else {
                // Crear nuevo
                $pdo->prepare("INSERT INTO roles (nombre) VALUES (:nom)")->execute([':nom'=>$nombre]);
                $id_rol = $pdo->lastInsertId();
            }

            // Guardar Permisos
            $pdo->prepare("DELETE FROM rol_permisos WHERE id_rol = :id")->execute([':id'=>$id_rol]);
            if (isset($_POST['permisos'])) {
                $stmtPerm = $pdo->prepare("INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (:rol, :perm)");
                foreach ($_POST['permisos'] as $id_permiso) {
                    $stmtPerm->execute([':rol'=>$id_rol, ':perm'=>$id_permiso]);
                }
            }
            $mensaje = '<div class="alert alert-success">✅ Cambios guardados correctamente.</div>';
        }
        
        if ($_POST['accion'] == 'eliminar') {
            $pdo->prepare("DELETE FROM roles WHERE id = :id")->execute([':id'=>$_POST['id_rol']]);
            $mensaje = '<div class="alert alert-success">🗑️ Rol eliminado.</div>';
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();
// Agrupar permisos por categoría para mostrarlos ordenados
$permisos_raw = $pdo->query("SELECT * FROM permisos ORDER BY categoria DESC, id ASC")->fetchAll();
$permisos_agrupados = [];
foreach($permisos_raw as $p) {
    $cat = $p['categoria'] ?? 'General';
    $permisos_agrupados[$cat][] = $p;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editor de Roles y Permisos</h1>
    <p class="text-muted">Controla qué ve cada rol en el menú y en el dashboard.</p>
    <?php echo $mensaje; ?>

    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span>Roles</span>
                    <button class="btn btn-sm btn-light py-0" onclick="nuevoRol()">+ Crear</button>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($roles as $r): ?>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                           onclick='cargarRol(<?php echo json_encode($r); ?>)'>
                            <strong><?php echo htmlspecialchars($r['nombre']); ?></strong>
                            <i class="fas fa-edit text-muted small"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <span id="tituloEditor" class="fw-bold">Selecciona un rol o crea uno nuevo</span>
                </div>
                <div class="card-body">
                    <form method="POST" id="formRol">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id_rol" id="id_rol">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nombre del Rol</label>
                            <input type="text" name="nombre" id="nombre_rol" class="form-control form-control-lg" placeholder="Ej: Auditor" required>
                        </div>

                        <hr>
                        <h5 class="mb-3 text-secondary">Permisos de Visualización</h5>
                        
                        <div class="row">
                            <?php foreach ($permisos_agrupados as $categoria => $items): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 bg-light border-0">
                                        <div class="card-header fw-bold text-uppercase small text-muted">
                                            <?php echo $categoria; ?>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($items as $p): ?>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input check-permiso" type="checkbox" name="permisos[]" 
                                                       value="<?php echo $p['id']; ?>" id="perm_<?php echo $p['id']; ?>">
                                                <label class="form-check-label" for="perm_<?php echo $p['id']; ?>">
                                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger" id="btnEliminar" style="display:none;" onclick="borrarRol()">Eliminar Rol</button>
                            <button type="submit" class="btn btn-success px-4 fw-bold">Guardar Configuración</button>
                        </div>
                    </form>
                    
                    <form method="POST" id="formEliminar">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_rol" id="id_rol_eliminar">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cargarRol(rol) {
    document.getElementById('tituloEditor').innerText = 'Editando: ' + rol.nombre;
    document.getElementById('id_rol').value = rol.id;
    document.getElementById('nombre_rol').value = rol.nombre;
    document.getElementById('btnEliminar').style.display = 'inline-block';
    
    // Limpiar checks
    document.querySelectorAll('.check-permiso').forEach(c => c.checked = false);

    // Cargar permisos via fetch (archivo auxiliar)
    fetch('api_get_permisos_rol.php?id=' + rol.id)
        .then(r => r.json())
        .then(ids => {
            ids.forEach(id => {
                let chk = document.getElementById('perm_' + id);
                if(chk) chk.checked = true;
            });
        });
}

function nuevoRol() {
    document.getElementById('tituloEditor').innerText = 'Creando Nuevo Rol';
    document.getElementById('id_rol').value = '';
    document.getElementById('nombre_rol').value = '';
    document.getElementById('btnEliminar').style.display = 'none';
    document.querySelectorAll('.check-permiso').forEach(c => c.checked = false);
}

function borrarRol() {
    if(confirm('¿Seguro? Esto quitará el acceso a los usuarios con este rol.')) {
        document.getElementById('id_rol_eliminar').value = document.getElementById('id_rol').value;
        document.getElementById('formEliminar').submit();
    }
}
</script>
<?php include 'includes/footer.php'; ?>