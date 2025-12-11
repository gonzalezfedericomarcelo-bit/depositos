<?php
// Archivo: admin_usuarios.php
require 'db.php';
session_start();

$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = "";

// --- LÓGICA DE APROBACIÓN AUTOMÁTICA DE ROL ---
if (isset($_GET['aprobar_id'])) {
    try {
        $id_aprob = $_GET['aprobar_id'];
        
        $pdo->beginTransaction();

        // 1. Activamos la validación
        $stmtUpd = $pdo->prepare("UPDATE usuarios SET validado_por_admin = 1 WHERE id = :id");
        $stmtUpd->execute([':id' => $id_aprob]);

        // 2. BUSCAR O CREAR ROL "Servicio"
        // Buscamos el ID del rol 'Servicio'
        $stmtRol = $pdo->query("SELECT id FROM roles WHERE nombre = 'Servicio' LIMIT 1");
        $id_rol_servicio = $stmtRol->fetchColumn();

        if (!$id_rol_servicio) {
            // Si por alguna razón no existe, lo creamos al vuelo
            $pdo->query("INSERT INTO roles (nombre, descripcion) VALUES ('Servicio', 'Usuario solicitante base')");
            $id_rol_servicio = $pdo->lastInsertId();
        }

        // 3. ASIGNAR EL ROL AL USUARIO (Si no lo tiene ya)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuario_roles WHERE id_usuario = ? AND id_rol = ?");
        $stmtCheck->execute([$id_aprob, $id_rol_servicio]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsertRol = $pdo->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (?, ?)");
            $stmtInsertRol->execute([$id_aprob, $id_rol_servicio]);
        }
        
        // 4. Notificamos al usuario
        $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$id_aprob, "¡Tu cuenta ha sido aprobada y tu rol de Servicio asignado! Ya puedes ingresar.", "dashboard.php"]);
            
        $pdo->commit();
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Usuario aprobado y rol "Servicio" asignado correctamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
// ------------------------------------------

// LÓGICA DE CREACIÓN MANUAL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    try {
        if (empty($_POST['nombre']) || empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Faltan datos obligatorios.");
        }
        // Verificar email
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmtCheck->execute([':email' => $_POST['email']]);
        if ($stmtCheck->fetch()) throw new Exception("El email ya está registrado.");

        $pdo->beginTransaction();
        $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insertar Usuario
        $sqlInsert = "INSERT INTO usuarios (nombre_completo, email, password, servicio, rol_en_servicio, activo, validado_por_admin) VALUES (:nom, :mail, :pass, :serv, :rol_serv, 1, 1)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([':nom' => $_POST['nombre'], ':mail' => $_POST['email'], ':pass' => $passHash, ':serv' => $_POST['servicio'], ':rol_serv' => $_POST['rol_servicio']]);
        $id_nuevo = $pdo->lastInsertId();

        // Asignar Rol (Si seleccionó uno, sino asignamos Servicio por defecto)
        $rol_a_asignar = !empty($_POST['rol_sistema']) ? $_POST['rol_sistema'] : null;
        
        if (!$rol_a_asignar) {
            // Si no eligió rol especial, le damos 'Servicio'
            $stmtRolDef = $pdo->query("SELECT id FROM roles WHERE nombre = 'Servicio' LIMIT 1");
            $rol_a_asignar = $stmtRolDef->fetchColumn();
        }

        if ($rol_a_asignar) {
            $stmtRol = $pdo->prepare("INSERT INTO usuario_roles (id_usuario, id_rol) VALUES (:u, :r)");
            $stmtRol->execute([':u' => $id_nuevo, ':r' => $rol_a_asignar]);
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Usuario creado.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$lista_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();

$sqlUsuarios = "
    SELECT u.id, u.nombre_completo, u.email, u.activo, u.validado_por_admin, u.servicio, u.rol_en_servicio,
           GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_nombres
    FROM usuarios u
    LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario
    LEFT JOIN roles r ON ur.id_rol = r.id
    GROUP BY u.id
    ORDER BY u.validado_por_admin ASC, u.nombre_completo ASC
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
                            <th>Servicio / Rol</th>
                            <th>Email</th>
                            <th>Roles Sistema</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="<?php echo ($u['validado_por_admin'] == 0) ? 'table-warning' : ''; ?>">
                                <td class="fw-bold"><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                                <td>
                                    <?php if($u['servicio']): ?>
                                        <small class="d-block fw-bold text-primary"><?php echo $u['servicio']; ?></small>
                                        <small class="text-muted"><?php echo $u['rol_en_servicio']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php 
                                        if ($u['roles_nombres']) {
                                            $rolesArr = explode(', ', $u['roles_nombres']);
                                            foreach($rolesArr as $rol) {
                                                echo '<span class="badge bg-info text-dark me-1">'.$rol.'</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted fst-italic">Sin rol sistema</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['validado_por_admin'] == 0): ?>
                                        <a href="admin_usuarios.php?aprobar_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="return confirm('¿Aprobar y asignar rol Servicio?');">
                                            <i class="fas fa-check"></i> APROBAR
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Activo</span>
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
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Nuevo Usuario</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3"><label>Nombre Completo *</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="mb-3"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label>Contraseña *</label><input type="password" name="password" class="form-control" required></div>
                    <div class="row">
                        <div class="col"><label>Servicio</label><input type="text" name="servicio" class="form-control"></div>
                        <div class="col"><label>Rol Local</label><select name="rol_servicio" class="form-select"><option>Personal</option><option>Responsable</option></select></div>
                    </div>
                    <div class="mt-3"><label>Rol Sistema</label>
                        <select name="rol_sistema" class="form-select">
                            <option value="">-- Automático (Servicio) --</option>
                            <?php foreach($lista_roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo $r['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>