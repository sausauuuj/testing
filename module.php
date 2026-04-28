<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$view = strtolower(trim((string) ($_GET['view'] ?? 'dashboard')));
$allowed = [
    'dashboard' => __DIR__ . '/modules/dashboard.php',
    'registration' => __DIR__ . '/modules/registration.php',
    'assets' => __DIR__ . '/modules/assets.php',
    'inventory' => __DIR__ . '/modules/inventory.php',
    'manage' => __DIR__ . '/modules/assets.php',
    'reports' => __DIR__ . '/modules/reports.php',
];

if (!isset($allowed[$view])) {
    http_response_code(404);
    echo 'Module not found.';
    exit;
}

require $allowed[$view];
