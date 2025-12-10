<?php
// Archivo: admin_usuarios_editar.php
// Propósito: Editar datos de usuario y reasignar sus ROLES

require 'db.php';
session_start();

// Solo Admin puede entrar
$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_usuarios.php");
    exit;
}

$id_user = $_GET['id'];
$mensaje = "";

// 1. PROCESAR GUARDADO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // A. Actualizar Datos Básicos
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Si escribió contraseña nueva, la actualizamos. Si no, dejamos la vieja.
        if (!empty($_POST['password'])) {
            $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("UPDATE usuarios SET nombre_completo=:nom, email=:mail, password=:pass, activo=:act WHERE id=:id");
            $stmtUser->execute([':nom'=>$nombre, ':mail'=>$email, ':pass'=>$pass_hash, ':act'=>$activo, ':id'=>$id_user]);
        } else {
            $stmtUser = $pdo->prepare("UPDATE usuarios SET nombre_completo=:nom, email=:mail, activo=:act WHERE id=:id");
            $stmtUser->execute([':nom'=>$nombre, ':mail'=>$email, ':act'=>$activo, ':id'=>$id_user]);
        }

        // B. Actualizar ROLES (Borrar viejos -> Insertar nuevos)
        // Primero borramos todos los roles actuales de este usuario
        $stmtDel = $pdo->prepare("DELETE FROM usuario_roles WHERE id_usuario = :id");
        $stmtDel->execute([':id' => $id_user]);

        // Ahora insertamos los marcados
        if (isset($_POST['roles']) && is_array($_POST['roles'])) {
            $stmtAdd = $pdo->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (:user, :rol)");
            foreach ($_POST['roles'] as $rol_id) {
                $stmtAdd->execute([':user' => $id_user, ':rol' => $rol_id]);
            }
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success">✅ Usuario actualizado. <a href="admin_usuarios.php">Volver a la lista</a></div>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Incluir Layout
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// 2. Obtener Datos Actuales del Usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id_user]);
$usuario = $stmt->fetch();

if (!$usuario) { die("Usuario no encontrado"); }

// 3. Obtener Roles Disponibles y Roles que ya tiene el usuario
$lista_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();

$stmtMyRoles = $pdo->prepare("SELECT id_rol FROM usuario_roles WHERE id_usuario = :id");
$stmtMyRoles->execute(['id' => $id_user]);
$mis_roles_ids = $stmtMyRoles->fetchAll(PDO::FETCH_COLUMN); // Array simple de IDs [1, 3]
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Editar Usuario: <?php echo htmlspecialchars($usuario['nombre_completo']); ?></h1>
    
    <?php echo $mensaje; ?>

    <div class="card mb-4 border-primary">
        <div class="card-body">
            <form method="POST">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email (Login)</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="(Dejar vacío para no cambiar)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" id="checkActivo" <?php echo ($usuario['activo'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="checkActivo">Usuario Activo (Puede iniciar sesión)</label>
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3 text-primary"><i class="fas fa-user-tag me-2"></i> Roles Asignados</h5>
                <p class="text-muted small">Selecciona qué funciones cumplirá este usuario en el sistema.</p>

                <div class="row">
                    <?php foreach ($lista_roles as $rol): ?>
                        <?php $checked = in_array($rol['id'], $mis_roles_ids) ? 'checked' : ''; ?>
                        <div class="col-md-4 mb-2">
                            <div class="card h-100 shadow-sm <?php echo $checked ? 'border-primary bg-light' : ''; ?>">
                                <div class="card-body py-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $rol['id']; ?>" id="rol_<?php echo $rol['id']; ?>" <?php echo $checked; ?>>
                                        <label class="form-check-label fw-bold" for="rol_<?php echo $rol['id']; ?>">
                                            <?php echo htmlspecialchars($rol['nombre']); ?>
                                        </label>
                                        <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($rol['descripcion']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 text-end">
                    <a href="admin_usuarios.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>