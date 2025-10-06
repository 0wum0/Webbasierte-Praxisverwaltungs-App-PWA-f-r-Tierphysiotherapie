<?php
declare(strict_types=1);

/**
 * Appointments Management Page
 * Unified design matching dashboard
 */

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

require_once __DIR__ . '/includes/csrf.php';

// Initialize variables
$appointments = [];
$patients = [];
$errorMessage = null;
$successMessage = null;

// Handle POST request for adding new appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        if ($patient_id === 0 || empty($appointment_date) || empty($appointment_time)) {
            $errorMessage = "Patient, Datum und Uhrzeit sind erforderlich.";
        } else {
            try {
                $datetime = $appointment_date . ' ' . $appointment_time . ':00';
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (patient_id, appointment_date, notes, created_at)
                    VALUES (:patient_id, :appointment_date, :notes, NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':appointment_date' => $datetime,
                    ':notes' => $notes ?: null
                ]);
                
                $successMessage = "✅ Termin wurde erfolgreich angelegt!";
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating appointment', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Anlegen des Termins.";
            }
        }
    }
}

// Load appointments list
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.appointment_date,
               p.name AS patient_name,
               CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name,
               a.notes
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN owners o ON o.id = p.owner_id
        ORDER BY a.appointment_date DESC
        LIMIT 200
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load patients for dropdown
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading appointments', ['error' => $e->getMessage()]);
    }
    $errorMessage = "Datenbankfehler beim Laden der Termine.";
    $appointments = [];
    $patients = [];
}

// Generate CSRF token
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminverwaltung - Tierphysio Manager</title>
    
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
                                <li class="breadcrumb-item active" aria-current="page">Termine</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0">Terminverwaltung</h2>
                    </div>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                            <i class="bx bx-plus me-2"></i>Neuer Termin
                        </button>
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
                            <h5 class="card-title mb-0">Termine Übersicht</h5>
                            <span class="badge bg-primary"><?php echo count($appointments); ?> Termine</span>
                        </div>
                        
                        <?php if (count($appointments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Datum/Zeit</th>
                                            <th>Patient</th>
                                            <th>Besitzer</th>
                                            <th>Notizen</th>
                                            <th width="150">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <span class="avatar-title bg-light text-primary rounded-circle">
                                                            <i class="bx bx-calendar"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo date('d.m.Y', strtotime($appointment['appointment_date'])); ?></strong>
                                                        <div class="text-muted small">
                                                            <?php echo date('H:i', strtotime($appointment['appointment_date'])); ?> Uhr
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['owner_name']); ?></td>
                                            <td>
                                                <?php if ($appointment['notes']): ?>
                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                          title="<?php echo htmlspecialchars($appointment['notes']); ?>">
                                                        <?php echo htmlspecialchars($appointment['notes']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" title="Details">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning" title="Bearbeiten">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" title="Löschen"
                                                            onclick="deleteAppointment(<?php echo $appointment['id']; ?>)">
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
                                <i class="bx bx-calendar-x display-1 text-muted mb-3"></i>
                                <h5 class="text-muted">Keine Termine vorhanden</h5>
                                <p class="text-muted">Legen Sie den ersten Termin an, um zu beginnen.</p>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                                    <i class="bx bx-plus me-2"></i>Ersten Termin anlegen
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
    
    <!-- Add Appointment Modal -->
    <div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true" style="z-index: 2000 !important;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAppointmentModalLabel">
                        <i class="bx bx-calendar-plus me-2"></i>Neuen Termin anlegen
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
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appointment_date" class="form-label">Datum *</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appointment_time" class="form-label">Uhrzeit *</label>
                                <input type="time" class="form-control" id="appointment_time" name="appointment_time" 
                                       value="09:00" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notizen</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Optionale Notizen zum Termin..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Termin speichern
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
    function deleteAppointment(id) {
        if (confirm('Möchten Sie diesen Termin wirklich löschen?')) {
            window.location.href = 'delete_appointment.php?id=' + id;
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