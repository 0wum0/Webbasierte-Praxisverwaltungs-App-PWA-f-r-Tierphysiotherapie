<?php
declare(strict_types=1);

/**
 * Dashboard - Hauptseite nach Login
 */

// Bootstrap laden (enthält automatisch Login-Check)
require_once __DIR__ . '/includes/bootstrap.php';

// Benutzerinformationen abrufen
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tierphysio Praxis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-heart-pulse me-2"></i>
                Tierphysio Praxis
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/dashboard.php">
                            <i class="bi bi-house me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/patients.php">
                            <i class="bi bi-people me-1"></i> Patienten
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/appointments.php">
                            <i class="bi bi-calendar me-1"></i> Termine
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="bi bi-gear me-1"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/profile.php">
                                <i class="bi bi-person me-2"></i>Profil
                            </a></li>
                            <li><a class="dropdown-item" href="/settings.php">
                                <i class="bi bi-gear me-2"></i>Einstellungen
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Abmelden
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <h2>Willkommen zurück, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>!</h2>
            <p class="text-muted mb-0">
                Hier ist Ihre Übersicht für heute, <?= date('d. F Y') ?>
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon blue">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">12</h5>
                            <p class="text-muted mb-0">Termine heute</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon green">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">248</h5>
                            <p class="text-muted mb-0">Aktive Patienten</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon orange">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">3</h5>
                            <p class="text-muted mb-0">Wartende</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon red">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">2</h5>
                            <p class="text-muted mb-0">Offene Aufgaben</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Heutige Termine
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Max Mustermann - Hund "Bello"</h6>
                                    <small class="text-muted">Physiotherapie - Hüfte</small>
                                </div>
                                <span class="badge bg-primary">09:00 Uhr</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Maria Schmidt - Katze "Luna"</h6>
                                    <small class="text-muted">Nachkontrolle</small>
                                </div>
                                <span class="badge bg-primary">10:30 Uhr</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Thomas Weber - Pferd "Thunder"</h6>
                                    <small class="text-muted">Massage & Mobilisation</small>
                                </div>
                                <span class="badge bg-primary">14:00 Uhr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bell me-2"></i>
                            Benachrichtigungen
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-2">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                Neue Terminanfrage von Julia Meyer
                            </small>
                        </div>
                        <div class="alert alert-warning mb-2">
                            <small>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Medikamentenvorrat niedrig
                            </small>
                        </div>
                        <div class="alert alert-success mb-0">
                            <small>
                                <i class="bi bi-check-circle me-1"></i>
                                Backup erfolgreich erstellt
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>