<div class="content-wrapper">
    <nav class="navbar navbar-expand-lg top-navbar">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-light text-primary d-md-none shadow-none me-3">
                <i class="fas fa-bars fa-lg"></i>
            </button>

            <div class="d-flex align-items-center ms-auto">
                
                <div class="dropdown me-3">
                    <a href="#" class="text-secondary position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 1.2rem;">
                        <i class="fas fa-bell"></i>
                        <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; display: none;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="notifDropdown" id="notif-list" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notificaciones</h6></li>
                        <li class="text-center p-2 text-muted small">Sin novedades</li>
                    </ul>
                </div>

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                        <div class="d-flex flex-column text-end me-3 d-none d-sm-flex">
                            <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?php echo htmlspecialchars($nombre_usuario_global); ?></span>
                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo htmlspecialchars($rol_principal_global); ?></small>
                        </div>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-user"></i></div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                        <li><h6 class="dropdown-header text-uppercase small text-muted">Cuenta</h6></li>
                        <li><a class="dropdown-item py-2" href="perfil.php"><i class="fas fa-signature me-2 text-primary"></i> Mi Perfil</a></li>
                        <?php if (in_array('Administrador', $roles_global)): ?>
                        <li><a class="dropdown-item py-2" href="admin_sistema.php"><i class="fas fa-cogs me-2 text-secondary"></i> Configuración</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div id="liveToast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-bell me-2"></i> <strong id="toast-text">Nueva Notificación</strong>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <div class="main-content flex-grow-1">