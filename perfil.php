<?php
// Archivo: perfil.php
// Propósito: Gestión de perfil de usuario y Firma Digital (Visualización + Edición)

require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$id_usuario = $_SESSION['user_id'];
$mensaje = "";

// 1. PROCESAR GUARDADO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        
        // A. Actualizar Contraseña (si se escribió algo)
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
            $stmt->execute([':p' => $pass, ':id' => $id_usuario]);
        }

        // B. Actualizar Firma (si se dibujó algo nuevo)
        if (!empty($_POST['firma_base64'])) {
            $dir = 'uploads/firmas/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            // Decodificar base64
            $data_uri = $_POST['firma_base64'];
            $encoded_image = explode(",", $data_uri)[1];
            $decoded_image = base64_decode($encoded_image);
            
            // Crear nombre único para evitar caché
            $filename = 'firma_' . $id_usuario . '_' . time() . '.png';
            $file_path = $dir . $filename;
            
            // Guardar archivo físico
            file_put_contents($file_path, $decoded_image);
            
            // Guardar ruta en BD
            $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = :f WHERE id = :id");
            $stmt->execute([':f' => $file_path, ':id' => $id_usuario]);
        }

        $pdo->commit();
        $mensaje = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> Perfil actualizado correctamente. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Error al guardar: ' . $e->getMessage() . '</div>';
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Obtener datos frescos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id_usuario]);
$u = $stmt->fetch();
?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-primary">Mi Perfil</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Gestión de Cuenta y Firma Digital</li>
    </ol>
    
    <?php echo $mensaje; ?>

    <form method="POST" id="formPerfil">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4 border-primary shadow-sm h-100">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="fas fa-user-circle me-2"></i> Datos Personales
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Nombre Completo</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($u['nombre_completo']); ?>" disabled readonly>
                            <div class="form-text small">Para cambiar tu nombre, contacta al administrador.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Email / Usuario</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($u['email']); ?>" disabled readonly>
                        </div>

                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">Cambiar Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="Escribe nueva contraseña (opcional)">
                            <div class="form-text">Déjalo en blanco si no quieres cambiarla.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4 border-success shadow-sm h-100">
                    <div class="card-header bg-success text-white fw-bold">
                        <i class="fas fa-signature me-2"></i> Firma Digital
                    </div>
                    <div class="card-body d-flex flex-column">
                        
                        <?php if (!empty($u['firma_digital']) && file_exists($u['firma_digital'])): ?>
                            <div class="mb-4 text-center p-3 bg-light rounded border">
                                <label class="fw-bold d-block text-success mb-2"><i class="fas fa-check me-1"></i> Firma Registrada Actual:</label>
                                <img src="<?php echo $u['firma_digital']; ?>?t=<?php echo time(); ?>" alt="Firma Actual" class="img-fluid" style="max-height: 100px; border: 1px dashed #ccc;">
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning small text-center mb-3">
                                <i class="fas fa-exclamation-circle"></i> No tienes firma registrada. Por favor, firma abajo.
                            </div>
                        <?php endif; ?>

                        <div class="flex-grow-1">
                            <label class="form-label fw-bold">Dibujar Nueva Firma:</label>
                            <div class="signature-container" style="position: relative; width: 100%; height: 200px; border: 2px dashed #aaa; background-color: #fff; border-radius: 8px;">
                                <canvas id="signature-pad" style="width: 100%; height: 100%; touch-action: none;"></canvas>
                                <div class="text-muted small position-absolute top-50 start-50 translate-middle pointer-events-none opacity-25" style="pointer-events: none;">Firme Aquí</div>
                            </div>
                            
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="fas fa-info-circle"></i> Use el mouse o el dedo.</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-signature">
                                    <i class="fas fa-eraser me-1"></i> Limpiar
                                </button>
                            </div>
                            
                            <input type="hidden" name="firma_base64" id="firma_base64">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 border-0">
            <div class="card-body text-end">
                <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow">
                    <i class="fas fa-save me-2"></i> Guardar Todo
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var canvas = document.getElementById('signature-pad');
        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)', // Fondo transparente
            penColor: 'rgb(0, 0, 0)'
        });

        // Ajuste de tamaño responsive para el canvas
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            // Guardamos el contenido actual si queremos que no se borre al rotar pantalla (opcional)
            // En este caso simple, al redimensionar se limpia, es lo estándar en web simple.
            
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            
            // signaturePad.clear(); // Opcional: limpiar al redimensionar para evitar distorsión
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        // Botón limpiar
        document.getElementById('clear-signature').addEventListener('click', function () {
            signaturePad.clear();
        });

        // Antes de enviar, volcar la firma al input hidden
        document.getElementById('formPerfil').addEventListener('submit', function (e) {
            if (!signaturePad.isEmpty()) {
                // Solo si el usuario dibujó algo nuevo, lo guardamos.
                // Si está vacío, se asume que mantiene la firma vieja (si existe).
                var data = signaturePad.toDataURL('image/png');
                document.getElementById('firma_base64').value = data;
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>