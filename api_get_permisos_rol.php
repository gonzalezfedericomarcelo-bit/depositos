<?php
// Archivo: api_get_permisos_rol.php
require 'db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id_permiso FROM rol_permisos WHERE id_rol = ?");
        $stmt->execute([$id]);
        // Devolvemos una lista simple de IDs: [1, 5, 8]
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>