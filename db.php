<?php
// Archivo: db.php
// Propósito: Conexión centralizada a la base de datos usando PDO.
// Este archivo se incluirá en todas las páginas que necesiten datos.

// Credenciales de Hostinger
$host = 'localhost'; // En Hostinger, el host local suele ser "localhost"
$db   = 'u415354546_deposito';
$user = 'u415354546_deposito';
$pass = 'Fmg35911@';
$charset = 'utf8mb4';

// Data Source Name (Configuración de conexión)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones de PDO para seguridad y manejo de errores
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza errores fatales si algo falla
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve los datos como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Previene inyecciones SQL reales
];

try {
    // Intentamos crear la conexión
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    

} catch (\PDOException $e) {
    // Si falla, mostramos el error (En producción esto debería ir a un log)
    echo "<h1>❌ Error de Conexión</h1>";
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>