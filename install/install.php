<?php
/**
 * Tierphysio Manager - Installation Script
 * @package TierphysioManager
 * @version 3.0.0
 */

// Start session
session_start();

// Check if already installed
$lockFile = __DIR__ . '/../includes/installed.lock';
if (file_exists($lockFile)) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tierphysio Manager - Bereits installiert</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #7C4DFF, #9C27B0);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .install-container {
                background: white;
                padding: 3rem;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                max-width: 500px;
                width: 100%;
            }
            .btn-primary {
                background: linear-gradient(135deg, #7C4DFF, #9C27B0);
                border: none;
            }
            .btn-primary:hover {
                background: linear-gradient(135deg, #6B4BAE, #8B1FA2);
            }
        </style>
    </head>
    <body>
        <div class="install-container text-center">
            <h1 class="mb-4">🐾 Tierphysio Manager</h1>
            <div class="alert alert-info">
                <h5>Die Anwendung ist bereits installiert.</h5>
                <p>Wenn Sie ein Update durchführen möchten, klicken Sie hier.</p>
            </div>
            <a href="../login.php" class="btn btn-primary me-2">Zum Login</a>
            <a href="../migrations/run.php" class="btn btn-outline-primary">Migrations ausführen</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Redirect to installer
header('Location: installer.php');
exit;