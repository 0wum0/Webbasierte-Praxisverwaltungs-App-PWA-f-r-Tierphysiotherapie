<?php
/**
 * Unified Header Component
 * Modern dashboard-style header with theme toggle and user menu
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

// Determine if we're on dashboard (gradient navbar) or other pages (standard header)
$currentPage = basename($_SERVER['PHP_SELF']);
$isDashboard = ($currentPage === 'dashboard.php');
?>

<?php if ($isDashboard): ?>
<!-- Dashboard Gradient Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-activity me-2"></i>
            Tierphysio Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <button id="themeToggle" class="theme-toggle btn btn-link nav-link" aria-label="Toggle theme">
                        <i class="bi bi-moon-fill"></i>
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" class="rounded-circle" width="32" height="32" alt="Avatar">
                        <span><?php echo htmlspecialchars($userName); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <div class="text-muted small"><?php echo htmlspecialchars($userRole); ?></div>
                            <div class="text-truncate small"><?php echo htmlspecialchars($userEmail); ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Abmelden</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php else: ?>
<!-- Standard Header for Other Pages -->
<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand gap-3 w-100">
            <!-- Mobile Menu Toggle -->
            <div class="mobile-toggle-menu d-lg-none">
                <i class='bx bx-menu'></i>
            </div>

            <!-- Brand (Mobile) -->
            <div class="d-lg-none">
                <a class="navbar-brand" href="dashboard.php" style="color: var(--text-primary);">
                    🐾 Tierphysio
                </a>
            </div>

            <!-- Search Bar -->
            <div class="search-bar flex-grow-1 d-none d-md-block">
                <div class="position-relative search-bar-box">
                    <input type="text" id="globalSearch" class="form-control search-control" placeholder="Suche Patient/Besitzer...">
                    <span class="position-absolute top-50 search-show translate-middle-y">
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
                        <a class="nav-link" href="javascript:void(0);" onclick="toggleMobileSearch()">
                            <i class='bx bx-search'></i>
                        </a>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item">
                        <button id="theme-toggle" class="theme-toggle-btn" data-theme-toggle aria-label="Toggle theme" title="Design wechseln">
                            <i class="bi bi-moon-fill"></i>
                        </button>
                    </li>
                    
                    <!-- Notifications (Optional) -->
                    <li class="nav-item dropdown d-none">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-bell'></i>
                            <span class="badge bg-danger rounded-pill">3</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Benachrichtigungen</h6></li>
                            <li><a class="dropdown-item" href="#">Neue Termine heute</a></li>
                            <li><a class="dropdown-item" href="#">Offene Rechnungen</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Alle anzeigen</a></li>
                        </ul>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="d-flex align-items-center nav-link dropdown-toggle gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars($userAvatar); ?>" class="user-img rounded-circle" alt="Avatar" width="40" height="40">
                            <div class="user-info d-none d-lg-block">
                                <p class="user-name mb-0"><?php echo htmlspecialchars($userName); ?></p>
                                <p class="designattion mb-0 small text-muted"><?php echo htmlspecialchars($userRole); ?></p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($userEmail); ?></div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="dashboard.php"><i class='bx bx-home me-2'></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class='bx bx-cog me-2'></i>Einstellungen</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class='bx bx-log-out me-2'></i>Abmelden</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>

<!-- Mobile Search Overlay -->
<div id="mobileSearchOverlay" class="mobile-search-overlay">
    <div class="mobile-search-content">
        <button class="close-search" onclick="toggleMobileSearch()">&times;</button>
        <input type="text" id="mobileSearchInput" class="form-control" placeholder="Suche Patient/Besitzer..." autofocus>
        <div id="mobileSearchResults"></div>
    </div>
</div>

<style>
.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    max-height: 400px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    margin-top: 0.5rem;
}

.search-results-dropdown .result-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.search-results-dropdown .result-item:last-child {
    border-bottom: none;
}

.search-results-dropdown .result-item:hover {
    background: var(--bg-tertiary);
}

.search-results-dropdown .no-results {
    padding: 1rem;
    text-align: center;
    color: var(--text-muted);
}

.mobile-search-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    padding: 2rem;
}

.mobile-search-overlay.active {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
}

.mobile-search-content {
    width: 100%;
    max-width: 500px;
}

.close-search {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
}

.mobile-search-content input {
    width: 100%;
    padding: 1rem;
    font-size: 1.125rem;
    border-radius: var(--radius-md);
}

#mobileSearchResults {
    margin-top: 1rem;
    background: var(--card-bg);
    border-radius: var(--radius-md);
    max-height: 60vh;
    overflow-y: auto;
}
</style>

<script>
function toggleMobileSearch() {
    const overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        overlay.classList.toggle('active');
        if (overlay.classList.contains('active')) {
            document.getElementById('mobileSearchInput').focus();
        }
    }
}

// Copy search functionality to mobile search
document.addEventListener('DOMContentLoaded', function() {
    const mobileSearchInput = document.getElementById('mobileSearchInput');
    const mobileSearchResults = document.getElementById('mobileSearchResults');
    
    if (mobileSearchInput && mobileSearchResults) {
        mobileSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                mobileSearchResults.innerHTML = '';
                return;
            }
            
            // Reuse the search functionality from main.js
            if (window.TierphysioApp && window.TierphysioApp.SearchManager) {
                window.TierphysioApp.SearchManager.performSearch(query, mobileSearchResults);
            }
        });
    }
});
</script>
<?php endif; ?>