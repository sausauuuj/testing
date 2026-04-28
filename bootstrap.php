<?php
declare(strict_types=1);

define('APP_ROOT', __DIR__);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/php-errors.log');

require_once APP_ROOT . '/app/Support/helpers.php';

$appConfig = app_config('app');
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('depdev_ims_session');
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

ensure_authenticated();
