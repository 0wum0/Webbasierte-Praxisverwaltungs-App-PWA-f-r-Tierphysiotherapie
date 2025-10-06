<?php
/**
 * Unified Header Component - SSoT Implementation
 * @package TierphysioManager
 * @version 3.0.0
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user information for dynamic content
$userName = $_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Benutzer';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['admin_email'] ?? '';
$userRole = isset($_SESSION['admin_id']) ? 'Administrator' : 'Benutzer';

// Include the SSoT HTML partial
include_once __DIR__ . '/_header.html';
?>

<script>
// Set dynamic user information
document.addEventListener('DOMContentLoaded', function() {
    const usernameEl = document.getElementById('headerUsername');
    if (usernameEl) {
        usernameEl.textContent = <?php echo json_encode($userName); ?>;
    }
});
</script>
