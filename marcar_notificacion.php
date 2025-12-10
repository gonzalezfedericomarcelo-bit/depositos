<?php
// Archivo: marcar_notificacion.php
// Propósito: Marcar notificación como leída y redirigir al destino

require 'db.php';
session_start();

if (isset($_GET['id']) && isset($_GET['url'])) {
    $id_notificacion = $_GET['id'];
    $url_destino = $_GET['url'];
    $id_usuario = $_SESSION['user_id'];

    // Validar y Marcar como Leída
    // (Solo marcamos si el usuario es el dueño o tiene el rol, pero para simplificar hacemos update directo por ID)
    try {
        $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = :id");
        $stmt->execute([':id' => $id_notificacion]);
    } catch (Exception $e) {
        // Si falla, no importa, redirigimos igual
    }

    // Redirigir al destino final (ej: ver la orden)
    header("Location: " . $url_destino);
    exit;
} else {
    // Si faltan datos, volver al dashboard
    header("Location: dashboard.php");
    exit;
}
?>