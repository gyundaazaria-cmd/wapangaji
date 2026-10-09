<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if (Auth::user() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? null;
    if (!verify_csrf(is_string($csrf) ? $csrf : null)) {
        http_response_code(400);
        $error = 'Your sign-in form expired. Refresh the page and try again.';
    } else {
        $usernameValue = $_POST['username'] ?? '';
        $passwordValue = $_POST['password'] ?? '';
        $username = is_string($usernameValue) ? trim($usernameValue) : '';
        $password = is_string($passwordValue) ? $passwordValue : '';
        try {
            if ($username !== '' && strlen($username) <= 80 && $password !== '' && strlen($password) <= 72
                && Auth::authenticate($username, $password)) {
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $exception) {
            error_log('GYUNDA HOUSE sign-in error: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Sign in is temporarily unavailable. Please try again shortly.';
        }
        if ($error === '') {
            $error = 'Those sign-in details are not valid, or the account is temporarily locked.';
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
    <title>Sign in · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="login-body lobby-login">
    <main class="login-experience" id="login-experience">
        <div class="lobby-background" aria-hidden="true">
            <div class="lobby-window"><span></span><span></span><span></span><span></span></div>
            <div class="lobby-arch"></div>
            <div class="lobby-lamp"><i></i><span></span><b></b></div>
            <div class="lobby-sofa"><i></i><span></span><b></b></div>
            <svg class="lobby-plant plant-left" viewBox="0 0 140 210" aria-hidden="true"><path d="M70 192C66 133 30 94 21 38m50 152C79 125 105 94 113 45M69 162C50 126 44 96 51 58m24 80c-2-44 7-76 24-105M69 190c-5-35-23-61-48-78" fill="none" stroke="#526c50" stroke-width="4" stroke-linecap="round"/><path d="M22 38C3 48 5 66 27 69c10-12 8-24-5-31Zm91 7c19 8 19 25-2 31-11-11-10-23 2-31ZM51 58C31 65 32 83 55 86c9-11 7-22-4-28Zm52-21c-1-22 15-31 27-17-1 19-11 25-27 17ZM21 112C4 98 8 82 30 84c8 13 5 23-9 28Z" fill="#77906b"/><path d="M66 182h8l7 24H59z" fill="#b87955"/></svg>
            <svg class="lobby-plant plant-right" viewBox="0 0 140 210" aria-hidden="true"><path d="M70 192C66 133 30 94 21 38m50 152C79 125 105 94 113 45M69 162C50 126 44 96 51 58m24 80c-2-44 7-76 24-105" fill="none" stroke="#526c50" stroke-width="4" stroke-linecap="round"/><path d="M22 38C3 48 5 66 27 69c10-12 8-24-5-31Zm91 7c19 8 19 25-2 31-11-11-10-23 2-31ZM51 58C31 65 32 83 55 86c9-11 7-22-4-28Zm52-21c-1-22 15-31 27-17-1 19-11 25-27 17Z" fill="#77906b"/><path d="M66 182h8l7 24H59z" fill="#b87955"/></svg>
            <div class="lobby-floor"></div>
        </div>
        <header class="login-topbar">
            <a class="brand" href="index.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
            <div><a class="lobby-overview-link" href="overview.php">Explore the system <span aria-hidden="true">↗</span></a><button class="admin-language-toggle" type="button" data-admin-language-toggle aria-label="Switch language">Kiswahili</button></div>
        </header>
        <div class="lobby-content">
            <section class="character-stage" aria-label="Welcome to Gyunda House">
                <div class="character-message"><span class="live-dot"></span> A WARM WELCOME HOME</div>
                <div class="character-halo"></div>
                <div class="character-shadow"></div>
                <svg class="welcome-character" viewBox="0 0 260 450" role="img" aria-label="Friendly illustrated property manager carrying a backpack">
                    <g class="character-backpack">
                        <path d="M68 164c-18 7-29 23-28 49l3 86c1 11 9 17 19 17h34c11 0 17-8 16-19l-8-99c-2-23-17-37-36-34Z" fill="#9a6248"/>
                        <path d="M67 177c-11 7-17 18-16 35l4 45h46l-5-47c-2-21-14-35-29-33Z" fill="#b77a59"/>
                        <path d="M61 257h42l3 34H63z" fill="#87533e"/>
                        <path d="M61 255c0-8 6-14 14-14h13c8 0 14 6 14 14v9H61z" fill="#c48a65"/>
                        <path d="M77 155c-7-14-5-28 7-38 10-8 23-6 31 4l-4 15c-10 0-19 8-22 21" fill="none" stroke="#78513f" stroke-width="9" stroke-linecap="round"/>
                    </g>
                    <g class="character-body">
                        <path d="M94 158c-16 8-27 25-30 50l-8 104 108 8-3-115c-1-26-15-43-37-49Z" fill="#66836b"/>
                        <path d="M73 225c12-19 29-27 51-25 24 1 39 18 43 42l4 41c2 26-12 43-39 49l-52-5c-25-5-38-20-35-46l5-34c3-10 10-18 23-22Z" fill="#d39067"/>
                        <path d="M77 238c11-13 27-19 48-19 22 1 35 12 40 29" fill="none" stroke="#e4ab84" stroke-width="5" stroke-linecap="round"/>
                        <path d="M96 158c5 19 17 31 33 32 14-2 24-12 31-30l-16-9-32 1Z" fill="#d39168"/>
                        <path d="M111 167c5 12 11 18 19 19 9-2 16-8 21-18l-10-8h-22Z" fill="#f0c19e"/>
                        <path d="M92 159c-18 4-32 17-40 39l-16 43c-4 11 0 19 9 22 9 3 15-2 20-12l24-42 21-15m54-30c17 7 27 22 31 45l5 39c2 10-3 17-12 18-8 1-13-4-15-13l-9-33-25-18" fill="none" stroke="#638168" stroke-width="22" stroke-linecap="round" stroke-linejoin="round"/>
                        <path class="character-gesture-arm" d="m180 202 18 37 18 13" fill="none" stroke="#d39168" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M207 252c3-7 10-10 15-7l10 5c4 2 6 7 3 11-2 4-7 5-11 3l-13-5" fill="#d39168"/>
                        <path d="m81 299-5 68h34l13-61m29-3 12 64h34l-9-75" fill="none" stroke="#334d3a" stroke-width="25" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M78 365c-10 1-17 8-19 17l-1 8h52l1-9c0-9-6-15-15-16Zm83 1c-9 0-15 7-15 16l2 9h54l-2-9c-3-10-9-15-19-15Z" fill="#71513e"/>
                        <path d="M72 385h37m48 1h38" stroke="#d19b6d" stroke-width="3" stroke-linecap="round"/>
                        <path d="M94 86c0-29 17-48 42-48 27 0 44 19 44 49v29c0 27-17 48-43 48-25 0-43-21-43-48Z" fill="#d99a70"/>
                        <path d="M91 94c-9-24 1-55 21-68 9-7 20-10 33-9 21 1 39 17 43 40 2 13-1 26-5 35-5-17-15-29-28-35-13 10-34 15-59 13Z" fill="#44352f"/>
                        <path d="M97 65c8-24 32-34 51-28 12 3 21 11 26 22-19-7-46-3-77 6Z" fill="#554039"/>
                        <path d="M106 102c5-4 10-4 15 0m31 0c5-4 10-4 15 0" fill="none" stroke="#50382e" stroke-width="3" stroke-linecap="round"/>
                        <ellipse cx="115" cy="111" rx="3" ry="4" fill="#332c27"/><ellipse cx="159" cy="111" rx="3" ry="4" fill="#332c27"/>
                        <path d="M132 114c-1 7-3 12-1 14 2 2 5 2 8 0" fill="none" stroke="#bc7957" stroke-width="2" stroke-linecap="round"/>
                        <path d="M119 138c7 8 21 9 30-1" fill="none" stroke="#8d4f43" stroke-width="3" stroke-linecap="round"/>
                        <path d="M110 131c4 2 8 2 12 0m23 0c4 2 8 2 12 0" fill="none" stroke="#cf8866" stroke-width="2" stroke-linecap="round"/>
                    </g>
                </svg>
                <div class="character-caption"><strong>Your property, in good hands.</strong><span>A simpler way to manage home.</span></div>
            </section>
            <section class="login-panel">
                <div class="login-card" id="login-card">
                    <div class="mobile-brand brand"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></div>
                    <div class="form-heading entrance-item"><span class="eyebrow">WELCOME BACK</span><h1>GYUNDA HOUSE</h1><p>Sign in to your property dashboard.</p></div>
                    <?php if ($error !== ''): ?><div class="notice notice-error entrance-item" role="alert"><?= h($error) ?></div><?php endif; ?>
                    <a class="login-overview-link entrance-item" href="overview.php"><span class="overview-play" aria-hidden="true">▶</span><span><strong>New to GYUNDA HOUSE?</strong><small>Watch a quick system overview</small></span><span class="overview-link-arrow" aria-hidden="true">↗</span></a>
                    <form method="post" action="index.php" class="login-form entrance-item">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <label for="username">Username</label>
                        <div class="input-wrap login-username-wrap"><span class="input-icon" aria-hidden="true">◎</span><input id="username" name="username" type="text" autocomplete="username" placeholder="Your username" maxlength="80" required autofocus></div>
                        <div class="password-label"><label for="password">Password</label><span class="secure-note">SECURE SIGN IN</span></div>
                        <div class="input-wrap login-password-wrap"><span class="input-icon" aria-hidden="true">⌑</span><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required><button class="password-toggle" type="button" aria-label="Show password">Show</button></div>
                        <button class="button button-primary login-submit" type="submit">LOGIN <span aria-hidden="true">→</span></button>
                    </form>
                    <p class="login-help entrance-item">Don’t have an account yet? <a class="register-link" href="register.php">Register your manager account <span aria-hidden="true">→</span></a><small class="register-note">First manager account only · closes after registration</small></p>
                    <div class="privacy-note entrance-item"><span aria-hidden="true">◈</span> Your property data is private and protected.</div>
                </div>
                <div class="login-copyright entrance-item">© <?= date('Y') ?> GYUNDA HOUSE <span>•</span> PROPERTY MANAGEMENT, MADE SIMPLE</div>
            </section>
        </div>
    </main>
    <script src="assets/admin-language.js" defer></script>
    <script src="assets/login.js" defer></script>
</body>
</html>
