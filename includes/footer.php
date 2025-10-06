<?php
/**
 * Unified Footer Component
 * Modern footer matching dashboard design with animations and changelog
 * @package TierphysioManager
 * @version 3.0
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

<!-- Unified Footer -->
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

<!-- Theme Toggle Button (Floating) -->
<button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
    <i class="bi bi-moon-fill"></i>
</button>

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
                <a href="/install/install.php" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-2"></i>Nach Updates suchen
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Footer Styles */
.page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
    padding: 0.75rem 1rem;
    text-align: center;
    z-index: 1000;
    box-shadow: 0 -2px 10px var(--shadow-sm);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

@media (min-width: 992px) {
    .page-footer {
        left: 260px;
    }
    
    .wrapper.toggled .page-footer {
        left: 0;
    }
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
}

.page-footer p {
    margin: 0;
    line-height: 1.4;
}

.footer-main {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.footer-credits {
    font-size: 0.75rem;
    color: var(--text-muted);
    opacity: 0;
    animation: fadeInFooter 2s ease-in forwards;
    animation-delay: 0.5s;
}

.footer-links {
    margin-top: 0.5rem;
}

.footer-link {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.75rem;
    transition: color 0.2s;
}

.footer-link:hover {
    color: var(--primary-dark);
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

/* Dark mode footer */
[data-theme="dark"] .page-footer,
body.dark-mode .page-footer {
    background: var(--bg-secondary);
    border-top-color: var(--border-color);
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 45px;
    height: 45px;
    background: var(--primary-gradient);
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
    box-shadow: var(--shadow-lg);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
    color: white;
}

/* Theme Toggle Floating Button */
.theme-toggle {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--card-bg);
    border: 2px solid var(--primary-color);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    box-shadow: var(--shadow-lg);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 998;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--text-primary);
}

.theme-toggle:hover {
    transform: scale(1.1) rotate(180deg);
    box-shadow: var(--shadow-xl);
    background: var(--primary-gradient);
    border-color: transparent;
    color: white;
}

/* Adjust position when back-to-top is visible */
.back-to-top.show ~ .theme-toggle {
    bottom: 5.5rem;
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

[data-theme="dark"] .loading-overlay,
body.dark-mode .loading-overlay {
    background: rgba(18, 18, 18, 0.95);
}

.loading-overlay.active {
    display: flex;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--bg-tertiary);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Changelog Modal Styles */
.changelog-entry {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
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

.version-release { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
.version-feature { background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); }
.version-bugfix { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); }
.version-security { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); }
.version-default { background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%); }

.changes-list {
    margin-top: 1rem;
    padding-left: 1rem;
}

.change-item {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.change-item i {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.developer-info {
    margin-top: 2rem;
    padding: 1rem;
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
}

.developer-info h6 {
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.developer-info p {
    color: var(--text-secondary);
    margin-bottom: 1rem;
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
    color: var(--text-secondary);
}

.dev-details i {
    width: 20px;
    color: var(--primary-color);
}

.dev-details a {
    color: var(--primary-color);
    text-decoration: none;
}

.dev-details a:hover {
    text-decoration: underline;
}

/* Modal Dark Mode Support */
[data-theme="dark"] .modal-content,
body.dark-mode .modal-content {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

[data-theme="dark"] .modal-header,
body.dark-mode .modal-header {
    background: var(--primary-gradient);
    color: white;
    border-bottom: 1px solid var(--border-color);
}

[data-theme="dark"] .btn-close,
body.dark-mode .btn-close {
    filter: invert(1);
}

[data-theme="dark"] .developer-info,
body.dark-mode .developer-info {
    background: var(--bg-tertiary);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .page-footer {
        padding: 0.5rem;
        left: 0 !important;
    }
    
    .footer-main {
        font-size: 0.75rem;
    }
    
    .footer-credits {
        font-size: 0.625rem;
    }
    
    .back-to-top,
    .theme-toggle {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        bottom: 1rem;
        right: 1rem;
    }
    
    .back-to-top.show ~ .theme-toggle {
        bottom: 4rem;
    }
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
</script>