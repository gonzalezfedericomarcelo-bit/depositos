<?php
// Archivo: admin_usuarios.php
// CORREGIDO: Botón de editar ACTIVO

require 'db.php';
session_start();

$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = "";

// LÓGICA DE GUARDADO (Nuevo Usuario Rápido)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    try {
        $pdo->beginTransaction();
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $pass_raw = $_POST['password'];
        $roles_asignados = $_POST['roles'] ?? [];

        if (empty($nombre) || empty($email) || empty($pass_raw) || empty($roles_asignados)) {
            throw new Exception("Datos incompletos.");
        }

        $pass_hash = password_hash($pass_raw, PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, activo) VALUES (:nom, :email, :pass, 1)");
        $stmtUser->execute([':nom' => $nombre, ':email' => $email, ':pass' => $pass_hash]);
        $id_nuevo_usuario = $pdo->lastInsertId();

        $stmtRol = $pdo->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (:user, :rol)");
        foreach ($roles_asignados as $id_rol) {
            $stmtRol->execute([':user' => $id_nuevo_usuario, ':rol' => $id_rol]);
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Usuario creado.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$lista_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();

$sqlUsuarios = "
    SELECT u.id, u.nombre_completo, u.email, u.activo,
           GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_nombres
    FROM usuarios u
    LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario
    LEFT JOIN roles r ON ur.id_rol = r.id
    GROUP BY u.id
    ORDER BY u.nombre_completo ASC
";
$usuarios = $pdo->query($sqlUsuarios)->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Administración de Usuarios</h1>
    <?php echo $mensaje; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-users-cog me-1"></i> Personal Registrado</div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php 
                                        if ($u['roles_nombres']) {
                                            $rolesArr = explode(', ', $u['roles_nombres']);
                                            foreach($rolesArr as $rol) {
                                                echo '<span class="badge bg-info text-dark me-1">'.$rol.'</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted fst-italic">Sin rol</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="admin_usuarios_editar.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Roles">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registrar Nuevo Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label>Contraseña</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="fw-bold">Roles</label>
                        <div class="card p-2" style="max-height: 150px; overflow-y: auto;">
                            <?php foreach ($lista_roles as $rol): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo $rol['id']; ?>">
                                    <label class="form-check-label"><?php echo htmlspecialchars($rol['nombre']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Crear</button></div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>