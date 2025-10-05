<?php
declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . '/../templates');

$twig = new Environment($loader, [
    'cache' => false,
    'debug' => true,
    'autoescape' => 'html'
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Alle Notifications aus der Session holen
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']); // nur einmal anzeigen

$twig->addGlobal('notifications', $notifications);

$twig->addGlobal('user', [
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'Administrator'
]);