<?php
/**
 * Unified Header Component - Violet Gradient Design
 * Single header implementation without duplication
 * @package TierphysioManager
 * @version 3.0.0
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$userRole = isset($_SESSION['admin_id']) ? 'Administrator' : 'Benutzer';
$userAvatar = $_SESSION['user_avatar'] ?? 'assets/images/avatars/avatar-2.png';

// Current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Unified Violet Gradient Header (Global) -->
<header class="topbar">
    <nav class="navbar navbar-expand gap-3 w-100">
        <!-- Mobile Menu Toggle -->
        <div class="mobile-toggle-menu d-lg-none">
            <i class='bx bx-menu'></i>
        </div>

        <!-- Brand Logo -->
        <div class="brand-logo d-none d-lg-block">
            <a href="dashboard.php" class="d-flex align-items-center text-white text-decoration-none">
                <span style="font-size: 1.5rem; margin-right: 0.5rem;">🐾</span>
                <span class="brand-text">Tierphysio Manager</span>
            </a>
        </div>

        <!-- Search Bar (Desktop) -->
        <div class="search-bar flex-grow-1 d-none d-md-block mx-3">
            <div class="position-relative">
                <input type="text" id="globalSearch" class="form-control search-control" 
                       placeholder="Suche Patient/Besitzer..." autocomplete="off">
                <span class="position-absolute top-50 translate-middle-y search-icon">
                    <i class='bx bx-search'></i>
                </span>
            </div>
            <!-- Search Results Dropdown -->
            <div id="searchResults" class="search-results-dropdown"></div>
        </div>

        <!-- Right Side Menu -->
        <div class="top-menu ms-auto">
            <ul class="navbar-nav align-items-center gap-1">
                <!-- Mobile Search Icon -->
                <li class="nav-item d-md-none">
                    <a class="nav-link text-white" href="javascript:void(0);" onclick="toggleMobileSearch()">
                        <i class='bx bx-search'></i>
                    </a>
                </li>
                
                <!-- Theme Toggle -->
                <li class="nav-item">
                    <button id="themeToggle" class="theme-toggle-btn" type="button" aria-label="Toggle theme">
                        <i class="bi bi-moon-fill"></i>
                    </button>
                </li>
                
                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-white" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                             class="user-img rounded-circle" alt="Avatar" width="36" height="36">
                        <div class="user-info d-none d-lg-block">
                            <p class="user-name mb-0"><?php echo htmlspecialchars($userName); ?></p>
                            <p class="user-role mb-0 small opacity-75"><?php echo htmlspecialchars($userRole); ?></p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($userEmail); ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="dashboard.php">
                            <i class='bx bx-home me-2'></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class='bx bx-cog me-2'></i>Einstellungen
                        </a></li>
                        <?php if (isset($_SESSION['admin_id'])): ?>
                        <li><a class="dropdown-item" href="admin/dashboard.php">
                            <i class='bx bx-shield me-2'></i>Admin Bereich
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class='bx bx-log-out me-2'></i>Abmelden
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>

<!-- Mobile Search Overlay -->
<div id="mobileSearchOverlay" class="mobile-search-overlay">
    <div class="mobile-search-content">
        <button class="close-search" onclick="toggleMobileSearch()">&times;</button>
        <input type="text" id="mobileSearchInput" class="form-control" 
               placeholder="Suche Patient/Besitzer..." autocomplete="off">
        <div id="mobileSearchResults"></div>
    </div>
</div>

<style>
/* Violet Gradient Header - Single Unified Design */
.topbar {
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    height: 70px;
    transition: all 0.3s ease;
}

/* Prevent any duplicate headers */
.topbar + .topbar,
header + header {
    display: none !important;
}

/* Adjust for sidebar on desktop */
@media (min-width: 992px) {
    .topbar {
        left: 260px;
        width: calc(100% - 260px);
    }
    
    .wrapper.toggled .topbar {
        left: 0;
        width: 100%;
    }
}

/* Brand Logo */
.brand-logo {
    font-size: 1.25rem;
    font-weight: 600;
}

.brand-text {
    background: linear-gradient(135deg, rgba(255,255,255,1), rgba(255,255,255,0.9));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

/* Search Bar - Static, No Animations */
.search-bar {
    max-width: 500px;
    position: relative;
    transform: none !important;
    animation: none !important;
}

.search-control {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #fff;
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    border-radius: 50px;
    backdrop-filter: blur(10px);
    transition: background 0.3s ease, border 0.3s ease;
}

.search-control::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-control:focus {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
    outline: none;
    color: #fff;
}

.search-icon {
    right: 1rem;
    color: rgba(255, 255, 255, 0.7);
    pointer-events: none;
}

/* Search Results Dropdown */
.search-results-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid rgba(124, 77, 255, 0.2);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    max-height: 400px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
}

[data-theme="dark"] .search-results-dropdown {
    background: #2a2733;
    border-color: rgba(156, 39, 176, 0.3);
}

.search-results-dropdown .result-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid rgba(124, 77, 255, 0.1);
    transition: background 0.2s;
}

.search-results-dropdown .result-item:hover {
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.1), rgba(156, 39, 176, 0.1));
}

.search-results-dropdown .no-results {
    padding: 1rem;
    text-align: center;
    color: #6b7280;
}

/* Theme Toggle Button - Static, No Animations */
.theme-toggle-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.4);
    color: #fff;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.3s ease, border 0.3s ease, transform 0.3s ease;
    font-size: 1.125rem;
    transform: none !important;
    animation: none !important;
}

.theme-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.6);
}

/* Top Menu - Static, No Animations */
.top-menu {
    transform: none !important;
    animation: none !important;
    transition: none !important;
    position: static !important;
}

.top-menu ul {
    margin: 0;
    padding: 0;
}

/* User Menu */
.user-img {
    border: 2px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.user-name {
    font-weight: 500;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
}

/* Mobile Menu Toggle */
.mobile-toggle-menu {
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    color: #fff;
}

/* Dropdown Styling */
.dropdown-menu {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    margin-top: 0.5rem;
}

[data-theme="dark"] .dropdown-menu {
    background: #2a2733;
    border: 1px solid rgba(156, 39, 176, 0.2);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.1), rgba(156, 39, 176, 0.1));
    padding-left: 1.25rem;
}

/* Mobile Search Overlay */
.mobile-search-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.95), rgba(156, 39, 176, 0.95));
    z-index: 9999;
    padding: 2rem;
    backdrop-filter: blur(20px);
}

.mobile-search-overlay.active {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 20vh;
}

.mobile-search-content {
    width: 100%;
    max-width: 500px;
    position: relative;
}

.close-search {
    position: absolute;
    top: -3rem;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 2.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.close-search:hover {
    opacity: 1;
}

.mobile-search-content input {
    width: 100%;
    padding: 1rem;
    font-size: 1.125rem;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    backdrop-filter: blur(10px);
}

.mobile-search-content input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.mobile-search-content input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.2);
}

#mobileSearchResults {
    margin-top: 1rem;
    background: white;
    border-radius: 12px;
    max-height: 60vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

/* Glass effect - subtle, no animation */
.topbar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.05),
        transparent
    );
    pointer-events: none;
}

/* Dark mode adjustments */
[data-theme="dark"] .topbar {
    background: linear-gradient(135deg, #5a3a99, #7b1fa2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

[data-theme="dark"] .search-control {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

[data-theme="dark"] .search-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

/* Ensure no element causes periodic movements */
* {
    animation-play-state: paused !important;
}

.topbar,
.topbar * {
    animation: none !important;
    animation-play-state: running !important;
}
</style>