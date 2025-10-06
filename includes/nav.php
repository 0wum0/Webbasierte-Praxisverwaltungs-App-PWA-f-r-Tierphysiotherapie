<?php
/**
 * Unified Navigation/Sidebar Component
 * Modern sidebar navigation matching dashboard design
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Navigation items configuration
$navItems = [
    [
        'id' => 'nav-dashboard',
        'href' => 'dashboard.php',
        'icon' => 'bx bx-home-alt',
        'title' => 'Dashboard',
        'page' => 'dashboard.php'
    ],
    [
        'id' => 'nav-patients',
        'href' => 'patients.php',
        'icon' => 'bx bx-group',
        'title' => 'Patienten',
        'page' => 'patients.php'
    ],
    [
        'id' => 'nav-owners',
        'href' => 'owners.php',
        'icon' => 'bx bx-user',
        'title' => 'Besitzer',
        'page' => 'owners.php'
    ],
    [
        'id' => 'nav-owner-patient',
        'href' => 'owner_patient.php',
        'icon' => 'bx bx-user-plus',
        'title' => 'Besitzer & Patient',
        'page' => 'owner_patient.php'
    ],
    [
        'id' => 'nav-appointments',
        'href' => 'appointments.php',
        'icon' => 'bx bx-calendar',
        'title' => 'Termine',
        'page' => 'appointments.php'
    ],
    [
        'id' => 'nav-invoices',
        'href' => 'invoices.php',
        'icon' => 'bx bx-receipt',
        'title' => 'Rechnungen',
        'page' => 'invoices.php'
    ],
    [
        'id' => 'nav-notes',
        'href' => 'notes.php',
        'icon' => 'bx bx-note',
        'title' => 'Notizen',
        'page' => 'notes.php'
    ],
    [
        'id' => 'nav-bookkeeping',
        'href' => 'bookkeeping.php',
        'icon' => 'bx bx-calculator',
        'title' => 'Buchhaltung',
        'page' => 'bookkeeping.php'
    ],
    [
        'id' => 'nav-settings',
        'href' => 'settings.php',
        'icon' => 'bx bx-cog',
        'title' => 'Einstellungen',
        'page' => 'settings.php'
    ]
];

// Check if admin section should be shown
$isAdmin = isset($_SESSION['admin_id']);
if ($isAdmin) {
    $navItems[] = [
        'id' => 'nav-admin',
        'href' => 'admin/dashboard.php',
        'icon' => 'bx bx-shield',
        'title' => 'Admin Bereich',
        'page' => 'admin/dashboard.php'
    ];
}
?>

<!-- Sidebar Wrapper -->
<div class="sidebar-wrapper" data-simplebar="true">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span style="font-size: 1.5rem;">🐾</span>
            <span class="logo-text">Tierphysio Manager</span>
        </div>
        <div class="toggle-icon ms-auto d-lg-block d-none">
            <i class='bx bx-arrow-back'></i>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar-nav">
        <ul class="metismenu" id="menu">
            <?php foreach ($navItems as $item): ?>
                <?php 
                $isActive = ($currentPage === $item['page']) ? 'active' : '';
                ?>
                <li id="<?php echo $item['id']; ?>" class="<?php echo $isActive; ?>">
                    <a href="<?php echo $item['href']; ?>" class="<?php echo $isActive; ?>">
                        <div class="parent-icon">
                            <i class='<?php echo $item['icon']; ?>'></i>
                        </div>
                        <div class="menu-title"><?php echo $item['title']; ?></div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Sidebar Footer (Optional) -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center p-3 border-top">
            <small class="text-muted">Version 3.0.0</small>
        </div>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay"></div>

<style>
/* Custom Sidebar Styles */
.sidebar-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 260px;
    background: var(--bg-secondary);
    border-right: 1px solid var(--border-color);
    z-index: 1040;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Desktop: Sidebar visible by default */
@media (min-width: 992px) {
    .sidebar-wrapper {
        transform: translateX(0);
    }
    
    /* When toggled on desktop */
    .wrapper.toggled .sidebar-wrapper {
        transform: translateX(-100%);
    }
}

/* Mobile: Sidebar hidden by default */
.sidebar-wrapper.active {
    transform: translateX(0);
}

/* Sidebar Header */
.sidebar-header {
    padding: 1.25rem 1rem;
    background: var(--primary-gradient);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.toggle-icon {
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--radius-sm);
    transition: background 0.2s;
}

.toggle-icon:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Sidebar Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: var(--text-muted);
    border-radius: 3px;
}

.metismenu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.metismenu li {
    margin-bottom: 0.25rem;
}

.metismenu li a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
    border-left: 3px solid transparent;
}

.metismenu li a:hover {
    background: var(--bg-tertiary);
    color: var(--primary-color);
    border-left-color: var(--primary-color);
}

.metismenu li.active > a,
.metismenu li a.active {
    background: var(--primary-gradient);
    color: white;
    border-left-color: white;
}

.metismenu .parent-icon {
    font-size: 1.25rem;
    width: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.metismenu .menu-title {
    font-size: 0.9375rem;
    font-weight: 500;
}

/* Sidebar Footer */
.sidebar-footer {
    margin-top: auto;
    background: var(--bg-tertiary);
    flex-shrink: 0;
}

/* Sidebar Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1039;
    backdrop-filter: blur(4px);
}

.sidebar-overlay.active {
    display: block;
}

/* Animation for menu items */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar-wrapper.active .metismenu li {
    animation: slideInLeft 0.3s ease forwards;
    opacity: 0;
}

.sidebar-wrapper.active .metismenu li:nth-child(1) { animation-delay: 0.05s; }
.sidebar-wrapper.active .metismenu li:nth-child(2) { animation-delay: 0.10s; }
.sidebar-wrapper.active .metismenu li:nth-child(3) { animation-delay: 0.15s; }
.sidebar-wrapper.active .metismenu li:nth-child(4) { animation-delay: 0.20s; }
.sidebar-wrapper.active .metismenu li:nth-child(5) { animation-delay: 0.25s; }
.sidebar-wrapper.active .metismenu li:nth-child(6) { animation-delay: 0.30s; }
.sidebar-wrapper.active .metismenu li:nth-child(7) { animation-delay: 0.35s; }
.sidebar-wrapper.active .metismenu li:nth-child(8) { animation-delay: 0.40s; }
.sidebar-wrapper.active .metismenu li:nth-child(9) { animation-delay: 0.45s; }
.sidebar-wrapper.active .metismenu li:nth-child(10) { animation-delay: 0.50s; }

/* Hover effects with glow */
.metismenu li a {
    position: relative;
    overflow: hidden;
}

.metismenu li a::after {
    content: '';
    position: absolute;
    top: 50%;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s;
    transform: translateY(-50%);
}

.metismenu li a:hover::after {
    left: 100%;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .toggle-icon {
        display: none !important;
    }
}

/* Dark mode specific adjustments */
[data-theme="dark"] .sidebar-wrapper {
    background: var(--bg-secondary);
    border-right-color: var(--border-color);
}

[data-theme="dark"] .metismenu li a:hover {
    background: rgba(124, 77, 255, 0.1);
}

[data-theme="dark"] .sidebar-footer {
    background: var(--bg-tertiary);
}
</style>