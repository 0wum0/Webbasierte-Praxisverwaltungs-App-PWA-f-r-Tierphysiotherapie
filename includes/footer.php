<?php
/**
 * Unified Footer Component - Scrollable Design
 * Modern footer that scrolls with content (not fixed)
 * @package TierphysioManager
 * @version 3.0.0
 */

// Include version information if available
if (file_exists(__DIR__ . '/version.php')) {
    require_once __DIR__ . '/version.php';
    $changelog = function_exists('getChangelog') ? getChangelog() : [];
} else {
    $changelog = [];
    if (!defined('APP_VERSION')) {
        define('APP_VERSION', '3.0.0');
    }
}

// Get current year dynamically
$currentYear = date('Y');
?>

<!-- Unified Footer (Scrollable, not fixed) -->
<footer class="page-footer">
    <div class="footer-content">
        <p class="footer-main">© <?php echo $currentYear; ?> Tierphysio Eileen Wenzel – Alle Rechte vorbehalten.</p>
        <p class="footer-credits">ERP-System für Tierphysiotherapeut:innen – Coding & Design © <?php echo $currentYear; ?> by Florian Engelhardt</p>
        <?php if (!empty($changelog)): ?>
        <div class="footer-links">
            <a href="#" data-bs-toggle="modal" data-bs-target="#changelogModal" class="footer-link">
                <i class="bi bi-journal-text"></i> Version <?php echo APP_VERSION; ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="javascript:void(0);" class="back-to-top" id="backToTop">
    <i class='bx bxs-up-arrow-alt'></i>
</a>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<?php if (!empty($changelog)): ?>
<!-- Changelog Modal -->
<div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="changelogModalLabel">
                    <i class="bi bi-journal-text me-2"></i>
                    Änderungsprotokoll
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="changelog-container">
                    <?php foreach ($changelog as $version): ?>
                    <div class="changelog-entry">
                        <div class="d-flex align-items-start mb-2">
                            <span class="version-badge version-<?php echo $version['type'] ?? 'default'; ?>">
                                <?php echo $version['icon'] ?? '📦'; ?> Version <?php echo htmlspecialchars($version['version']); ?>
                            </span>
                            <span class="text-muted small ms-auto">
                                <?php echo date('d.m.Y', strtotime($version['date'])); ?>
                            </span>
                        </div>
                        <div class="changes-list">
                            <?php 
                            $changes = is_array($version['changes']) ? $version['changes'] : explode(',', $version['changes']);
                            foreach ($changes as $change): 
                            ?>
                            <div class="change-item">
                                <i class="bi bi-check-circle text-success"></i>
                                <span><?php echo htmlspecialchars(trim($change)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Developer Info -->
                <div class="developer-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Über den Entwickler</h6>
                    <p>Der Tierphysio Manager wurde mit Leidenschaft entwickelt, um Tierphysiotherapeuten bei ihrer täglichen Arbeit zu unterstützen.</p>
                    <div class="dev-details">
                        <div><i class="bi bi-person"></i> <strong>Entwickler:</strong> Florian Engelhardt</div>
                        <div><i class="bi bi-envelope"></i> <strong>Kontakt:</strong> <a href="mailto:florian0engelhardt@gmail.com">florian0engelhardt@gmail.com</a></div>
                        <div><i class="bi bi-code-slash"></i> <strong>Technologien:</strong> PHP 8.2, MySQL, Bootstrap 5, Chart.js</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <a href="/install/installer.php" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-2"></i>Nach Updates suchen
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Footer Styles - SCROLLABLE (NOT FIXED) */
.page-footer {
    position: relative; /* Changed from fixed to relative */
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.03), rgba(156, 39, 176, 0.03));
    border-top: 2px solid rgba(124, 77, 255, 0.1);
    padding: 2rem 1rem;
    text-align: center;
    margin-top: 3rem;
    box-shadow: 0 -2px 10px rgba(124, 77, 255, 0.05);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

/* Adjust for sidebar on desktop */
@media (min-width: 992px) {
    .page-footer {
        margin-left: 260px;
        width: calc(100% - 260px);
    }
    
    .wrapper.toggled .page-footer {
        margin-left: 0;
        width: 100%;
    }
}

[data-theme="dark"] .page-footer {
    background: linear-gradient(135deg, rgba(124, 77, 255, 0.05), rgba(156, 39, 176, 0.05));
    border-top-color: rgba(156, 39, 176, 0.2);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
}

.page-footer p {
    margin: 0;
    line-height: 1.6;
}

.footer-main {
    font-size: 0.9375rem;
    color: #4b5563;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

[data-theme="dark"] .footer-main {
    color: #e5e7eb;
}

.footer-credits {
    font-size: 0.8125rem;
    color: #9ca3af;
    opacity: 0;
    animation: fadeInFooter 2s ease-in forwards;
    animation-delay: 0.5s;
}

[data-theme="dark"] .footer-credits {
    color: #6b7280;
}

.footer-links {
    margin-top: 1rem;
}

.footer-link {
    color: #7C4DFF;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.footer-link:hover {
    color: #9C27B0;
    text-decoration: none;
}

@keyframes fadeInFooter {
    from { 
        opacity: 0; 
        transform: translateY(5px); 
    }
    to { 
        opacity: 0.85; 
        transform: translateY(0); 
    }
}

/* Shine effect */
.page-footer::after {
    content: "";
    position: absolute;
    top: 0;
    left: -40%;
    width: 40%;
    height: 100%;
    background: linear-gradient(120deg, transparent, rgba(124, 77, 255, 0.05), transparent);
    transform: skewX(-20deg);
    animation: footerShine 8s infinite;
    pointer-events: none;
}

@keyframes footerShine {
    0%, 100% { left: -40%; }
    50% { left: 120%; }
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.25rem;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
    box-shadow: 0 4px 15px rgba(124, 77, 255, 0.3);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 6px 20px rgba(124, 77, 255, 0.4);
    color: white;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

[data-theme="dark"] .loading-overlay {
    background: rgba(26, 29, 35, 0.95);
}

.loading-overlay.active {
    display: flex;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(124, 77, 255, 0.1);
    border-top: 4px solid #7C4DFF;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Changelog Modal Styles */
.modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    color: white;
    border: none;
}

[data-theme="dark"] .modal-content {
    background: #1a1d23;
    color: #e5e7eb;
}

.changelog-entry {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(124, 77, 255, 0.1);
}

.changelog-entry:last-child {
    border-bottom: none;
}

.version-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
}

.version-release { background: linear-gradient(135deg, #10b981, #34d399); }
.version-feature { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
.version-bugfix { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.version-security { background: linear-gradient(135deg, #ef4444, #f87171); }
.version-default { background: linear-gradient(135deg, #6b7280, #9ca3af); }

.changes-list {
    margin-top: 1rem;
    padding-left: 1rem;
}

.change-item {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: #6b7280;
}

[data-theme="dark"] .change-item {
    color: #9ca3af;
}

.change-item i {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.developer-info {
    margin-top: 2rem;
    padding: 1rem;
    background: rgba(124, 77, 255, 0.05);
    border-radius: 12px;
}

[data-theme="dark"] .developer-info {
    background: rgba(156, 39, 176, 0.05);
}

.developer-info h6 {
    color: #7C4DFF;
    margin-bottom: 0.75rem;
}

.developer-info p {
    color: #6b7280;
    margin-bottom: 1rem;
}

[data-theme="dark"] .developer-info p {
    color: #9ca3af;
}

.dev-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.dev-details > div {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
}

[data-theme="dark"] .dev-details > div {
    color: #9ca3af;
}

.dev-details i {
    width: 20px;
    color: #7C4DFF;
}

.dev-details a {
    color: #7C4DFF;
    text-decoration: none;
}

.dev-details a:hover {
    color: #9C27B0;
    text-decoration: underline;
}

/* Modal buttons */
.modal-footer .btn-primary {
    background: linear-gradient(135deg, #7C4DFF, #9C27B0);
    border: none;
}

.modal-footer .btn-primary:hover {
    background: linear-gradient(135deg, #6B4BAE, #8B1FA2);
    transform: translateY(-1px);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .page-footer {
        padding: 1.5rem 1rem;
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    .footer-main {
        font-size: 0.875rem;
    }
    
    .footer-credits {
        font-size: 0.75rem;
    }
    
    .back-to-top {
        width: 45px;
        height: 45px;
        font-size: 1rem;
        bottom: 1rem;
        right: 1rem;
    }
}

/* Ensure content doesn't go under footer */
.page-wrapper {
    min-height: 100vh;
    padding-bottom: 2rem; /* Space before footer */
}

.page-content {
    padding-bottom: 2rem;
}
</style>

<script>
// Back to Top functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        // Show/hide based on scroll
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        // Smooth scroll to top
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});

// Loading overlay helper functions
window.showLoading = function() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('active');
    }
};

window.hideLoading = function() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
};
</script>