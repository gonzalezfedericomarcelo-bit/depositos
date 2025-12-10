<?php
// Archivo: fix_pass.php
require 'db.php';

// 1. Definimos la contraseña que queremos usar
$password_texto_plano = "admin123";

// 2. La encriptamos usando la configuración de TU servidor actual
$password_encriptada = password_hash($password_texto_plano, PASSWORD_DEFAULT);

// 3. Actualizamos el usuario admin en la base de datos
try {
    $sql = "UPDATE usuarios SET password = :pass WHERE email = 'admin@actis.com'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pass' => $password_encriptada]);
    
    echo "<h1>✅ ¡Reparación Exitosa!</h1>";
    echo "<p>La contraseña para <b>admin@actis.com</b> ha sido actualizada a: <b>admin123</b></p>";
    echo "<p>El hash generado fue: " . $password_encriptada . "</p>";
    echo "<br><a href='index.php'>Volver al Login e intentar entrar</a>";
    
} catch (PDOException $e) {
    echo "❌ Error actualizando: " . $e->getMessage();
}
?>