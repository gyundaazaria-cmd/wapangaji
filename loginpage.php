<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if (Auth::tenant() !== null) {
    $pdo = Database::connection();
    $active = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND active = 1');
    $active->execute([Auth::tenant()['id']]);
    if ($active->fetchColumn() !== false) {
        header('Location: dashboardpage.php');
        exit;
    }
    unset($_SESSION['tenant_id'], $_SESSION['user_role'], $_SESSION['username']);
}
$error = '';
$loginValue = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Ukurasa umeisha muda wake. Tafadhali jaribu tena.';
    } elseif ($loginValue === '' || $password === '') {
        $error = 'Jaza jina la mtumiaji/barua pepe na nenosiri.';
    } elseif (!Auth::authenticateTenant($loginValue, $password)) {
        $error = 'Taarifa za kuingia si sahihi, au akaunti imefungwa kwa muda.';
    } else {
        header('Location: dashboardpage.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f7f3">
    <title>Ingia · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/portal.css">
</head>
<body class="portal-auth">
    <main class="auth-shell">
        <section class="auth-art auth-art-login">
            <a class="portal-brand" href="../index.php"><span class="brand-mark">G</span> GYUNDA <b>HOUSE</b></a>
            <div class="auth-art-copy">
                <span class="eyebrow">NYUMBA YAKO, HUDUMA RAHISI</span>
                <h1>Karibu nyumbani.</h1>
                <p>Angalia taarifa za upangaji, rekodi zako za malipo na mkataba wako kwa urahisi.</p>
            </div>
            <div class="lobby-art" aria-hidden="true"><span class="lobby-window"></span><span class="lobby-plant">♧</span><span class="lobby-sofa"></span><span class="lobby-lamp"></span></div>
            <span class="art-caption">GYUNDA HOUSE · PORTAL YA MPANGAJI</span>
        </section>
        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-icon" aria-hidden="true">⌂</div>
                <span class="eyebrow">PORTAL YA MPANGAJI</span>
                <h2>Ingia kwenye akaunti</h2>
                <p>Weka taarifa zako ili kuendelea.</p>
                <?php if ($error !== ''): ?><div class="portal-alert" role="alert"><?= h($error) ?></div><?php endif; ?>
                <form method="post" class="portal-form">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <label for="login">Jina la mtumiaji au barua pepe</label>
                    <input id="login" name="login" autocomplete="username" value="<?= h($loginValue) ?>" required>
                    <label for="password">Nenosiri</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="portal-button" type="submit">INGIA <span>→</span></button>
                </form>
                <p class="auth-switch">Una mwaliko kutoka kwa mwenye nyumba? <a href="registerpage.php">Fungua akaunti</a></p>
            </div>
        </section>
    </main>
    <footer class="support-footer"><span>Unahitaji msaada?</span><a href="tel:+255787982159">0787982159</a><a class="support-whatsapp" href="https://wa.me/255787982159" target="_blank" rel="noopener noreferrer" aria-label="Wasiliana nasi WhatsApp"><svg class="whatsapp-icon" viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16.03 3.2c-7.02 0-12.73 5.7-12.73 12.72 0 2.24.59 4.43 1.72 6.37L3.2 28.8l6.66-1.75a12.7 12.7 0 0 0 6.17 1.58h.01c7.02 0 12.73-5.71 12.73-12.73 0-3.4-1.32-6.6-3.73-9a12.65 12.65 0 0 0-9.01-3.7Zm0 23.28h-.01a10.56 10.56 0 0 1-5.38-1.47l-.39-.23-3.95 1.04 1.05-3.85-.26-.4a10.55 10.55 0 0 1-1.62-5.65c0-5.83 4.74-10.57 10.57-10.57 2.82 0 5.48 1.1 7.47 3.1a10.5 10.5 0 0 1 3.1 7.48c0 5.83-4.74 10.57-10.58 10.57Zm5.8-7.92c-.32-.16-1.88-.93-2.17-1.04-.29-.1-.5-.15-.7.16-.2.3-.8 1.04-.98 1.25-.18.2-.36.23-.67.08-.31-.16-1.31-.49-2.5-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.13.31-.36.47-.54.16-.18.21-.31.31-.52.1-.2.05-.39-.03-.54-.08-.16-.7-1.7-.96-2.33-.25-.6-.51-.52-.7-.53l-.6-.01c-.21 0-.54.08-.83.39-.29.31-1.08 1.06-1.08 2.58s1.1 3 1.26 3.2c.16.2 2.17 3.31 5.26 4.64.73.32 1.3.51 1.74.65.73.23 1.4.2 1.93.12.59-.09 1.88-.77 2.15-1.5.26-.75.26-1.38.18-1.51-.08-.13-.29-.21-.6-.36Z"/></svg><span>WhatsApp</span></a></footer>
</body>
</html>
