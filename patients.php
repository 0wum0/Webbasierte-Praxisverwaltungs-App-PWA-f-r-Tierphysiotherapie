<?php
declare(strict_types=1);

/**
 * Patients Management Page
 * Unified design matching dashboard
 */

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

require_once __DIR__ . '/includes/csrf.php';

// Initialize variables
$patients = [];
$owners = [];
$errorMessage = null;
$successMessage = null;
$species = ['Hund', 'Katze', 'Pferd', 'Kaninchen', 'Meerschweinchen', 'Vogel', 'Reptil', 'Andere'];

// Handle POST request for adding new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $owner_id = (int)($_POST['owner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $species = trim($_POST['species'] ?? '');
        $breed = trim($_POST['breed'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $weight = (float)($_POST['weight'] ?? 0);
        
        if (empty($name) || empty($species) || $owner_id === 0) {
            $errorMessage = "Name, Tierart und Besitzer sind erforderlich.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO patients (owner_id, name, species, breed, age, weight, created_at)
                    VALUES (:owner_id, :name, :species, :breed, :age, :weight, NOW())
                ");
                $stmt->execute([
                    ':owner_id' => $owner_id,
                    ':name' => $name,
                    ':species' => $species,
                    ':breed' => $breed ?: null,
                    ':age' => $age > 0 ? $age : null,
                    ':weight' => $weight > 0 ? $weight : null
                ]);
                
                $successMessage = "✅ Patient wurde erfolgreich angelegt!";
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating patient', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Anlegen des Patienten.";
            }
        }
    }
}

// Search functionality
$searchQuery = $_GET['search'] ?? '';

// Load patients list
try {
    $query = "
        SELECT p.*, 
               CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name,
               o.email AS owner_email,
               o.phone AS owner_phone,
               (SELECT COUNT(*) FROM appointments WHERE patient_id = p.id) AS appointment_count,
               (SELECT COUNT(*) FROM invoices WHERE patient_id = p.id) AS invoice_count
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
    ";
    
    if ($searchQuery) {
        $query .= " WHERE p.name LIKE :search OR o.firstname LIKE :search OR o.lastname LIKE :search";
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT 200";
    
    $stmt = $pdo->prepare($query);
    if ($searchQuery) {
        $stmt->execute([':search' => '%' . $searchQuery . '%']);
    } else {
        $stmt->execute();
    }
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load owners for dropdown
    $stmt = $pdo->prepare("
        SELECT id, CONCAT_WS(' ', firstname, lastname) AS name 
        FROM owners 
        ORDER BY firstname, lastname
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $totalPatients = count($patients);
    $dogCount = count(array_filter($patients, fn($p) => $p['species'] === 'Hund'));
    $catCount = count(array_filter($patients, fn($p) => $p['species'] === 'Katze'));
    $otherCount = $totalPatients - $dogCount - $catCount;
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading patients', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Patienten.";
    $patients = [];
    $owners = [];
    $totalPatients = 0;
    $dogCount = 0;
    $catCount = 0;
    $otherCount = 0;
}

// Generate CSRF token
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patientenverwaltung - Tierphysio Manager</title>
    
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
                                <li class="breadcrumb-item active" aria-current="page">Patienten</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0">Patientenverwaltung</h2>
                    </div>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                            <i class="bx bx-plus me-2"></i>Neuer Patient
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <i class="bx bx-group kpi-icon"></i>
                            <div class="kpi-label">Gesamt Patienten</div>
                            <div class="kpi-value"><?php echo $totalPatients; ?></div>
                            <small class="text-muted">Alle registrierten Tiere</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card info">
                            <i class="bx bxs-dog kpi-icon"></i>
                            <div class="kpi-label">Hunde</div>
                            <div class="kpi-value"><?php echo $dogCount; ?></div>
                            <small class="text-info">
                                <?php echo $totalPatients > 0 ? round(($dogCount / $totalPatients) * 100) : 0; ?>% der Patienten
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card warning">
                            <i class="bx bxs-cat kpi-icon"></i>
                            <div class="kpi-label">Katzen</div>
                            <div class="kpi-value"><?php echo $catCount; ?></div>
                            <small class="text-warning">
                                <?php echo $totalPatients > 0 ? round(($catCount / $totalPatients) * 100) : 0; ?>% der Patienten
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card success">
                            <i class="bx bx-dots-horizontal-rounded kpi-icon"></i>
                            <div class="kpi-label">Andere</div>
                            <div class="kpi-value"><?php echo $otherCount; ?></div>
                            <small class="text-success">
                                <?php echo $totalPatients > 0 ? round(($otherCount / $totalPatients) * 100) : 0; ?>% der Patienten
                            </small>
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
                            <h5 class="card-title mb-0">Patienten Übersicht</h5>
                            <div class="d-flex gap-2">
                                <form class="d-flex" method="GET">
                                    <input type="search" name="search" class="form-control form-control-sm me-2" 
                                           placeholder="Suchen..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (count($patients) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Tierart</th>
                                            <th>Rasse</th>
                                            <th>Alter</th>
                                            <th>Besitzer</th>
                                            <th>Termine</th>
                                            <th>Rechnungen</th>
                                            <th width="150">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <span class="avatar-title bg-light text-primary rounded-circle">
                                                            <?php if ($patient['species'] === 'Hund'): ?>
                                                                🐕
                                                            <?php elseif ($patient['species'] === 'Katze'): ?>
                                                                🐈
                                                            <?php elseif ($patient['species'] === 'Pferd'): ?>
                                                                🐎
                                                            <?php elseif ($patient['species'] === 'Kaninchen'): ?>
                                                                🐰
                                                            <?php elseif ($patient['species'] === 'Vogel'): ?>
                                                                🦜
                                                            <?php else: ?>
                                                                🐾
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($patient['name']); ?></strong>
                                                        <?php if ($patient['weight']): ?>
                                                            <div class="text-muted small"><?php echo $patient['weight']; ?> kg</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($patient['species']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['breed'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($patient['age']): ?>
                                                    <?php echo $patient['age']; ?> <?php echo $patient['age'] == 1 ? 'Jahr' : 'Jahre'; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?php echo htmlspecialchars($patient['owner_name']); ?></div>
                                                    <?php if ($patient['owner_phone']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($patient['owner_phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $patient['appointment_count']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $patient['invoice_count']; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="patient.php?id=<?php echo $patient['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Details">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-warning" title="Bearbeiten"
                                                            onclick="editPatient(<?php echo $patient['id']; ?>)">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" title="Löschen"
                                                            onclick="deletePatient(<?php echo $patient['id']; ?>)">
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
                                <i class="bx bx-group display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">
                                    <?php echo $searchQuery ? 'Keine Suchergebnisse gefunden' : 'Keine Patienten vorhanden'; ?>
                                </h5>
                                <p class="text-muted">
                                    <?php echo $searchQuery ? 'Versuchen Sie eine andere Suche.' : 'Legen Sie den ersten Patienten an, um zu beginnen.'; ?>
                                </p>
                                <?php if (!$searchQuery): ?>
                                    <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                                        <i class="bx bx-plus me-2"></i>Ersten Patient anlegen
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Include Footer -->
        <?php include __DIR__ . '/includes/footer.php'; ?>
        
    </div>
    
    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPatientModalLabel">
                        <i class="bx bx-user-plus me-2"></i>Neuen Patient anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name des Tieres *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="owner_id" class="form-label">Besitzer *</label>
                                <select class="form-select" id="owner_id" name="owner_id" required>
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?php echo $owner['id']; ?>">
                                            <?php echo htmlspecialchars($owner['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="species" class="form-label">Tierart *</label>
                                <select class="form-select" id="species" name="species" required>
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($species as $s): ?>
                                        <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="breed" class="form-label">Rasse</label>
                                <input type="text" class="form-control" id="breed" name="breed" 
                                       placeholder="z.B. Labrador, Perser, etc.">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Alter (Jahre)</label>
                                <input type="number" class="form-control" id="age" name="age" min="0" max="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="weight" class="form-label">Gewicht (kg)</label>
                                <input type="number" class="form-control" id="weight" name="weight" 
                                       step="0.1" min="0" max="200">
                            </div>
                        </div>
                        
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                <strong>Hinweis:</strong> Sie können weitere Details nach dem Anlegen des Patienten hinzufügen.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Patient speichern
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
    function editPatient(id) {
        window.location.href = 'edit_patient.php?id=' + id;
    }
    
    function deletePatient(id) {
        if (confirm('Möchten Sie diesen Patienten wirklich löschen? Alle zugehörigen Termine und Rechnungen werden ebenfalls gelöscht.')) {
            // In real implementation, make AJAX call or redirect to delete script
            alert('Löschfunktion noch nicht implementiert');
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