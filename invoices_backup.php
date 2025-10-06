<?php
declare(strict_types=1);

/**
 * Invoices Management Page
 * Unified design matching dashboard
 */

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

require_once __DIR__ . '/includes/csrf.php';

// Initialize variables
$invoices = [];
$patients = [];
$errorMessage = null;
$successMessage = null;

// Handle POST request for adding new invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'open';
        
        if ($patient_id === 0 || $amount <= 0) {
            $errorMessage = "Patient und Betrag sind erforderlich.";
        } else {
            try {
                // Check if description column exists
                $columns = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('description', $columns)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO invoices (patient_id, amount, description, status, created_at)
                        VALUES (:patient_id, :amount, :description, :status, NOW())
                    ");
                    $stmt->execute([
                        ':patient_id' => $patient_id,
                        ':amount' => $amount,
                        ':description' => $description ?: null,
                        ':status' => $status
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO invoices (patient_id, amount, status, created_at)
                        VALUES (:patient_id, :amount, :status, NOW())
                    ");
                    $stmt->execute([
                        ':patient_id' => $patient_id,
                        ':amount' => $amount,
                        ':status' => $status
                    ]);
                }
                
                $successMessage = "✅ Rechnung wurde erfolgreich erstellt!";
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating invoice', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Erstellen der Rechnung.";
            }
        }
    }
}

// Load invoices list
try {
    // Check which columns exist
    $columns = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('description', $columns)) {
        $query = "
            SELECT i.id, i.amount, i.status, i.created_at, i.description,
                   p.name AS patient_name,
                   CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
            FROM invoices i
            JOIN patients p ON p.id = i.patient_id
            JOIN owners o ON o.id = p.owner_id
            ORDER BY i.created_at DESC
            LIMIT 200
        ";
    } else {
        $query = "
            SELECT i.id, i.amount, i.status, i.created_at,
                   p.name AS patient_name,
                   CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
            FROM invoices i
            JOIN patients p ON p.id = i.patient_id
            JOIN owners o ON o.id = p.owner_id
            ORDER BY i.created_at DESC
            LIMIT 200
        ";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load patients for dropdown
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $totalInvoices = count($invoices);
    $totalAmount = array_sum(array_column($invoices, 'amount'));
    $paidInvoices = count(array_filter($invoices, fn($i) => $i['status'] === 'paid'));
    $openInvoices = count(array_filter($invoices, fn($i) => $i['status'] === 'open'));
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading invoices', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Rechnungen.";
    $invoices = [];
    $patients = [];
    $totalInvoices = 0;
    $totalAmount = 0;
    $paidInvoices = 0;
    $openInvoices = 0;
}

// Generate CSRF token
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnungsverwaltung - Tierphysio Manager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Unified Styles -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Wrapper -->
    <div class="wrapper">
        
        <!-- Include Navigation -->
        <?php include __DIR__ . '/includes/nav.php'; ?>
        
        <!-- Include Header -->
        <?php include __DIR__ . '/includes/header.php'; ?>
        
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                
                <!-- Page Header -->
                <div class="page-breadcrumb d-flex align-items-center mb-4">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="dashboard.php"><i class="bx bx-home-alt"></i></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Rechnungen</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0">Rechnungsverwaltung</h2>
                    </div>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInvoiceModal">
                            <i class="bx bx-plus me-2"></i>Neue Rechnung
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <div class="kpi-label">Gesamt Rechnungen</div>
                            <div class="kpi-value"><?php echo $totalInvoices; ?></div>
                            <small class="text-muted">Alle Zeit</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card success">
                            <div class="kpi-label">Bezahlt</div>
                            <div class="kpi-value"><?php echo $paidInvoices; ?></div>
                            <small class="text-success">Abgeschlossen</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card warning">
                            <div class="kpi-label">Offen</div>
                            <div class="kpi-value"><?php echo $openInvoices; ?></div>
                            <small class="text-warning">Ausstehend</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card info">
                            <div class="kpi-label">Gesamtsumme</div>
                            <div class="kpi-value">€<?php echo number_format($totalAmount, 2, ',', '.'); ?></div>
                            <small class="text-muted">Alle Rechnungen</small>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts -->
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $successMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Main Content Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="card-title mb-0">Rechnungen Übersicht</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-filter-alt"></i> Filter
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-export"></i> Export
                                </button>
                            </div>
                        </div>
                        
                        <?php if (count($invoices) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Rechnung Nr.</th>
                                            <th>Datum</th>
                                            <th>Patient</th>
                                            <th>Besitzer</th>
                                            <th>Betrag</th>
                                            <th>Status</th>
                                            <th width="150">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad((string)$invoice['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['owner_name']); ?></td>
                                            <td>
                                                <strong>€<?php echo number_format($invoice['amount'], 2, ',', '.'); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($invoice['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Bezahlt</span>
                                                <?php elseif ($invoice['status'] === 'open'): ?>
                                                    <span class="badge bg-warning">Offen</span>
                                                <?php elseif ($invoice['status'] === 'overdue'): ?>
                                                    <span class="badge bg-danger">Überfällig</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($invoice['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" title="PDF">
                                                        <i class="bx bx-file-pdf"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info" title="Senden">
                                                        <i class="bx bx-send"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning" title="Bearbeiten">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" title="Löschen"
                                                            onclick="deleteInvoice(<?php echo $invoice['id']; ?>)">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bx bx-receipt display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">Keine Rechnungen vorhanden</h5>
                                <p class="text-muted">Erstellen Sie die erste Rechnung, um zu beginnen.</p>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addInvoiceModal">
                                    <i class="bx bx-plus me-2"></i>Erste Rechnung erstellen
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Include Footer -->
        <?php include __DIR__ . '/includes/footer.php'; ?>
        
    </div>
    
    <!-- Add Invoice Modal -->
    <div class="modal fade" id="addInvoiceModal" tabindex="-1" aria-labelledby="addInvoiceModalLabel" aria-hidden="true" style="z-index: 2000 !important;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInvoiceModalLabel">
                        <i class="bx bx-receipt me-2"></i>Neue Rechnung erstellen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient *</label>
                            <select class="form-select" id="patient_id" name="patient_id" required>
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['id']; ?>">
                                        <?php echo htmlspecialchars($patient['name']); ?> 
                                        (<?php echo htmlspecialchars($patient['owner_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Betrag (€) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Leistungsbeschreibung..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="open">Offen</option>
                                <option value="paid">Bezahlt</option>
                                <option value="overdue">Überfällig</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Rechnung speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
    function deleteInvoice(id) {
        if (confirm('Möchten Sie diese Rechnung wirklich löschen?')) {
            window.location.href = 'delete_invoice.php?id=' + id;
        }
    }
    
    // Auto-dismiss success alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>