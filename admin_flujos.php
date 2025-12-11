<?php
// Archivo: admin_flujos.php
require 'db.php';
session_start();

if (!in_array('Administrador', $_SESSION['user_roles'])) { die("Acceso denegado"); }

$proceso_seleccionado = $_GET['proceso'] ?? 'adquisicion_insumos';

// GUARDAR CAMBIOS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Guardar Iniciador
        if (isset($_POST['iniciador'])) {
            $stmtInit = $pdo->prepare("UPDATE config_procesos SET id_rol_iniciador = :rol WHERE codigo = :cod");
            $stmtInit->execute([':rol' => $_POST['iniciador'], ':cod' => $proceso_seleccionado]);
        }

        // 2. Guardar Pasos
        if (isset($_POST['pasos'])) {
            foreach ($_POST['pasos'] as $id_config => $datos) {
                $stmt = $pdo->prepare("UPDATE config_flujos SET paso_orden = :ord, id_rol_responsable = :rol, etiqueta_estado = :etiq WHERE id = :id");
                $stmt->execute([':ord'=>$datos['orden'], ':rol'=>$datos['rol'], ':etiq'=>$datos['etiqueta'], ':id'=>$id_config]);
            }
        }
        $pdo->commit();
        $msg = '<div class="alert alert-success">Configuración guardada exitosamente.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

$roles = $pdo->query("SELECT * FROM roles ORDER BY nombre ASC")->fetchAll();
// Agregamos opción "El Solicitante" para los pasos de cierre
$roles_extendidos = $roles;
$roles_extendidos[] = ['id' => 0, 'nombre' => '--- EL SOLICITANTE (Servicio) ---'];

// Cargar Config
$proc_info = $pdo->prepare("SELECT * FROM config_procesos WHERE codigo = :c");
$proc_info->execute([':c' => $proceso_seleccionado]);
$info_proceso = $proc_info->fetch();

$pasos = $pdo->prepare("SELECT * FROM config_flujos WHERE nombre_proceso = :proc ORDER BY paso_orden ASC");
$pasos->execute([':proc' => $proceso_seleccionado]);
$lista_pasos = $pasos->fetchAll();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Flujos</h1>
    <?php if(isset($msg)) echo $msg; ?>

    <div class="btn-group mb-4 w-100">
        <a href="?proceso=adquisicion_insumos" class="btn btn-outline-primary <?php echo ($proceso_seleccionado=='adquisicion_insumos')?'active':''; ?>">Compras Médicas</a>
        <a href="?proceso=movimiento_insumos" class="btn btn-outline-info <?php echo ($proceso_seleccionado=='movimiento_insumos')?'active':''; ?>">Entregas Médicas</a>
        <a href="?proceso=adquisicion_suministros" class="btn btn-outline-success <?php echo ($proceso_seleccionado=='adquisicion_suministros')?'active':''; ?>">Compras Suministros</a>
        <a href="?proceso=movimiento_suministros" class="btn btn-outline-warning <?php echo ($proceso_seleccionado=='movimiento_suministros')?'active':''; ?>">Entregas Suministros</a>
    </div>

    <form method="POST">
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                Configuración: <?php echo $info_proceso['nombre']; ?>
            </div>
            <div class="card-body">
                <div class="row mb-4 align-items-center bg-light p-3 rounded mx-0">
                    <div class="col-md-2 fw-bold text-end">QUIÉN INICIA:</div>
                    <div class="col-md-6">
                        <select name="iniciador" class="form-select border-primary fw-bold">
                            <option value="0" <?php echo ($info_proceso['id_rol_iniciador'] == 0) ? 'selected' : ''; ?>>Cualquier Responsable de Servicio</option>
                            <?php foreach($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo ($info_proceso['id_rol_iniciador'] == $r['id']) ? 'selected' : ''; ?>>
                                    Solo: <?php echo $r['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-muted small">Define qué rol tiene acceso al formulario de solicitud.</div>
                </div>

                <h5 class="text-secondary border-bottom pb-2">Cadena de Aprobación e Intervención</h5>
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr><th width="50">Orden</th><th>Nombre del Estado (Visible)</th><th>Responsable del Paso</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($lista_pasos as $p): ?>
                        <tr>
                            <td><input type="number" name="pasos[<?php echo $p['id']; ?>][orden]" class="form-control text-center fw-bold" value="<?php echo $p['paso_orden']; ?>"></td>
                            <td><input type="text" name="pasos[<?php echo $p['id']; ?>][etiqueta]" class="form-control" value="<?php echo $p['etiqueta_estado']; ?>"></td>
                            <td>
                                <select name="pasos[<?php echo $p['id']; ?>][rol]" class="form-select">
                                    <?php foreach($roles_extendidos as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo ($r['id'] == $p['id_rol_responsable']) ? 'selected' : ''; ?>>
                                            <?php echo $r['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Guardar Flujo</button>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>