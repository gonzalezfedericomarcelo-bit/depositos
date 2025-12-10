<?php
// Archivo: includes/footer.php
// Propósito: Cierre de HTML y Lógica JS (CORREGIDO: ALERTA DESDE CERO)
?>
    </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. LÓGICA DE SIDEBAR
    document.addEventListener("DOMContentLoaded", function(event) {
        const sidebarToggle = document.getElementById('sidebarCollapse');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function (event) {
                event.preventDefault();
                sidebar.classList.toggle('d-none');
            });
        }
    });

    // 2. CONFIGURACIÓN DE AUDIO
    // Ruta verificada: /depositos/assets/sound/alert.mp3
    const notifSound = new Audio('/depositos/assets/sound/alert.mp3');
    
    // Desbloqueo de audio (hack para navegadores)
    document.body.addEventListener('click', function() {
        notifSound.play().then(() => {
            notifSound.pause();
            notifSound.currentTime = 0;
        }).catch(() => {});
    }, { once: true });


    // 3. SISTEMA DE NOTIFICACIONES (LÓGICA CORREGIDA)
    let lastCount = 0;
    let isFirstLoad = true; // Bandera para saber si es la primera carga

    function checkNotifications() {
        fetch('api_notificaciones.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                const list = document.getElementById('notif-list');
                const toastEl = document.getElementById('liveToast');
                const toastBody = toastEl.querySelector('.toast-body');

                // A. Actualizar Visuales (Badge y Lista)
                if (data.count > 0) {
                    badge.innerText = data.count;
                    badge.style.display = 'inline-block';
                    
                    let htmlList = '<li><h6 class="dropdown-header">Pendientes ('+data.count+')</h6></li>';
                    data.items.forEach(item => {
                        let link = item.url_destino ? item.url_destino : '#';
                        htmlList += `<li><a class="dropdown-item small py-2 text-wrap border-bottom" href="${link}">
                                        <i class="fas fa-circle text-primary me-2" style="font-size:0.5rem"></i>${item.mensaje}
                                     </a></li>`;
                    });
                    list.innerHTML = htmlList;
                } else {
                    badge.style.display = 'none';
                    list.innerHTML = '<li><h6 class="dropdown-header">Notificaciones</h6></li><li class="text-center p-2 text-muted small">No tienes mensajes nuevos</li>';
                }

                // B. LÓGICA DE ALERTA (SONIDO Y TOAST)
                // Si NO es la primera carga Y hay más mensajes que antes... ¡SUENA!
                if (!isFirstLoad && data.count > lastCount) {
                    
                    console.log("🔔 Alerta disparada: de " + lastCount + " a " + data.count);

                    // Inyectar HTML en el Toast
                    let linkDestino = data.latest && data.latest.url_destino ? data.latest.url_destino : '#';
                    let mensajeTexto = data.latest ? data.latest.mensaje : 'Tienes nuevas notificaciones';

                    toastBody.innerHTML = `
                        <a href="${linkDestino}" class="text-white text-decoration-none d-flex align-items-center w-100 h-100">
                            <i class="fas fa-bell fa-lg me-3"></i>
                            <div>
                                <strong class="d-block text-uppercase small opacity-75">¡Atención!</strong>
                                <span style="font-size: 0.95rem;">${mensajeTexto}</span>
                                <div class="mt-1 small text-white-50" style="font-size: 0.75rem;">Clic para ver <i class="fas fa-arrow-right ms-1"></i></div>
                            </div>
                        </a>
                    `;
                    
                    // Mostrar Toast
                    const toast = new bootstrap.Toast(toastEl, { delay: 10000 });
                    toast.show();

                    // Reproducir Sonido
                    notifSound.play().catch(e => console.error("Error audio:", e));
                }

                // Actualizar contadores para la próxima vuelta
                lastCount = data.count;
                isFirstLoad = false; // Ya no es la primera carga
            })
            .catch(error => console.error('Polling error:', error));
    }

    // Intervalo de 5 segundos
    setInterval(checkNotifications, 5000);
    checkNotifications();

</script>
</body>
</html>