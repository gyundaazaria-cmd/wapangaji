<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if (Auth::user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$registrationClosed = false;

try {
    $registrationClosed = (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
} catch (PDOException $exception) {
    error_log('GYUNDA HOUSE registration availability error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Registration is temporarily unavailable. Check the database connection and try again.');
}

if ($registrationClosed) {
    http_response_code(403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$registrationClosed) {
    $usernameValue = $_POST['username'] ?? '';
    $passwordValue = $_POST['password'] ?? '';
    $confirmationValue = $_POST['password_confirmation'] ?? '';
    $csrfValue = $_POST['csrf_token'] ?? null;
    $username = is_string($usernameValue) ? trim($usernameValue) : '';
    $password = is_string($passwordValue) ? $passwordValue : '';
    $confirmation = is_string($confirmationValue) ? $confirmationValue : '';

    if (!verify_csrf(is_string($csrfValue) ? $csrfValue : null)) {
        $error = 'Your registration form expired. Reload the page and try again.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
        $error = 'Choose a username using 3–80 letters, numbers, dots, dashes or underscores.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Choose a password between 12 and 72 characters.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $result = Auth::registerFirstAdmin($username, $password);
            if ($result === 'created') {
                $statement = Database::connection()->prepare('SELECT id FROM users WHERE username = ?');
                $statement->execute([$username]);
                $userId = $statement->fetchColumn();
                if ($userId === false) {
                    throw new RuntimeException('The newly registered administrator could not be loaded.');
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $userId;
                $_SESSION['username'] = $username;
                unset($_SESSION['csrf_token']);
                csrf_token();
                header('Location: dashboard.php');
                exit;
            }

            $registrationClosed = $result === 'exists';
            if ($registrationClosed) {
                http_response_code(403);
            } else {
                http_response_code(503);
                $error = 'Registration is busy. Please wait a moment and try again.';
            }
        } catch (PDOException $exception) {
            error_log('GYUNDA HOUSE manager registration error: ' . $exception->getMessage());
            http_response_code(503);
            $error = $exception->getCode() === '23000'
                ? 'That username is already in use.'
                : 'Your manager account could not be created. Please check the database connection and try again.';
        } catch (RuntimeException $exception) {
            error_log('GYUNDA HOUSE manager registration error: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Your account was saved, but sign-in could not be completed. Please return to sign in.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8f4">
    <meta name="description" content="Register the first GYUNDA HOUSE property manager account.">
    <title>Register manager · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="setup-body">
    <main class="setup-card">
        <button class="admin-language-toggle auth-language-toggle" type="button" data-admin-language-toggle aria-label="Switch language">Kiswahili</button>
        <a class="brand" href="index.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
        <?php if ($registrationClosed): ?>
            <span class="eyebrow setup-eyebrow">REGISTRATION COMPLETE</span>
            <h1>This property already has a manager.</h1>
            <p>For security, only the first manager account can be registered here. Sign in to continue.</p>
            <a class="button button-dark full-button" href="index.php">Go to sign in <span aria-hidden="true">→</span></a>
        <?php else: ?>
            <span class="eyebrow setup-eyebrow">CREATE YOUR MANAGER ACCOUNT</span>
            <h1>Welcome to your property workspace.</h1>
            <p>Register the first manager account for GYUNDA HOUSE. Registration closes automatically after the account is created.</p>
            <div class="register-security-note"><span aria-hidden="true">◈</span><span><strong>First account only</strong><small>Only register on your own trusted property system. After registration, this page is closed to everyone.</small></span></div>
            <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= h($error) ?></div><?php endif; ?>
            <form method="post" action="register.php" class="setup-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label for="username">Manager username</label>
                <input id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="80" placeholder="e.g. manager" required autofocus>
                <label for="password">Password <span class="optional">12–72 CHARACTERS</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                <label for="password-confirmation">Confirm password</label>
                <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                <button class="button button-dark full-button" type="submit">Create manager account <span aria-hidden="true">→</span></button>
            </form>
        <?php endif; ?>
        <a class="setup-back-link" href="index.php">← Back to sign in</a>
    </main>
    <script src="assets/admin-language.js" defer></script>
</body>
</html>
