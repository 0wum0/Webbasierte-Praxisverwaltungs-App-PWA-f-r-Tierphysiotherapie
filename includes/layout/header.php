<?php
/**
 * Unified Header Component - Elegant Header Design
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
$userAvatar = $_SESSION['user_avatar'] ?? 'assets/img/avatar.png';

// Current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Include header styles for PHP files that don't use main.css -->
<style>
<?php include __DIR__ . '/header-styles.css'; ?>
</style>

<header class="app-header">
  <div class="header-left">
    <button class="menu-toggle" id="menuToggle">
      <i class="bi bi-list"></i>
    </button>
    <h1 class="app-title">Tierphysio Praxis</h1>
  </div>
  <div class="header-right">
    <div class="dropdown user-dropdown">
      <button class="dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="/public/img/user.png" alt="User" class="user-avatar">
        <span class="username"><?php echo htmlspecialchars($userName ?? 'Eileen Wenzel'); ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
        <li><a class="dropdown-item" href="/profile.php"><i class="bi bi-person"></i> Profil</a></li>
        <li><a class="dropdown-item" href="/settings.php"><i class="bi bi-gear"></i> Einstellungen</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/logout.php"><i class="bi bi-box-arrow-right"></i> Abmelden</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- Search Overlay -->
<div id="searchOverlay" class="search-overlay" style="display: none;">
    <div class="search-overlay-content">
        <button class="close-search" onclick="closeSearch()">&times;</button>
        <input type="text" id="searchInput" class="form-control search-input" placeholder="Suche Patient/Besitzer..." autocomplete="off">
        <div id="searchResults" class="search-results"></div>
    </div>
</div>
