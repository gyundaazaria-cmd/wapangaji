<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$error = '';
$complete = false;
$setupClosed = false;
$setupKey = app_config('setup_key');

try {
    $setupClosed = (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (PDOException $exception) {
    http_response_code(503);
    exit('Database is unavailable. Check your GYUNDA_DB_* settings and import database/schema.sql.');
}

if ($setupClosed) {
    http_response_code(403);
} elseif (strlen($setupKey) < 32) {
    http_response_code(503);
    exit('Initial setup is disabled. Configure a random GYUNDA_SETUP_KEY of at least 32 characters, then reload this page.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$setupClosed) {
    $usernameValue = $_POST['username'] ?? '';
    $passwordValue = $_POST['password'] ?? '';
    $confirmationValue = $_POST['password_confirmation'] ?? '';
    $csrfValue = $_POST['csrf_token'] ?? null;
    $keyValue = $_POST['setup_key'] ?? '';
    $username = is_string($usernameValue) ? trim($usernameValue) : '';
    $password = is_string($passwordValue) ? $passwordValue : '';
    $confirmation = is_string($confirmationValue) ? $confirmationValue : '';
    if (!verify_csrf(is_string($csrfValue) ? $csrfValue : null)) {
        $error = 'Your setup form expired. Reload this page and try again.';
    } elseif (!is_string($keyValue) || !hash_equals($setupKey, $keyValue)) {
        $error = 'The setup key is not valid.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
        $error = 'Use 3–80 letters, numbers, dots, dashes or underscores for the username.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Use a password between 12 and 72 characters.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $registration = Auth::registerFirstAdmin($username, $password);
            if ($registration === 'created') {
                $complete = true;
            } elseif ($registration === 'exists') {
                $setupClosed = true;
                http_response_code(403);
            } else {
                $error = 'Setup is busy. Please wait a moment and try again.';
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That administrator username is already in use.';
            } else {
                error_log('GYUNDA HOUSE administrator setup error: ' . $exception->getMessage());
                $error = 'The administrator account could not be created. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Secure setup · GYUNDA HOUSE</title><link rel="stylesheet" href="assets/app.css"></head>
<body class="setup-body"><main class="setup-card"><button class="admin-language-toggle auth-language-toggle" type="button" data-admin-language-toggle aria-label="Switch language">Kiswahili</button><a class="brand" href="index.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
<?php if ($complete): ?><span class="eyebrow setup-eyebrow">YOU’RE ALL SET</span><h1>Your property workspace is ready.</h1><p>Your administrator account has been created. You can now sign in securely.</p><a class="button button-dark" href="index.php">Go to sign in <span>→</span></a>
<?php elseif ($setupClosed): ?><span class="eyebrow setup-eyebrow">SETUP ALREADY COMPLETE</span><h1>Your manager account is ready.</h1><p>An administrator account already exists for this property, so first-time setup is closed. Return to sign in and use the manager username and password you chose when registering.</p><div class="register-security-note"><span aria-hidden="true">◈</span><span><strong>Registration is protected</strong><small>New accounts cannot be created from this page after the first manager registers.</small></span></div><a class="button button-dark full-button" href="index.php">Go to sign in <span>→</span></a>
<?php else: ?><span class="eyebrow setup-eyebrow">ONE-TIME ADMINISTRATOR SETUP</span><h1>Set up your manager account.</h1><p>Use the setup key you configured on the server. This page closes as soon as the first administrator is created.</p>
<?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="setup-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label for="setup-key">Setup key</label><input id="setup-key" name="setup_key" type="password" autocomplete="off" required><label for="username">Administrator username</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="80" required><label for="password">Password <span class="optional">12–72 CHARACTERS</span></label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><label for="password-confirmation">Confirm password</label><input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><button class="button button-dark full-button" type="submit">Create manager account <span>→</span></button></form><a class="setup-back-link" href="index.php">← Back to sign in</a><?php endif; ?>
</main><script src="assets/admin-language.js" defer></script></body></html>
