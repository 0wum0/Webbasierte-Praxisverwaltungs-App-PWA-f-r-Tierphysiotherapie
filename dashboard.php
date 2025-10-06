<?php
declare(strict_types=1);

/**
 * KPI Dashboard 2.0 - Modern Dashboard with Live Statistics
 */

// Bootstrap laden (enthält automatisch Login-Check)
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/version.php';

// Benutzerinformationen abrufen
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$isAdmin = isset($_SESSION['admin_id']);

// Check for available updates
$updateAvailable = $_SESSION['update_available'] ?? false;
$updateFromVersion = $_SESSION['update_from_version'] ?? null;
$updateToVersion = $_SESSION['update_to_version'] ?? null;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - Tierphysio Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #0cbc87 0%, #3dd5a7 100%);
            --warning-gradient: linear-gradient(135deg, #ffb612 0%, #ffcb52 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ff8787 100%);
            --info-gradient: linear-gradient(135deg, #4da6ff 0%, #7fbfff 100%);
        }

        body {
            background: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Dark mode support */
        body.dark-mode {
            background: #1a1d23;
            color: #e4e6eb;
        }

        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white !important;
        }

        /* KPI Cards */
        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .kpi-card.success::before { background: var(--success-gradient); }
        .kpi-card.warning::before { background: var(--warning-gradient); }
        .kpi-card.danger::before { background: var(--danger-gradient); }
        .kpi-card.info::before { background: var(--info-gradient); }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .kpi-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .kpi-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
        }

        .kpi-trend {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .kpi-trend.up {
            background: #d1fae5;
            color: #065f46;
        }

        .kpi-trend.down {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Charts */
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        /* Birthday Widget */
        .birthday-widget {
            background: var(--warning-gradient);
            border-radius: 16px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }

        .birthday-item {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            backdrop-filter: blur(10px);
        }

        /* Update Notification */
        .update-notification {
            background: var(--info-gradient);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Quick Actions */
        .quick-action {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #4b5563;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .quick-action:hover {
            border-color: #8b5cf6;
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15);
            color: #8b5cf6;
        }

        .quick-action i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Dark mode styles */
        body.dark-mode .kpi-card,
        body.dark-mode .chart-card,
        body.dark-mode .quick-action {
            background: #2d3136;
            color: #e4e6eb;
        }

        body.dark-mode .kpi-label {
            color: #9ca3af;
        }

        body.dark-mode .chart-title {
            color: #e4e6eb;
        }

        /* Theme toggle */
        .theme-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark-mode .theme-toggle {
            background: #374151;
            color: #fbbf24;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-activity me-2"></i>
                Tierphysio Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($userName) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Abmelden</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <?php if ($updateAvailable): ?>
        <!-- Update Notification -->
        <div class="update-notification">
            <div>
                <i class="bi bi-download me-2"></i>
                <strong>Update verfügbar!</strong> Version <?= htmlspecialchars($updateToVersion) ?> ist bereit zur Installation.
            </div>
            <a href="/install/install.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Jetzt aktualisieren
            </a>
        </div>
        <?php endif; ?>

        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>Willkommen zurück, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>!</h2>
                <p class="text-muted">Hier ist Ihre Übersicht für heute, <?= date('d. F Y', strtotime('now')) ?></p>
            </div>
        </div>

        <!-- KPI Cards Row 1 -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="kpi-card">
                    <i class="bi bi-calendar-check kpi-icon"></i>
                    <div class="kpi-label">Termine heute</div>
                    <div class="kpi-value" id="kpi-appointments">-</div>
                    <div class="kpi-trend up" id="kpi-appointments-trend">
                        <i class="bi bi-arrow-up"></i> <span>0 abgeschlossen</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi-card success">
                    <i class="bi bi-people kpi-icon"></i>
                    <div class="kpi-label">Aktive Patienten</div>
                    <div class="kpi-value" id="kpi-patients">-</div>
                    <small class="text-muted">Letzte 3 Monate</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi-card info">
                    <i class="bi bi-person-plus kpi-icon"></i>
                    <div class="kpi-label">Neue Patienten</div>
                    <div class="kpi-value" id="kpi-new-patients">-</div>
                    <small class="text-muted">Diese Woche</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="kpi-card warning">
                    <i class="bi bi-currency-euro kpi-icon"></i>
                    <div class="kpi-label">Einnahmen heute</div>
                    <div class="kpi-value" id="kpi-revenue-today">-</div>
                    <small class="text-muted" id="kpi-revenue-month">Monat: -</small>
                </div>
            </div>
        </div>

        <!-- Charts and Widgets Row -->
        <div class="row">
            <!-- Revenue Chart -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="bi bi-graph-up me-2"></i>Umsatzentwicklung (12 Monate)
                    </h3>
                    <canvas id="revenueChart" height="100"></canvas>
                </div>

                <!-- Invoice Status -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="chart-card">
                            <h3 class="chart-title">
                                <i class="bi bi-pie-chart me-2"></i>Rechnungsstatus
                            </h3>
                            <canvas id="invoiceChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card">
                            <h3 class="chart-title">
                                <i class="bi bi-bar-chart me-2"></i>Top Services
                            </h3>
                            <canvas id="servicesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Birthday Widget -->
                <div class="birthday-widget" id="birthdayWidget" style="display: none;">
                    <h5 class="mb-3">
                        <i class="bi bi-cake2 me-2"></i>Anstehende Geburtstage
                    </h5>
                    <div id="birthdayList"></div>
                </div>

                <!-- Revenue Summary -->
                <div class="kpi-card mb-3">
                    <h5 class="mb-3">Einnahmen-Übersicht</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Heute:</span>
                        <strong id="revenue-today">€ 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Monat:</span>
                        <strong id="revenue-month">€ 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Jahr:</span>
                        <strong id="revenue-year">€ 0</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Gesamt:</span>
                        <strong id="revenue-total">€ 0</strong>
                    </div>
                </div>

                <!-- Quick Actions -->
                <h5 class="mb-3">Schnellzugriff</h5>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="appointments.php" class="quick-action">
                            <i class="bi bi-calendar-plus"></i>
                            <small>Neuer Termin</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="patients.php" class="quick-action">
                            <i class="bi bi-person-plus"></i>
                            <small>Neuer Patient</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="invoices.php" class="quick-action">
                            <i class="bi bi-receipt"></i>
                            <small>Rechnung</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="owners.php" class="quick-action">
                            <i class="bi bi-people"></i>
                            <small>Besitzer</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer with Credits -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <i class="bi bi-moon-fill"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const currentTheme = localStorage.getItem('theme') || 'light';

        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            themeToggle.innerHTML = isDark ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
        });

        // Chart configurations
        let revenueChart, invoiceChart, servicesChart;

        // Initialize charts
        function initCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Umsatz (€)',
                        data: [],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€ ' + value.toLocaleString('de-DE');
                                }
                            }
                        }
                    }
                }
            });

            // Invoice Status Chart
            const invoiceCtx = document.getElementById('invoiceChart').getContext('2d');
            invoiceChart = new Chart(invoiceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Bezahlt', 'Offen', 'Überfällig'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Services Chart
            const servicesCtx = document.getElementById('servicesChart').getContext('2d');
            servicesChart = new Chart(servicesCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Anzahl',
                        data: [],
                        backgroundColor: '#8b5cf6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Fetch and update dashboard data
        async function updateDashboard() {
            try {
                const response = await fetch('/api/dashboard_metrics.php');
                const result = await response.json();

                if (result.success) {
                    const data = result.data;

                    // Update KPI cards
                    document.getElementById('kpi-appointments').textContent = data.appointments_today.total;
                    document.getElementById('kpi-appointments-trend').innerHTML = 
                        `<i class="bi bi-check-circle"></i> ${data.appointments_today.completed} abgeschlossen`;
                    
                    document.getElementById('kpi-patients').textContent = data.active_patients;
                    document.getElementById('kpi-new-patients').textContent = data.new_patients_week;
                    document.getElementById('kpi-revenue-today').textContent = '€ ' + data.revenue.today.toLocaleString('de-DE');
                    document.getElementById('kpi-revenue-month').textContent = 
                        'Monat: € ' + data.revenue.month.toLocaleString('de-DE');

                    // Update revenue summary
                    document.getElementById('revenue-today').textContent = '€ ' + data.revenue.today.toLocaleString('de-DE');
                    document.getElementById('revenue-month').textContent = '€ ' + data.revenue.month.toLocaleString('de-DE');
                    document.getElementById('revenue-year').textContent = '€ ' + data.revenue.year.toLocaleString('de-DE');
                    document.getElementById('revenue-total').textContent = '€ ' + data.revenue.total.toLocaleString('de-DE');

                    // Update charts
                    if (data.monthly_chart) {
                        revenueChart.data.labels = data.monthly_chart.labels;
                        revenueChart.data.datasets[0].data = data.monthly_chart.revenue;
                        revenueChart.update();
                    }

                    if (data.invoices) {
                        invoiceChart.data.datasets[0].data = [
                            data.invoices.paid,
                            data.invoices.open,
                            data.invoices.overdue
                        ];
                        invoiceChart.update();
                    }

                    if (data.top_services) {
                        servicesChart.data.labels = data.top_services.map(s => s.service);
                        servicesChart.data.datasets[0].data = data.top_services.map(s => s.count);
                        servicesChart.update();
                    }

                    // Update birthdays
                    if (data.birthdays && data.birthdays.length > 0) {
                        const birthdayWidget = document.getElementById('birthdayWidget');
                        const birthdayList = document.getElementById('birthdayList');
                        
                        birthdayWidget.style.display = 'block';
                        birthdayList.innerHTML = data.birthdays.map(b => `
                            <div class="birthday-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${b.patient_name}</strong>
                                        <small class="d-block">${b.owner_name}</small>
                                    </div>
                                    <div class="text-end">
                                        <strong>${b.days_until === 0 ? 'Heute!' : `In ${b.days_until} Tagen`}</strong>
                                        <small class="d-block">${b.age} Jahre</small>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initCharts();
            updateDashboard();
            
            // Refresh every 30 seconds
            setInterval(updateDashboard, 30000);
        });
    </script>
</body>
</html>