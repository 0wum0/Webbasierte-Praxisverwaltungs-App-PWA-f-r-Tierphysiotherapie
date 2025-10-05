    <!-- Admin Footer -->
    <footer class="admin-footer bg-white border-top py-3 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-muted">
                        © <?= date('Y') ?> Tierphysio Admin Panel - Alle Rechte vorbehalten
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-muted">
                        Version 1.0.0 | 
                        <a href="https://github.com/tierphysio" target="_blank" class="text-decoration-none">
                            Dokumentation
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Custom JS -->
    <script src="assets/js/admin.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.admin-sidebar')?.classList.toggle('collapsed');
            document.querySelector('.admin-content')?.classList.toggle('expanded');
        });
        
        // Benachrichtigungen automatisch ausblenden
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
