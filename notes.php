<?php
declare(strict_types=1);

/**
 * Notes Management Page
 * Unified design matching dashboard
 */

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
if (!$pdo) {
    throw new RuntimeException('DB connection unavailable');
}

require_once __DIR__ . '/includes/csrf.php';

// Initialize variables
$notes = [];
$patients = [];
$errorMessage = null;
$successMessage = null;

// Handle POST request for adding new note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && csrf_validate($_POST['csrf_token'] ?? '')) {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $note_text = trim($_POST['note_text'] ?? '');
        $category = $_POST['category'] ?? 'general';
        
        if ($patient_id === 0 || empty($note_text)) {
            $errorMessage = "Patient und Notiztext sind erforderlich.";
        } else {
            try {
                // Check if table exists and has expected columns
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'notes'")->fetch();
                if (!$tableCheck) {
                    // Create notes table if it doesn't exist
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS notes (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            patient_id INT NOT NULL,
                            note_text TEXT NOT NULL,
                            category VARCHAR(50) DEFAULT 'general',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
                            INDEX idx_patient (patient_id),
                            INDEX idx_category (category),
                            INDEX idx_created (created_at)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO notes (patient_id, note_text, category, created_at)
                    VALUES (:patient_id, :note_text, :category, NOW())
                ");
                $stmt->execute([
                    ':patient_id' => $patient_id,
                    ':note_text' => $note_text,
                    ':category' => $category
                ]);
                
                $successMessage = "✅ Notiz wurde erfolgreich gespeichert!";
                
            } catch (PDOException $e) {
                if (function_exists('logError')) {
                    logError('Database error creating note', ['error' => $e->getMessage()]);
                }
                $errorMessage = "Datenbankfehler beim Speichern der Notiz.";
            }
        }
    }
}

// Search functionality
$searchQuery = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Load notes list
try {
    // Check if notes table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'notes'")->fetch();
    
    if ($tableExists) {
        $query = "
            SELECT n.*, 
                   p.name AS patient_name,
                   p.species AS patient_species,
                   CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
            FROM notes n
            JOIN patients p ON p.id = n.patient_id
            JOIN owners o ON o.id = p.owner_id
        ";
        
        $conditions = [];
        if ($searchQuery) {
            $conditions[] = "(n.note_text LIKE :search OR p.name LIKE :search)";
        }
        if ($categoryFilter) {
            $conditions[] = "n.category = :category";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY n.created_at DESC LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        if ($searchQuery) {
            $stmt->bindValue(':search', '%' . $searchQuery . '%');
        }
        if ($categoryFilter) {
            $stmt->bindValue(':category', $categoryFilter);
        }
        $stmt->execute();
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Load patients for dropdown
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.species, CONCAT_WS(' ', o.firstname, o.lastname) AS owner_name
        FROM patients p
        JOIN owners o ON o.id = p.owner_id
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $totalNotes = count($notes);
    $todayNotes = count(array_filter($notes, fn($n) => date('Y-m-d', strtotime($n['created_at'] ?? '')) === date('Y-m-d')));
    $weekNotes = count(array_filter($notes, fn($n) => strtotime($n['created_at'] ?? '') > strtotime('-7 days')));
    $uniquePatients = count(array_unique(array_column($notes, 'patient_id')));
    
} catch (PDOException $e) {
    if (function_exists('logError')) {
        logError('Database error loading notes', ['error' => $e->getMessage()]);
    }
    $notes = [];
    $patients = [];
    $totalNotes = 0;
    $todayNotes = 0;
    $weekNotes = 0;
    $uniquePatients = 0;
}

// Generate CSRF token
$csrfToken = csrf_token();

// Note categories
$categories = [
    'general' => ['label' => 'Allgemein', 'icon' => 'bx-note', 'color' => 'secondary'],
    'treatment' => ['label' => 'Behandlung', 'icon' => 'bx-health', 'color' => 'primary'],
    'diagnosis' => ['label' => 'Diagnose', 'icon' => 'bx-search-alt', 'color' => 'info'],
    'medication' => ['label' => 'Medikation', 'icon' => 'bx-capsule', 'color' => 'warning'],
    'followup' => ['label' => 'Nachsorge', 'icon' => 'bx-calendar-check', 'color' => 'success'],
    'important' => ['label' => 'Wichtig', 'icon' => 'bx-error-circle', 'color' => 'danger']
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notizen - Tierphysio Manager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Unified Styles -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
    .note-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .note-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }
    
    .note-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 3px;
        height: 100%;
        background: var(--primary-gradient);
    }
    
    .note-card.category-treatment::before { background: var(--info-gradient); }
    .note-card.category-diagnosis::before { background: var(--success-gradient); }
    .note-card.category-medication::before { background: var(--warning-gradient); }
    .note-card.category-important::before { background: var(--danger-gradient); }
    
    .note-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.75rem;
    }
    
    .note-patient {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .note-text {
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 0.75rem;
    }
    
    .note-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    
    .category-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
    }
    </style>
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
                                <li class="breadcrumb-item active" aria-current="page">Notizen</li>
                            </ol>
                        </nav>
                        <h2 class="mb-0">Notizen & Behandlungsverläufe</h2>
                    </div>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                            <i class="bx bx-plus me-2"></i>Neue Notiz
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="kpi-card">
                            <i class="bx bx-note kpi-icon"></i>
                            <div class="kpi-label">Gesamt Notizen</div>
                            <div class="kpi-value"><?php echo $totalNotes; ?></div>
                            <small class="text-muted">Alle gespeicherten Notizen</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card info">
                            <i class="bx bx-calendar-event kpi-icon"></i>
                            <div class="kpi-label">Heute</div>
                            <div class="kpi-value"><?php echo $todayNotes; ?></div>
                            <small class="text-info">Neue Einträge heute</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card warning">
                            <i class="bx bx-trending-up kpi-icon"></i>
                            <div class="kpi-label">Diese Woche</div>
                            <div class="kpi-value"><?php echo $weekNotes; ?></div>
                            <small class="text-warning">Letzte 7 Tage</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="kpi-card success">
                            <i class="bx bx-group kpi-icon"></i>
                            <div class="kpi-label">Patienten</div>
                            <div class="kpi-value"><?php echo $uniquePatients; ?></div>
                            <small class="text-success">Mit Notizen</small>
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
                
                <!-- Filter Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Suche</label>
                                <input type="search" name="search" id="search" class="form-control" 
                                       placeholder="In Notizen suchen..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Kategorie</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="">Alle Kategorien</option>
                                    <?php foreach ($categories as $key => $cat): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $categoryFilter === $key ? 'selected' : ''; ?>>
                                            <?php echo $cat['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-search me-2"></i>Filtern
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Notes List -->
                <?php if (count($notes) > 0): ?>
                    <div class="row">
                        <?php foreach ($notes as $note): ?>
                            <?php $category = $categories[$note['category'] ?? 'general'] ?? $categories['general']; ?>
                            <div class="col-lg-6">
                                <div class="note-card category-<?php echo $note['category'] ?? 'general'; ?>">
                                    <div class="note-header">
                                        <div class="note-patient">
                                            <?php
                                            $emoji = '🐾';
                                            if (($note['patient_species'] ?? '') === 'Hund') $emoji = '🐕';
                                            elseif (($note['patient_species'] ?? '') === 'Katze') $emoji = '🐈';
                                            elseif (($note['patient_species'] ?? '') === 'Pferd') $emoji = '🐎';
                                            ?>
                                            <span><?php echo $emoji; ?></span>
                                            <span><?php echo htmlspecialchars($note['patient_name'] ?? ''); ?></span>
                                            <small class="text-muted">(<?php echo htmlspecialchars($note['owner_name'] ?? ''); ?>)</small>
                                        </div>
                                        <span class="category-badge bg-<?php echo $category['color']; ?>">
                                            <i class="bx <?php echo $category['icon']; ?>"></i>
                                            <?php echo $category['label']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="note-text">
                                        <?php echo nl2br(htmlspecialchars($note['note_text'] ?? '')); ?>
                                    </div>
                                    
                                    <div class="note-footer">
                                        <span>
                                            <i class="bx bx-time-five"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($note['created_at'] ?? 'now')); ?>
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Bearbeiten">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Löschen"
                                                    onclick="deleteNote(<?php echo $note['id'] ?? 0; ?>)">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-note display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">
                                <?php echo $searchQuery || $categoryFilter ? 'Keine Notizen gefunden' : 'Noch keine Notizen vorhanden'; ?>
                            </h5>
                            <p class="text-muted">
                                <?php echo $searchQuery || $categoryFilter ? 'Versuchen Sie andere Suchkriterien.' : 'Erstellen Sie die erste Notiz, um zu beginnen.'; ?>
                            </p>
                            <?php if (!$searchQuery && !$categoryFilter): ?>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                    <i class="bx bx-plus me-2"></i>Erste Notiz erstellen
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Include Footer -->
        <?php include __DIR__ . '/includes/footer.php'; ?>
        
    </div>
    
    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">
                        <i class="bx bx-note me-2"></i>Neue Notiz erstellen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="patient_id" class="form-label">Patient *</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['name']); ?> 
                                            (<?php echo htmlspecialchars($patient['species']); ?>) - 
                                            <?php echo htmlspecialchars($patient['owner_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Kategorie</label>
                                <select class="form-select" id="category" name="category">
                                    <?php foreach ($categories as $key => $cat): ?>
                                        <option value="<?php echo $key; ?>">
                                            <?php echo $cat['label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="note_text" class="form-label">Notiz *</label>
                            <textarea class="form-control" id="note_text" name="note_text" rows="6" 
                                      placeholder="Behandlungsverlauf, Beobachtungen, wichtige Informationen..." 
                                      required></textarea>
                        </div>
                        
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="bi bi-lightbulb me-2"></i>
                            <div>
                                <strong>Tipp:</strong> Nutzen Sie Kategorien, um Ihre Notizen besser zu organisieren.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Notiz speichern
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
    function deleteNote(id) {
        if (confirm('Möchten Sie diese Notiz wirklich löschen?')) {
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