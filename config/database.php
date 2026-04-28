<?php
declare(strict_types=1);

$detectMysqlPort = static function (): int {
    $envPort = getenv('IMS_DB_PORT');

    if ($envPort !== false && $envPort !== '') {
        return (int) $envPort;
    }

    $iniPath = 'C:/xampp/mysql/bin/my.ini';

    if (file_exists($iniPath)) {
        $lines = file($iniPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $inMysqlSection = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, ';')) {
                continue;
            }

            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $inMysqlSection = strtolower($trimmed) === '[mysqld]';
                continue;
            }

            if ($inMysqlSection && str_starts_with(strtolower($trimmed), 'port=')) {
                return (int) trim(substr($trimmed, 5));
            }
        }
    }

    return 3307;
};

return [
    'host' => getenv('IMS_DB_HOST') ?: '127.0.0.1',
    'port' => $detectMysqlPort(),
    'database' => getenv('IMS_DB_DATABASE') ?: 'inventory_management_system',
    'username' => getenv('IMS_DB_USERNAME') ?: 'root',
    'password' => getenv('IMS_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
