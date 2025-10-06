<?php
/**
 * Footer Component - Not Fixed, Scrolls with Content
 * @package TierphysioManager
 * @version 3.0.0
 */
?>

<!-- Footer (Scrollable with Content) -->
<footer class="app-footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">© 2024 Tierphysio Manager - Entwickelt mit ❤️ für Tierphysiotherapeuten</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-links">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#changelogModal" class="text-decoration-none me-3">
                        <i class="bi bi-journal-text"></i> Changelog
                    </a>
                    <span class="text-muted">Version <?php echo defined('APP_VERSION') ? APP_VERSION : '3.0.0'; ?></span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" aria-label="Zurück nach oben">
    <i class="bx bx-chevron-up"></i>
</button>

<!-- Changelog Modal -->
<div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true" style="z-index: 1055;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changelogModalLabel">
                    <i class="bi bi-journal-text"></i> Changelog
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="changelog-content">
                    <div class="changelog-item">
                        <h6 class="fw-bold text-primary">Version 3.0.0</h6>
                        <p class="small text-muted">06. Oktober 2024</p>
                        <ul class="small">
                            <li>✨ Komplett überarbeitetes Design mit Violet-Gradient-Theme</li>
                            <li>🔧 Header-Duplikate behoben</li>
                            <li>🎨 Dark/Light Mode Unterstützung</li>
                            <li>📱 Verbesserte mobile Responsivität</li>
                            <li>🚀 Performance-Optimierungen</li>
                            <li>🐛 Modal-Z-Index-Probleme behoben</li>
                            <li>📊 KPI-Dashboard implementiert</li>
                        </ul>
                    </div>
                    
                    <div class="changelog-item">
                        <h6 class="fw-bold text-primary">Version 2.5.0</h6>
                        <p class="small text-muted">15. September 2024</p>
                        <ul class="small">
                            <li>📧 Verbesserte E-Mail-Funktionen</li>
                            <li>📅 Terminverwaltung optimiert</li>
                            <li>🔍 Globale Suchfunktion hinzugefügt</li>
                        </ul>
                    </div>
                    
                    <div class="changelog-item">
                        <h6 class="fw-bold text-primary">Version 2.0.0</h6>
                        <p class="small text-muted">01. August 2024</p>
                        <ul class="small">
                            <li>🎨 Neues Benutzerinterface</li>
                            <li>📱 Mobile App-Unterstützung</li>
                            <li>🔒 Verbesserte Sicherheit</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Footer Styles - Not Fixed */
.app-footer {
    background: var(--bg-secondary);
    padding: 1.5rem 0;
    margin-top: 3rem;
    border-top: 1px solid var(--border-color);
    position: relative; /* Not fixed! */
    width: 100%;
    color: var(--text-secondary);
}

[data-theme="dark"] .app-footer {
    background: var(--bg-tertiary);
    border-top-color: rgba(156, 39, 176, 0.2);
}

.footer-links a {
    color: var(--primary-color);
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--primary-dark);
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
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(124, 77, 255, 0.3);
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
    font-size: 1.5rem;
}

.back-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(124, 77, 255, 0.4);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
}

/* Changelog Modal Styles */
.changelog-item {
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.changelog-item:last-child {
    border-bottom: none;
}

.changelog-item ul {
    margin-bottom: 0;
    padding-left: 1.5rem;
}

.changelog-item li {
    margin-bottom: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .app-footer {
        text-align: center;
    }
    
    .footer-links {
        margin-top: 1rem;
    }
}

/* Ensure footer is at bottom of page but not fixed */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.wrapper {
    flex: 1 0 auto;
}

.app-footer {
    flex-shrink: 0;
}
</style>