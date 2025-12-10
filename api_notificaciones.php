<?php
// Archivo: api_notificaciones.php
// Propósito: Consultar notificaciones no leídas vía AJAX (JSON)

require 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No logueado']);
    exit;
}

$user_id = $_SESSION['user_id'];
// Para simplificar, buscamos los IDs de los roles del usuario
$mis_roles_ids = [];

// Obtenemos los IDs numéricos de los roles actuales
$stmtRoles = $pdo->prepare("SELECT id_rol FROM usuario_roles WHERE id_usuario = :id");
$stmtRoles->execute(['id' => $user_id]);
$mis_roles_ids = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

if (empty($mis_roles_ids)) {
    echo json_encode(['count' => 0, 'latest' => null]);
    exit;
}

// Convertimos array a string para SQL (ej: "1,2,5")
$roles_in = implode(',', array_map('intval', $mis_roles_ids));

// CONSULTA: Buscar notificaciones NO LEÍDAS (leida=0)
// Destinadas a MI USUARIO o a MI ROL
$sql = "SELECT * FROM notificaciones 
        WHERE leida = 0 
        AND (id_usuario_destino = $user_id OR id_rol_destino IN ($roles_in))
        ORDER BY fecha_creacion DESC LIMIT 5";

$stmt = $pdo->query($sql);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = count($notificaciones);
$latest = ($count > 0) ? $notificaciones[0] : null;

echo json_encode([
    'count' => $count,
    'latest' => $latest,
    'items' => $notificaciones
]);
?>