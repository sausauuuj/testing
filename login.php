<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

redirect_if_authenticated();

$app = app_config('app');
$auth = auth_config();
$credentials = $auth['credentials'] ?? [];
$profile = $auth['profile'] ?? [];
$appCssVersion = is_file(__DIR__ . '/assets/css/app.css') ? (string) filemtime(__DIR__ . '/assets/css/app.css') : (string) time();
$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $expectedEmail = trim((string) ($credentials['email'] ?? ''));
    $expectedPassword = (string) ($credentials['password'] ?? '');
    $passwordHash = (string) ($credentials['password_hash'] ?? '');
    $passwordMatches = false;

    if ($expectedPassword !== '') {
        $passwordMatches = hash_equals($expectedPassword, $password);
    } elseif ($passwordHash !== '') {
        $passwordMatches = password_verify($password, $passwordHash);
    }

    if (
        $emailValue !== ''
        && $expectedEmail !== ''
        && strcasecmp($emailValue, $expectedEmail) === 0
        && $passwordMatches
    ) {
        login_user([
            'email' => $expectedEmail,
            'name' => (string) ($profile['name'] ?? 'User'),
            'role' => (string) ($profile['role'] ?? ''),
            'avatar' => (string) ($profile['avatar'] ?? 'U'),
        ]);

        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape_html($app['name']); ?> | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: '#1155A5', surface: '#ECEFF1', ink: '#0f172a' },
                    boxShadow: { panel: '0 16px 40px rgba(15, 23, 42, 0.14)' },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= escape_html($appCssVersion); ?>">
</head>
<body class="login-page bg-surface text-ink antialiased">
    <div class="login-layout">
        <div class="login-layout__visual" aria-hidden="true">
            <div class="login-visual-card"></div>
        </div>

        <div class="login-layout__content">
            <div class="login-panel">
                <div class="login-panel__brand login-panel__brand--stacked">
                    <div class="login-panel__logo-wrap login-panel__logo-wrap--large">
                        <img src="assets/images/DEPDev_Logo_High-res.svg.png" alt="DEPDev IX logo" class="login-panel__logo">
                    </div>
                    <div class="login-panel__brand-copy">
                        <p class="login-panel__system">ASSET MANAGEMENT SYSTEM</p>
                        <p class="login-panel__department">Department of Economy, Planning, and Development - IX</p>
                    </div>
                </div>

                <div class="login-panel__heading-wrap">
                    <h1 class="login-panel__heading">LOGIN</h1>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="login-panel__alert" role="alert"><?= escape_html($error); ?></div>
                <?php endif; ?>

                <form method="post" class="login-form">
                    <label class="form-group">
                        <span class="form-label">Email</span>
                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter email"
                            value="<?= escape_html($emailValue); ?>"
                            autocomplete="username"
                            required
                        >
                    </label>
                    <label class="form-group">
                        <span class="form-label">Password</span>
                        <input
                            type="password"
                            name="password"
                            class="form-input"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            required
                        >
                    </label>
                    <div class="login-form__actions">
                        <button type="submit" class="action-primary login-form__submit">Log In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

