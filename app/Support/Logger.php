<?php
declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $path = APP_ROOT . '/logs/app.log';
        $entry = sprintf(
            "[%s] %s: %s %s%s",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
            PHP_EOL
        );

        file_put_contents($path, $entry, FILE_APPEND);
    }
}
