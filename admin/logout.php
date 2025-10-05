<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

auth_session_start();

// CSRF-Protection für Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_validate();
    } catch (RuntimeException $e) {
        // Logout trotzdem durchführen
        logWarning('Logout CSRF validation failed', [
            'admin_id' => $_SESSION['admin_id'] ?? 'unknown',
        ]);
    }
}

// Audit Log vor dem Logout
if (auth_check_admin()) {
    $adminId = auth_admin_id();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (admin_user_id, action, description, ip_address, user_agent)
            VALUES (:user_id, 'logout', 'Admin logout', :ip, :user_agent)
        ");
        $stmt->execute([
            ':user_id' => $adminId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    } catch (PDOException $e) {
        logError('Failed to write audit log on logout', [
            'admin_id' => $adminId,
            'error' => $e->getMessage(),
        ]);
    }
}

// Logout durchführen
auth_admin_logout();

// Weiterleitung
header('Location: login.php');
exit;
