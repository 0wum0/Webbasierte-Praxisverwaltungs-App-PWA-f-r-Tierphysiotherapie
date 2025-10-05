<!-- Admin Sidebar -->
<aside class="admin-sidebar bg-dark text-white position-fixed h-100" style="width: var(--admin-sidebar-width); z-index: 1050;">
    <div class="d-flex flex-column h-100">
        <!-- Logo -->
        <div class="p-3 border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
                <img src="../assets/images/logo-icon.png" alt="Logo" style="height: 32px;">
                <div>
                    <div class="fw-bold">Tierphysio</div>
                    <small class="text-muted">Admin Panel</small>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-grow-1 overflow-auto py-3">
            <ul class="nav flex-column">
                <!-- Übersicht -->
                <li class="nav-item px-3 mb-2">
                    <small class="text-muted text-uppercase fw-semibold">Übersicht</small>
                </li>
                <li class="nav-item">
                    <a href="index.php" class="nav-link text-white <?= ($currentPage ?? '') === 'dashboard' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="system_status.php" class="nav-link text-white <?= ($currentPage ?? '') === 'system_status' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-hdd me-2"></i>Systemstatus
                    </a>
                </li>
                
                <!-- Stammdaten -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Stammdaten</small>
                </li>
                <li class="nav-item">
                    <a href="practice_profile.php" class="nav-link text-white <?= ($currentPage ?? '') === 'practice_profile' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-building me-2"></i>Praxisprofil
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rates_taxes.php" class="nav-link text-white <?= ($currentPage ?? '') === 'rates_taxes' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-currency-euro me-2"></i>Stundensatz & MwSt.
                    </a>
                </li>
                <li class="nav-item">
                    <a href="branding.php" class="nav-link text-white <?= ($currentPage ?? '') === 'branding' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-palette me-2"></i>Branding
                    </a>
                </li>
                
                <!-- Benutzer & Rollen -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Benutzer & Rollen</small>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link text-white <?= ($currentPage ?? '') === 'admin_users' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-people me-2"></i>Admins verwalten
                    </a>
                </li>
                <li class="nav-item">
                    <a href="roles_permissions.php" class="nav-link text-white <?= ($currentPage ?? '') === 'roles_permissions' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-shield-lock me-2"></i>Rollen & Rechte
                    </a>
                </li>
                
                <!-- Daten -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Daten</small>
                </li>
                <li class="nav-item">
                    <a href="manage_patients.php" class="nav-link text-white <?= ($currentPage ?? '') === 'manage_patients' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-heart-pulse me-2"></i>Patienten
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_owners.php" class="nav-link text-white <?= ($currentPage ?? '') === 'manage_owners' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-person me-2"></i>Besitzer
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_appointments.php" class="nav-link text-white <?= ($currentPage ?? '') === 'manage_appointments' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-calendar-event me-2"></i>Termine
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_notes.php" class="nav-link text-white <?= ($currentPage ?? '') === 'manage_notes' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-journal-text me-2"></i>Notizen
                    </a>
                </li>
                
                <!-- Abrechnung -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Abrechnung</small>
                </li>
                <li class="nav-item">
                    <a href="invoice_layout.php" class="nav-link text-white <?= ($currentPage ?? '') === 'invoice_layout' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-file-earmark-text me-2"></i>Rechnungs-Layout
                    </a>
                </li>
                <li class="nav-item">
                    <a href="invoice_settings.php" class="nav-link text-white <?= ($currentPage ?? '') === 'invoice_settings' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-receipt me-2"></i>Nummernkreis
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payment_methods.php" class="nav-link text-white <?= ($currentPage ?? '') === 'payment_methods' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-credit-card me-2"></i>Zahlungsarten
                    </a>
                </li>
                
                <!-- Integrationen -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Integrationen</small>
                </li>
                <li class="nav-item">
                    <a href="smtp_settings.php" class="nav-link text-white <?= ($currentPage ?? '') === 'smtp_settings' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-envelope me-2"></i>SMTP
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pdf_settings.php" class="nav-link text-white <?= ($currentPage ?? '') === 'pdf_settings' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-file-pdf me-2"></i>PDF
                    </a>
                </li>
                <li class="nav-item">
                    <a href="backup.php" class="nav-link text-white <?= ($currentPage ?? '') === 'backup' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-cloud-arrow-up me-2"></i>Backup
                    </a>
                </li>
                
                <!-- Protokolle -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Protokolle</small>
                </li>
                <li class="nav-item">
                    <a href="audit_log.php" class="nav-link text-white <?= ($currentPage ?? '') === 'audit_log' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-journal-code me-2"></i>Audit-Log
                    </a>
                </li>
                <li class="nav-item">
                    <a href="login_history.php" class="nav-link text-white <?= ($currentPage ?? '') === 'login_history' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-clock-history me-2"></i>Login-Historie
                    </a>
                </li>
                
                <!-- Entwicklung -->
                <li class="nav-item px-3 mb-2 mt-3">
                    <small class="text-muted text-uppercase fw-semibold">Entwicklung</small>
                </li>
                <li class="nav-item">
                    <a href="maintenance.php" class="nav-link text-white <?= ($currentPage ?? '') === 'maintenance' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-tools me-2"></i>Wartungsmodus
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cache.php" class="nav-link text-white <?= ($currentPage ?? '') === 'cache' ? 'active bg-primary' : '' ?>">
                        <i class="bi bi-trash me-2"></i>Cache leeren
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Zurück zur App -->
        <div class="p-3 border-top border-secondary">
            <a href="../dashboard.php" class="btn btn-outline-light btn-sm w-100" target="_blank">
                <i class="bi bi-box-arrow-up-right me-2"></i>Zur App
            </a>
        </div>
    </div>
</aside>

<style>
    .admin-sidebar .nav-link {
        padding: 0.6rem 1rem;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }
    
    .admin-sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-left-color: var(--admin-primary);
    }
    
    .admin-sidebar .nav-link.active {
        border-left-color: var(--admin-primary);
    }
</style>
