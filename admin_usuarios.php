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

// --- NUEVO: LÓGICA DE APROBACIÓN RÁPIDA ---
if (isset($_GET['aprobar_id'])) {
    try {
        $id_aprob = $_GET['aprobar_id'];
        
        // Activamos la validación
        $stmtUpd = $pdo->prepare("UPDATE usuarios SET validado_por_admin = 1 WHERE id = :id");
        $stmtUpd->execute([':id' => $id_aprob]);
        
        // Notificamos al usuario
        $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino) VALUES (?, ?, ?)")
            ->execute([$id_aprob, "¡Tu cuenta ha sido aprobada! Ya puedes ingresar.", "dashboard.php"]);
            
        $mensaje = '<div class="alert alert-success alert-dismissible fade show">✅ Usuario aprobado exitosamente.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
// ------------------------------------------

// LÓGICA DE GUARDADO (Nuevo Usuario Rápido - Lógica original mantenida)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    // ... (Tu código original de creación manual se mantiene igual aquí) ...
    // Solo recuerda agregar 'validado_por_admin' => 1 en el INSERT original si lo modificaste
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$lista_roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();

// --- MODIFICADO: Agregamos validado_por_admin y servicio al SELECT ---
$sqlUsuarios = "
    SELECT u.id, u.nombre_completo, u.email, u.activo, u.validado_por_admin, u.servicio, u.rol_en_servicio,
           GROUP_CONCAT(r.nombre SEPARATOR ', ') as roles_nombres
    FROM usuarios u
    LEFT JOIN usuario_roles ur ON u.id = ur.id_usuario
    LEFT JOIN roles r ON ur.id_rol = r.id
    GROUP BY u.id
    ORDER BY u.validado_por_admin ASC, u.nombre_completo ASC
";
// Nota: ORDER BY validado_por_admin ASC pone los pendientes (0) arriba de todo.

$usuarios = $pdo->query($sqlUsuarios)->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Administración de Usuarios</h1>
    <?php echo $mensaje; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div><i class="fas fa-users-cog me-1"></i> Personal Registrado</div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                <i class="fas fa-user-plus"></i> Nuevo Usuario (Manual)
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
                            <th>Validación</th> <th>Acciones</th>
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
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($u['validado_por_admin'] == 0): ?>
                                        <a href="admin_usuarios.php?aprobar_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-success fw-bold shadow-sm" onclick="return confirm('¿Aprobar ingreso de este usuario?');">
                                            <i class="fas fa-check"></i> APROBAR
                                        </a>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle text-success"></i> OK
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

<?php include 'includes/footer.php'; ?>