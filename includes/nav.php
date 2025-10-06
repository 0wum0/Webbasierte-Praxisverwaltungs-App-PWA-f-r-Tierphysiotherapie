<?php
/**
 * Unified Navigation/Sidebar Component
 * Modern sidebar with violet gradient active states
 * @package TierphysioManager
 * @version 3.0.0
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

// Navigation items configuration - ALL LINKS RESTORED
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
        'id' => 'nav-appointments',
        'href' => 'appointments.php',
        'icon' => 'bx bx-calendar',
        'title' => 'Termine',
        'page' => 'appointments.php'
    ],
    [
        'id' => 'nav-notes',
        'href' => 'notes.php',
        'icon' => 'bx bx-note',
        'title' => 'Notizen',
        'page' => 'notes.php'
    ],
    [
        'id' => 'nav-invoices',
        'href' => 'invoices.php',
        'icon' => 'bx bx-receipt',
        'title' => 'Rechnungen',
        'page' => 'invoices.php'
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

// Add admin section if user is admin
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
    <!-- Sidebar Header with Violet Gradient -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span style="font-size: 1.75rem;">🐾</span>
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

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between p-3 border-top">
            <small class="text-muted">Version <?php echo defined('APP_VERSION') ? APP_VERSION : '3.0.0'; ?></small>
            <a href="#" data-bs-toggle="modal" data-bs-target="#changelogModal" class="text-decoration-none">
                <i class="bi bi-journal-text text-muted"></i>
            </a>
        </div>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay"></div>

<style>
/* Sidebar Styles with Violet Theme */
.sidebar-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 260px;
    background: #ffffff;
    border-right: 1px solid rgba(124, 77, 255, 0.1);
    z-index: 1040;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

[data-theme="dark"] .sidebar-wrapper {
    background: #1a1d23;
    border-right-color: rgba(156, 39, 176, 0.2);
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

/* Mobile: Sidebar hidden by default, show when active */
.sidebar-wrapper.active {
    transform: translateX(0);
}

/* Sidebar Header with Violet Gradient */
.sidebar-header {
    padding: 1.25rem 1rem;
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    box-shadow: 0 2px 10px rgba(124, 77, 255, 0.2);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.logo-text {
    background: linear-gradient(135deg, rgba(255,255,255,1), rgba(255,255,255,0.9));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

.toggle-icon {
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 8px;
    transition: all 0.2s;
    color: white;
}

.toggle-icon:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Sidebar Navigation */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

/* Custom scrollbar */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(124, 77, 255, 0.2);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(124, 77, 255, 0.3);
}

/* Menu Styling */
.metismenu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.metismenu li {
    margin-bottom: 0.25rem;
    padding: 0 0.75rem;
}

.metismenu li a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-radius: 12px;
    border-left: 3px solid transparent;
}

[data-theme="dark"] .metismenu li a {
    color: #9ca3af;
}

.metismenu li a:hover {
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.05), rgba(156, 39, 176, 0.05));
    color: #7C4DFF;
    border-left-color: #7C4DFF;
    transform: translateX(4px);
}

/* Active state with gradient */
.metismenu li.active > a,
.metismenu li a.active {
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    color: white;
    border-left-color: white;
    box-shadow: 0 4px 15px rgba(124, 77, 255, 0.3);
}

.metismenu li.active > a:hover,
.metismenu li a.active:hover {
    transform: translateX(2px);
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
    background: rgba(124, 77, 255, 0.03);
    flex-shrink: 0;
}

[data-theme="dark"] .sidebar-footer {
    background: rgba(156, 39, 176, 0.05);
}

.sidebar-footer a {
    color: #7C4DFF;
    transition: color 0.2s;
}

.sidebar-footer a:hover {
    color: #9C27B0;
}

/* Sidebar Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.6), rgba(156, 39, 176, 0.6));
    z-index: 1039;
    backdrop-filter: blur(5px);
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

/* Hover effect with glow */
.metismenu li a::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.1), rgba(156, 39, 176, 0.1));
    border-radius: 12px;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
}

.metismenu li a:hover::before {
    opacity: 1;
}

/* Shine effect on active items */
@keyframes menuShine {
    0% {
        background-position: -200% center;
    }
    100% {
        background-position: 200% center;
    }
}

.metismenu li.active > a::after,
.metismenu li a.active::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    background-size: 200% 100%;
    animation: menuShine 3s linear infinite;
    pointer-events: none;
    border-radius: 12px;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .toggle-icon {
        display: none !important;
    }
}
</style>

<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar-wrapper');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleBtn = document.querySelector('.mobile-toggle-menu');
    const collapseBtn = document.querySelector('.toggle-icon');
    const wrapper = document.querySelector('.wrapper');
    
    // Mobile toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isDesktop = window.innerWidth >= 992;
            
            if (isDesktop) {
                // Desktop: Toggle wrapper class
                if (wrapper) {
                    wrapper.classList.toggle('toggled');
                }
            } else {
                // Mobile: Toggle sidebar and overlay
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            }
        });
    }
    
    // Desktop collapse button
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            if (wrapper) {
                wrapper.classList.toggle('toggled');
            }
        });
    }
    
    // Close on overlay click (mobile)
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.remove('active');
            }
            overlay.classList.remove('active');
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const isDesktop = window.innerWidth >= 992;
        
        if (isDesktop && sidebar) {
            // Remove mobile classes on desktop
            sidebar.classList.remove('active');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    });
});
</script>