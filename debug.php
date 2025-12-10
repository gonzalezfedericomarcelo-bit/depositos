<?php
// Archivo: debug.php
// Propósito: Verificar rutas de audio y forzar una notificación de prueba

require 'db.php';
session_start();

echo "<h1>🛠️ Herramienta de Diagnóstico</h1>";

// 1. VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
if (!isset($_SESSION['user_id'])) {
    die("<p style='color:red'>❌ Error: No estás logueado. Inicia sesión primero.</p>");
}
echo "<p style='color:green'>✅ Usuario logueado: " . $_SESSION['user_name'] . " (ID: " . $_SESSION['user_id'] . ")</p>";

// 2. VERIFICAR LA RUTA DEL SONIDO (Case Sensitive)
$ruta_relativa = 'assets/sound/alert.mp3';
$ruta_absoluta = __DIR__ . '/' . $ruta_relativa;

echo "<h3>🎵 Verificando Audio</h3>";
echo "Buscando en: <code>$ruta_absoluta</code><br>";

if (file_exists($ruta_absoluta)) {
    echo "<p style='color:green'>✅ <strong>¡ARCHIVO ENCONTRADO!</strong> La ruta es correcta.</p>";
    echo "<audio controls src='$ruta_relativa'></audio><br><small>Dale play para probar si se escucha.</small>";
} else {
    echo "<p style='color:red'>❌ <strong>ARCHIVO NO ENCONTRADO</strong></p>";
    echo "<ul>";
    echo "<li>Verifica que la carpeta sea <strong>assets</strong> (minúscula).</li>";
    echo "<li>Verifica que la subcarpeta sea <strong>sound</strong> (minúscula).</li>";
    echo "<li>Verifica que el archivo sea <strong>alert.mp3</strong> (minúscula).</li>";
    echo "<li>Ruta actual detectada por el sistema: " . getcwd() . "</li>";
    echo "</ul>";
}

// 3. CREAR UNA NOTIFICACIÓN DE PRUEBA
echo "<h3>🔔 Generando Notificación de Prueba</h3>";
try {
    $mensaje = "Prueba de diagnóstico " . date('H:i:s');
    $stmt = $pdo->prepare("INSERT INTO notificaciones (id_usuario_destino, mensaje, url_destino, leida) VALUES (:uid, :msj, '#', 0)");
    $stmt->execute([':uid' => $_SESSION['user_id'], ':msj' => $mensaje]);
    
    echo "<p style='color:green'>✅ Notificación insertada en la base de datos.</p>";
    echo "<p>Ahora ve al <strong><a href='dashboard.php'>DASHBOARD</a></strong>. Debería sonar y aparecer el cartel azul.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error de Base de Datos: " . $e->getMessage() . "</p>";
}
?>