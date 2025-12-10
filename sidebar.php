<?php
// Archivo: includes/sidebar.php (ACTUALIZADO CON DIRECTOR MÉDICO)
$current_page = basename($_SERVER['PHP_SELF']);

if (!function_exists('hasRole')) {
    function hasRole($allowed_roles, $user_roles) {
        if (in_array('Administrador', $user_roles)) return true;
        $intersection = array_intersect($allowed_roles, $user_roles);
        return count($intersection) > 0;
    }
}
$my_roles = $_SESSION['user_roles'] ?? [];
?>

<nav id="sidebar" class="sidebar d-none d-md-block bg-dark text-white">
    <div class="brand p-3 text-center border-bottom border-secondary">
        <h4 class="m-0"><i class="fas fa-hospital-symbol me-2"></i>ACTIS</h4>
        <small class="text-muted">Gestión Integral</small>
    </div>

    <ul class="list-unstyled components p-2">
        <li><div class="section-title">General</div></li>
        <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2"></i> Inicio</a></li>

        <?php if (hasRole(['Administrador', 'Compras', 'Encargado Logística', 'Encargado Depósito Insumos', 'Auxiliar', 'Director Médico'], $my_roles)): ?>
        <li><div class="section-title">Insumos Médicos</div></li>
        <li><a href="insumos_stock.php" class="<?php echo ($current_page == 'insumos_stock.php') ? 'active' : ''; ?>"><i class="fas fa-pills me-2"></i> Stock & Inventario</a></li>
        <li><a href="insumos_compras.php" class="<?php echo ($current_page == 'insumos_compras.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar me-2"></i> Órdenes Compra</a></li>
        <li><a href="insumos_entregas.php" class="<?php echo ($current_page == 'insumos_entregas.php') ? 'active' : ''; ?>"><i class="fas fa-hand-holding-medical me-2"></i> Entregas / Salidas</a></li>
        <?php endif; ?>

        <?php if (hasRole(['Administrador', 'Compras', 'Encargado Logística', 'Encargado Depósito Suministros', 'Auxiliar'], $my_roles)): ?>
        <li><div class="section-title">Suministros Grales.</div></li>
        <li><a href="suministros_stock.php" class="<?php echo ($current_page == 'suministros_stock.php') ? 'active' : ''; ?>"><i class="fas fa-boxes me-2"></i> Stock & Inventario</a></li>
        <li><a href="suministros_compras.php" class="<?php echo ($current_page == 'suministros_compras.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list me-2"></i> Órdenes Compra</a></li>
        <li><a href="suministros_entregas.php" class="<?php echo ($current_page == 'suministros_entregas.php') ? 'active' : ''; ?>"><i class="fas fa-dolly me-2"></i> Entregas / Salidas</a></li>
        <?php endif; ?>

        <?php if (hasRole(['Administrador'], $my_roles)): ?>
        <li><div class="section-title">Configuración</div></li>
        <li><a href="admin_usuarios.php" class="<?php echo ($current_page == 'admin_usuarios.php') ? 'active' : ''; ?>"><i class="fas fa-users-cog me-2"></i> Usuarios y Roles</a></li>
        <li><a href="admin_auditoria.php" class="<?php echo ($current_page == 'admin_auditoria.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt me-2"></i> Auditoría / Seguridad</a></li>
        <li><a href="admin_sistema.php" class="<?php echo ($current_page == 'admin_sistema.php') ? 'active' : ''; ?>"><i class="fas fa-cogs me-2"></i> Ajustes Sistema</a></li>
        <?php endif; ?>

        <li class="mt-4 border-top border-secondary pt-2">
            <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a>
        </li>
    </ul>
</nav>