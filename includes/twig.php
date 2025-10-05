<?php
declare(strict_types=1);

/**
 * Twig Template Engine Setup
 */

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

$loader = new FilesystemLoader(__DIR__ . '/../templates');

$isDebug = config('app.debug', false);

$twig = new Environment($loader, [
    'cache' => false, // TODO: Enable in production mit __DIR__ . '/../cache/twig'
    'debug' => $isDebug,
    'autoescape' => 'html',
    'strict_variables' => $isDebug,
]);

if ($isDebug) {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
}

// Session starten falls nötig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF-Token als Twig-Funktion
$twig->addFunction(new TwigFunction('csrf_field', function () {
    return csrf_field();
}, ['is_safe' => ['html']]));

$twig->addFunction(new TwigFunction('csrf_token', function () {
    return csrf_token();
}));

// Output Escaping Helper
$twig->addFunction(new TwigFunction('e', function (string $value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}));

// Alle Notifications aus der Session holen
$notifications = $_SESSION['notify'] ?? [];
unset($_SESSION['notify']); // nur einmal anzeigen

$twig->addGlobal('notifications', $notifications);

$twig->addGlobal('user', [
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'Administrator',
]);

$twig->addGlobal('app', [
    'name' => config('app.name', 'Tierphysio'),
    'env' => config('app.env', 'production'),
    'debug' => $isDebug,
]);