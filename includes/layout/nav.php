<?php
/**
 * Unified Navigation Component - SSoT Implementation
 * @package TierphysioManager
 * @version 3.0.0
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$isAdmin = isset($_SESSION['admin_id']);

// Include the SSoT HTML partial
include_once __DIR__ . '/_nav.html';
?>

<script>
// Set active navigation state and admin visibility
document.addEventListener('DOMContentLoaded', function() {
    // Show admin menu if needed
    <?php if ($isAdmin): ?>
    const adminMenu = document.getElementById('admin-menu-item');
    if (adminMenu) adminMenu.style.display = 'block';
    <?php endif; ?>
    
    // Set active navigation item based on current page
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
    const navLinks = document.querySelectorAll('.nav-link[data-route]');
    
    navLinks.forEach(link => {
        const route = link.getAttribute('data-route');
        const href = link.getAttribute('href');
        
        // Check if this is the current page
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
            link.closest('li').classList.add('active');
        } else if (currentPage.includes(route)) {
            link.classList.add('active');
            link.closest('li').classList.add('active');
        }
    });
});
</script>