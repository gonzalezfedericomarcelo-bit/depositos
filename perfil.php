<?php
// Archivo: perfil.php (RESTAURADO)
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$id_usuario = $_SESSION['user_id'];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
            $stmt->execute([':p' => $pass, ':id' => $id_usuario]);
        }
        if (!empty($_POST['firma_base64'])) {
            $dir = 'uploads/firmas/';
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['firma_base64']));
            $file = $dir . 'firma_' . $id_usuario . '_' . time() . '.png';
            file_put_contents($file, $data);
            $stmt = $pdo->prepare("UPDATE usuarios SET firma_digital = :f WHERE id = :id");
            $stmt->execute([':f' => $file, ':id' => $id_usuario]);
        }
        $pdo->commit();
        $mensaje = '<div class="alert alert-success">✅ Perfil actualizado.</div>';
    } catch (Exception $e) { $pdo->rollBack(); $mensaje = '<div class="alert alert-danger">Error.</div>'; }
}

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
$u = $pdo->query("SELECT * FROM usuarios WHERE id = $id_usuario")->fetch();
?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<div class="container-fluid">
    <h1 class="mt-4">Mi Perfil</h1>
    <?php echo $mensaje; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" id="formPerfil">
                <div class="mb-3"><label>Nombre</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($u['nombre_completo']); ?>" disabled></div>
                <div class="mb-3"><label>Email</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" disabled></div>
                <div class="mb-3"><label>Nueva Contraseña</label><input type="password" name="password" class="form-control" placeholder="Dejar vacío para mantener"></div>
                <hr>
                <label>Firma Digital:</label>
                <div style="border:1px solid #ccc; height:200px; width:100%; background:#fff;">
                    <canvas id="signature-pad" style="width:100%; height:100%;"></canvas>
                </div>
                <div class="mt-2 text-end"><button type="button" class="btn btn-secondary btn-sm" id="clear-signature">Borrar</button></div>
                <input type="hidden" name="firma_base64" id="firma_base64">
                <br>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>
<script>
    var canvas = document.getElementById('signature-pad');
    function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
    }
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();
    var signaturePad = new SignaturePad(canvas, { backgroundColor:'rgba(255,255,255,0)' });
    document.getElementById('clear-signature').addEventListener('click', function(){ signaturePad.clear(); });
    document.getElementById('formPerfil').addEventListener('submit', function(e){
        if (!signaturePad.isEmpty()) document.getElementById('firma_base64').value = signaturePad.toDataURL();
    });
</script>
<?php include 'includes/footer.php'; ?>