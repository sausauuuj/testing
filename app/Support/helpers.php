<?php
declare(strict_types=1);

function app_config(string $name): array
{
    static $loaded = [];

    if (!isset($loaded[$name])) {
        $path = APP_ROOT . '/config/' . $name . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf('Configuration file [%s] was not found.', $name));
        }

        $loaded[$name] = require $path;
    }

    return $loaded[$name];
}

function request_data(): array
{
    $data = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : $_POST;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        $json = json_decode($rawBody ?: '[]', true);

        if (is_array($json)) {
            $data = array_merge($data, $json);
        }
    }

    return sanitize_array($data);
}

function sanitize_array(array $data): array
{
    $sanitized = [];

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_array($value);
            continue;
        }

        $sanitized[$key] = is_string($value) ? trim($value) : $value;
    }

    return $sanitized;
}

function escape_html(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function auth_config(): array
{
    return app_config('auth');
}

function auth_login_path(): string
{
    return 'login.php';
}

function current_script_name(): string
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
}

function is_authenticated(): bool
{
    return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
}

function authenticated_user(): ?array
{
    return is_authenticated() ? $_SESSION['auth_user'] : null;
}

function login_user(array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    session_regenerate_id(true);
    $_SESSION['auth_user'] = $user;
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

function ensure_authenticated(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (in_array(current_script_name(), ['login.php'], true)) {
        return;
    }

    if (is_authenticated()) {
        return;
    }

    $loginPath = auth_login_path();
    header('X-Auth-Redirect: ' . $loginPath);

    $scriptPath = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    if (str_contains($scriptPath, '/api/') || current_script_name() === 'module.php') {
        http_response_code(401);

        if (current_script_name() === 'module.php') {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Unauthorized';
            exit;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Your session has expired. Please log in again.',
            'redirect' => $loginPath,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Location: ' . $loginPath);
    exit;
}

function redirect_if_authenticated(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (!is_authenticated()) {
        return;
    }

    header('Location: index.php');
    exit;
}
