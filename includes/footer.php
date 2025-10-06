<?php
/**
 * Footer Component with Changelog Modal and Credits
 * 
 * Include this file at the bottom of your pages to add
 * footer with changelog and developer credits
 */

// Include version information for changelog
require_once __DIR__ . '/version.php';

// Get changelog data
$changelog = getChangelog();
?>

<!-- Footer -->
<footer class="footer text-center mt-5 py-3" style="background: rgba(255, 255, 255, 0.95); border-top: 1px solid #e5e7eb;">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center gap-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#changelogModal" class="text-decoration-none text-muted">
                <i class="bi bi-journal-text me-1"></i>Changelog
            </a>
            <span class="text-muted">|</span>
            <span class="text-muted">Version <?= APP_VERSION ?></span>
            <span class="text-muted">|</span>
            <span class="text-muted">
                © <?= date('Y') ?> Tierphysio Manager – entwickelt von 
                <a href="mailto:florian0engelhardt@gmail.com" class="text-decoration-none" style="color: #8b5cf6;">
                    Florian Engelhardt
                </a>
            </span>
        </div>
    </div>
</footer>

<!-- Changelog Modal -->
<div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <h5 class="modal-title d-flex align-items-center" id="changelogModalLabel">
                    <i class="bi bi-journal-text me-2"></i>
                    Änderungsprotokoll
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div class="changelog-container">
                    <?php foreach ($changelog as $version): ?>
                    <div class="changelog-entry mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-start mb-2">
                            <span class="version-badge me-2" style="
                                background: <?= match($version['type']) {
                                    'release' => 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
                                    'feature' => 'linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%)',
                                    'bugfix' => 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
                                    'security' => 'linear-gradient(135deg, #ef4444 0%, #f87171 100%)',
                                    default => 'linear-gradient(135deg, #6b7280 0%, #9ca3af 100%)'
                                } ?>;
                                color: white;
                                padding: 0.25rem 0.75rem;
                                border-radius: 20px;
                                font-size: 0.875rem;
                                font-weight: 600;
                            ">
                                <?= $version['icon'] ?> Version <?= htmlspecialchars($version['version']) ?>
                            </span>
                            <span class="text-muted small">
                                <?= date('d.m.Y', strtotime($version['date'])) ?>
                            </span>
                        </div>
                        <div class="changes-list ps-2">
                            <?php 
                            // Split changes by comma if multiple changes
                            $changes = explode(',', $version['changes']);
                            foreach ($changes as $change): 
                            ?>
                            <div class="change-item d-flex align-items-start mb-1">
                                <i class="bi bi-check-circle text-success me-2 mt-1" style="font-size: 0.875rem;"></i>
                                <span class="text-secondary"><?= htmlspecialchars(trim($change)) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Developer Info -->
                <div class="developer-info mt-4 p-3" style="background: #f8f9fa; border-radius: 12px;">
                    <h6 class="mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Über den Entwickler
                    </h6>
                    <p class="mb-2 text-secondary">
                        Der Tierphysio Manager wurde mit Leidenschaft entwickelt, um Tierphysiotherapeuten 
                        bei ihrer täglichen Arbeit zu unterstützen.
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <div>
                            <i class="bi bi-person me-2"></i>
                            <strong>Entwickler:</strong> Florian Engelhardt
                        </div>
                        <div>
                            <i class="bi bi-envelope me-2"></i>
                            <strong>Kontakt:</strong> 
                            <a href="mailto:florian0engelhardt@gmail.com" class="text-decoration-none" style="color: #8b5cf6;">
                                florian0engelhardt@gmail.com
                            </a>
                        </div>
                        <div>
                            <i class="bi bi-code-slash me-2"></i>
                            <strong>Technologien:</strong> PHP 8.2, MySQL, Bootstrap 5, Chart.js
                        </div>
                    </div>
                </div>
                
                <!-- Feature Request -->
                <div class="feature-request mt-3 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border-radius: 12px; border-left: 4px solid #3b82f6;">
                    <h6 class="mb-2">
                        <i class="bi bi-lightbulb me-2"></i>
                        Haben Sie Verbesserungsvorschläge?
                    </h6>
                    <p class="mb-0 text-secondary small">
                        Ihre Ideen und Feedback sind willkommen! Kontaktieren Sie uns gerne mit Ihren 
                        Vorschlägen zur Verbesserung der Software.
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <a href="/install/install.php" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    Nach Updates suchen
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Footer Styles */
.footer {
    margin-top: auto;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
}

body.dark-mode .footer {
    background: rgba(45, 49, 54, 0.95);
    border-top-color: #374151 !important;
}

body.dark-mode .footer .text-muted {
    color: #9ca3af !important;
}

/* Changelog Modal Styles */
.changelog-entry:last-child {
    border-bottom: none !important;
}

.change-item {
    transition: transform 0.2s ease;
}

.change-item:hover {
    transform: translateX(5px);
}

body.dark-mode .modal-content {
    background: #1f2937;
    color: #e5e7eb;
}

body.dark-mode .modal-header {
    background: linear-gradient(135deg, #4c1d95 0%, #5b21b6 100%) !important;
}

body.dark-mode .developer-info,
body.dark-mode .feature-request {
    background: #374151 !important;
}

body.dark-mode .text-secondary {
    color: #9ca3af !important;
}

/* Animation for modal appearance */
.modal.fade .modal-dialog {
    transition: transform .3s ease-out;
    transform: translate(0, -50px);
}

.modal.show .modal-dialog {
    transform: none;
}

/* Custom scrollbar for modal */
.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46b3 100%);
}

body.dark-mode .modal-body::-webkit-scrollbar-track {
    background: #374151;
}
</style>