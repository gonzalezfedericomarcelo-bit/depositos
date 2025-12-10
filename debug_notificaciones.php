<?php
// Archivo: debug_notificaciones.php
// Propósito: Verificación visual de Roles y Notificaciones

require 'db.php';
session_start();

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>🕵️ Escáner de Notificaciones</h1>";

// 1. DATOS DEL USUARIO ACTUAL
if (!isset($_SESSION['user_id'])) {
    die("<h3 style='color:red'>❌ Nadie logueado. Inicia sesión con el usuario que tiene el problema.</h3>");
}
$user_id = $_SESSION['user_id'];
echo "<h3>1. Usuario Actual</h3>";
echo "Nombre: <strong>" . $_SESSION['user_name'] . "</strong> (ID: $user_id)<br>";

// 2. ROLES ASIGNADOS AL USUARIO
echo "<h3>2. Roles de este Usuario (En Base de Datos)</h3>";
$stmtRoles = $pdo->prepare("
    SELECT r.id, r.nombre 
    FROM roles r 
    JOIN usuario_roles ur ON r.id = ur.id_rol 
    WHERE ur.id_usuario = :id
");
$stmtRoles->execute(['id' => $user_id]);
$mis_roles = $stmtRoles->fetchAll();

if (count($mis_roles) > 0) {
    echo "<ul>";
    foreach ($mis_roles as $rol) {
        echo "<li style='color:green'>ID: <strong>" . $rol['id'] . "</strong> - Nombre: <strong>" . $rol['nombre'] . "</strong></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>⚠️ Este usuario NO TIENE ROLES asignados en la tabla `usuario_roles`.</p>";
}

// 3. ROLES DEL SISTEMA (Para comparar nombres)
echo "<h3>3. Roles Disponibles en el Sistema</h3>";
$allRoles = $pdo->query("SELECT * FROM roles")->fetchAll();
echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
echo "<tr style='background:#eee'><th>ID</th><th>Nombre Exacto</th><th>¿Coincide con código?</th></tr>";
foreach ($allRoles as $r) {
    $coincide = "";
    if ($r['nombre'] == 'Encargado Logística') $coincide = "✅ Coincide con Suministros";
    if ($r['nombre'] == 'Director Médico') $coincide = "✅ Coincide con Insumos";
    echo "<tr><td>{$r['id']}</td><td>{$r['nombre']}</td><td>$coincide</td></tr>";
}
echo "</table>";

// 4. ÚLTIMAS 5 NOTIFICACIONES (Para ver si se crean)
echo "<h3>4. Últimas 5 Notificaciones Generadas (Total sistema)</h3>";
$notis = $pdo->query("SELECT * FROM notificaciones ORDER BY id DESC LIMIT 5")->fetchAll();

if (count($notis) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%'>";
    echo "<tr style='background:#eee'><th>ID</th><th>Mensaje</th><th>Destino Usuario ID</th><th>Destino ROL ID</th><th>Leída</th></tr>";
    foreach ($notis as $n) {
        // Analizamos si esta notificación le debería llegar al usuario
        $esParaMi = false;
        $motivo = "";
        
        // Chequeo por ID Usuario
        if ($n['id_usuario_destino'] == $user_id) {
            $esParaMi = true;
            $motivo = "Por ID Usuario";
        }
        
        // Chequeo por Rol
        if ($n['id_rol_destino']) {
            foreach ($mis_roles as $mr) {
                if ($mr['id'] == $n['id_rol_destino']) {
                    $esParaMi = true;
                    $motivo = "Por Rol ({$mr['nombre']})";
                }
            }
        }

        $style = $esParaMi ? "background-color: #d4edda;" : ""; // Verde si es para mí
        $leida = $n['leida'] ? "Sí" : "No";
        
        echo "<tr style='$style'>";
        echo "<td>{$n['id']}</td>";
        echo "<td>{$n['mensaje']}</td>";
        echo "<td>{$n['id_usuario_destino']}</td>";
        echo "<td>{$n['id_rol_destino']}</td>";
        echo "<td>$leida " . ($esParaMi ? "<strong>(ES PARA MÍ: $motivo)</strong>" : "") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay notificaciones en la tabla.</p>";
}

echo "</div>";
?>