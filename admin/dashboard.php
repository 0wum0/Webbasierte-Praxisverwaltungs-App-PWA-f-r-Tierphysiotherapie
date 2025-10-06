<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
$pdo = db();

// Safety check
if (!$pdo) {
    die("Database connection unavailable.");
}

// Auth Guard
auth_require_admin();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Statistiken laden
try {
    // Patienten
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $totalPatients = (int)$stmt->fetchColumn();
    
    // Besitzer
    $stmt = $pdo->query("SELECT COUNT(*) FROM owners");
    $totalOwners = (int)$stmt->fetchColumn();
    
    // Termine heute
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $appointmentsToday = (int)$stmt->fetchColumn();
    
    // Offene Rechnungen
    $stmt = $pdo->query("SELECT COUNT(*), SUM(amount) FROM invoices WHERE status = 'open'");
    $invoiceData = $stmt->fetch();
    $openInvoices = (int)$invoiceData[0];
    $openInvoicesAmount = (float)($invoiceData[1] ?? 0);
    
    // Umsatz diesen Monat
    $stmt = $pdo->query("
        SELECT SUM(amount) 
        FROM invoices 
        WHERE status = 'paid' 
          AND YEAR(updated_at) = YEAR(CURDATE())
          AND MONTH(updated_at) = MONTH(CURDATE())
    ");
    $revenueMonth = (float)($stmt->fetchColumn() ?: 0);
    
    // Letzte Aktivitäten (aus Audit Log)
    $stmt = $pdo->prepare("
        SELECT al.*, au.name as admin_name
        FROM audit_log al
        LEFT JOIN admin_users au ON al.admin_user_id = au.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
    
    // Admin-Benutzer
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE is_active = 1");
    $activeAdmins = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    logError('Failed to load dashboard statistics', ['error' => $e->getMessage()]);
    $totalPatients = $totalOwners = $appointmentsToday = $openInvoices = $activeAdmins = 0;
    $openInvoicesAmount = $revenueMonth = 0.0;
    $recentActivities = [];
}

require_once __DIR__ . '/partials/head.php';
?>

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<main class="admin-content">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1>Dashboard</h1>
        <p class="text-muted">Willkommen zurück, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Patienten</p>
                            <h3 class="mb-0"><?= number_format($totalPatients, 0, ',', '.') ?></h3>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Besitzer</p>
                            <h3 class="mb-0"><?= number_format($totalOwners, 0, ',', '.') ?></h3>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Termine heute</p>
                            <h3 class="mb-0"><?= $appointmentsToday ?></h3>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Offene Rechnungen</p>
                            <h3 class="mb-0"><?= $openInvoices ?></h3>
                            <small class="text-muted"><?= number_format($openInvoicesAmount, 2, ',', '.') ?> €</small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue & Admins -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-currency-euro text-success me-2"></i>
                        Umsatz diesen Monat
                    </h5>
                    <h2 class="text-success mb-0"><?= number_format($revenueMonth, 2, ',', '.') ?> €</h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-people text-primary me-2"></i>
                        Aktive Admins
                    </h5>
                    <h2 class="text-primary mb-0"><?= $activeAdmins ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Letzte Aktivitäten
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentActivities)): ?>
                        <div class="text-center text-muted py-4">
                            Keine Aktivitäten vorhanden
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Zeitpunkt</th>
                                        <th>Admin</th>
                                        <th>Aktion</th>
                                        <th>Beschreibung</th>
                                        <th>IP-Adresse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d.m.Y H:i', strtotime($activity['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($activity['admin_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-primary">
                                                    <?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($activity['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($activity['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <a href="audit_log.php" class="btn btn-sm btn-outline-primary">
                        Vollständiges Audit-Log anzeigen
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Schnellzugriff
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="admin_users.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-people me-2"></i>
                                Admins verwalten
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="roles_permissions.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-shield-lock me-2"></i>
                                Rollen & Rechte
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="smtp_settings.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-envelope me-2"></i>
                                SMTP-Einstellungen
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="backup.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-cloud-arrow-up me-2"></i>
                                Backup erstellen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastContainer"></div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
