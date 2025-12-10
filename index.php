<?php
// Archivo: index.php (LOGIN REDISEÑADO)
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Complete los campos.";
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre_completo, password, activo FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['activo'] == 0) {
                 $error = "Cuenta desactivada.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $stmtRoles = $pdo->prepare("SELECT r.nombre FROM roles r JOIN usuario_roles ur ON r.id = ur.id_rol WHERE ur.id_usuario = :id");
                $stmtRoles->execute(['id' => $user['id']]);
                $_SESSION['user_roles'] = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Policlínica ACTIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Manrope', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, #14b8a6, #0f766e);
        }
        .brand-logo { text-align: center; margin-bottom: 30px; color: #0f766e; }
        .brand-logo i { font-size: 3rem; margin-bottom: 10px; display: block; }
        .form-control {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1); background: #fff; }
        .btn-login {
            background: #0f766e; border: none; padding: 12px;
            border-radius: 8px; font-weight: 700; width: 100%;
            margin-top: 10px; transition: all 0.3s;
        }
        .btn-login:hover { background: #115e59; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container px-4">
        <div class="login-card mx-auto">
            <div class="brand-logo">
                <i class="fa-solid fa-hospital-user"></i>
                <h3 class="fw-bold">ACTIS</h3>
                <p class="text-muted small mb-0">Gestión Integral</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="alert alert-danger text-center py-2 small border-0 bg-danger bg-opacity-10 text-danger mb-3"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                    <input type="email" class="form-control" name="email" required placeholder="usuario@actis.com">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Contraseña</label>
                    <input type="password" class="form-control" name="password" required placeholder="••••••">
                </div>
                <button type="submit" class="btn btn-primary btn-login">INGRESAR</button>
            </form>
            
            <div class="mt-4 text-center">
                <small class="text-muted">© <?php echo date('Y'); ?> Policlínica ACTIS</small>
            </div>
        </div>
    </div>
</body>
</html>