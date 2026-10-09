<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if (Auth::tenant() === null) {
    header('Location: loginpage.php');
    exit;
}
$tenant = Auth::tenant();
$activeTenant = Database::connection()->prepare('SELECT id FROM tenants WHERE id = ? AND active = 1');
$activeTenant->execute([$tenant['id']]);
if ($activeTenant->fetchColumn() === false) {
    Auth::logout();
    header('Location: loginpage.php');
    exit;
}
$pages = [
    'dashboard' => ['Dashibodi', 'dashboard'],
    'profile' => ['Wasifu wangu', 'profile'],
    'contract' => ['Mkataba wa mtandaoni', 'contract'],
    'rules' => ['Sheria za nyumba', 'rules'],
    'payments' => ['Historia ya malipo', 'payments'],
];
$page = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($pages[$page])) {
    $page = 'dashboard';
}
$title = $pages[$page][0];
?>
<!doctype html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f7f3">
    <title><?= h($title) ?> · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/portal.css">
    <script src="assets/portal.js" defer></script>
</head>
<body class="tenant-app" data-csrf="<?= h(csrf_token()) ?>" data-tenant="<?= h($tenant['username']) ?>" data-page="<?= h($page) ?>">
    <div class="portal-layout">
        <aside class="portal-sidebar" id="portal-sidebar">
            <a class="portal-brand" href="dashboardpage.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
            <div class="portal-workspace">PORTAL YA MPANGAJI</div>
            <nav class="portal-nav" aria-label="Menyu kuu">
                <?php foreach ($pages as $key => [$label, $icon]): ?>
                    <a href="dashboardpage.php?page=<?= h($key) ?>" class="portal-nav-link<?= $page === $key ? ' active' : '' ?>"><span aria-hidden="true"><?= ['dashboard' => '▦', 'profile' => '♙', 'contract' => '▤', 'rules' => '⌂', 'payments' => '↗'][$key] ?></span><?= h($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="portal-sidebar-bottom">
                <a class="sidebar-support" href="https://wa.me/255787982159" target="_blank" rel="noopener noreferrer"><span>Unahitaji msaada?</span><strong>Wasiliana nasi ↗</strong></a>
                <div class="portal-account"><span class="portal-avatar" id="sidebar-avatar"><?= h(strtoupper(substr($tenant['username'], 0, 1))) ?></span><span><strong id="sidebar-username"><?= h($tenant['username']) ?></strong><small>Mpangaji</small></span></div>
                <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="portal-logout" type="submit">Toka kwenye akaunti <span>↗</span></button></form>
            </div>
        </aside>
        <main class="portal-main">
            <header class="portal-topbar">
                <button id="portal-menu" class="portal-menu" type="button" aria-label="Fungua menyu">☰</button>
                <div><span class="eyebrow">GYUNDA HOUSE <i>/</i> MPANGAJI</span><strong><?= h($title) ?></strong></div>
                <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Badili mwonekano"><span class="theme-icon">◐</span><span class="theme-label">Mwonekano</span></button>
            </header>
            <div class="portal-content">
                <?php if ($page === 'dashboard'): ?>
                    <section class="welcome-panel">
                        <div><span class="eyebrow">KARIBU KWENYE NYUMBA YAKO</span><h1>Habari, <span id="welcome-name"><?= h($tenant['username']) ?></span> <span class="welcome-sparkle">✳</span></h1><p>Taarifa zako zote muhimu za upangaji ziko hapa.</p></div>
                        <span class="welcome-house" aria-hidden="true">⌂</span>
                    </section>
                    <div class="tenant-stat-grid">
                        <article class="tenant-stat"><span class="tenant-stat-icon">▤</span><span class="eyebrow">HALI YA MKATABA</span><strong id="stat-contract">Inapakia...</strong><small>Mkataba wako wa upangaji</small></article>
                        <article class="tenant-stat"><span class="tenant-stat-icon paid-icon">↗</span><span class="eyebrow">JUMLA ULIYOLIPA</span><strong id="stat-paid">Inapakia...</strong><small>Malipo yote yaliyorekodiwa</small></article>
                        <article class="tenant-stat"><span class="tenant-stat-icon due-icon">◷</span><span class="eyebrow">MALIPO YANAYOFUATA</span><strong id="stat-due">Inapakia...</strong><small>Tarehe ya mwezi ujao</small></article>
                    </div>
                    <div class="portal-dashboard-grid">
                        <section class="portal-surface portal-next-step"><div class="surface-icon">✍</div><span class="eyebrow">ANZA HAPA</span><h2>Saini mkataba wako mtandaoni</h2><p>Jaza taarifa zako na utie sahihi kwa simu au kompyuta. Unaweza kupakua nakala ya PDF mara moja.</p><a class="portal-button portal-button-link" href="dashboardpage.php?page=contract">Fungua mkataba <span>→</span></a></section>
                        <section class="portal-surface recent-payments"><div class="surface-heading"><div><span class="eyebrow">REKODI ZAKO</span><h2>Malipo ya hivi karibuni</h2></div><a href="dashboardpage.php?page=payments">Yote →</a></div><div id="recent-payments" class="portal-table-wrap"><p class="portal-loading">Inapakia malipo...</p></div></section>
                    </div>
                <?php elseif ($page === 'profile'): ?>
                    <section class="portal-page-heading"><span class="eyebrow">TAARIFA ZAKO</span><h1>Wasifu wangu</h1><p>Angalia au sasisha maelezo yako ya mawasiliano.</p></section>
                    <section class="portal-surface profile-card">
                        <div class="profile-photo-block"><img id="profile-photo-preview" alt="Picha ya wasifu" hidden><span id="profile-photo-placeholder" class="profile-photo-placeholder">♙</span><label class="photo-upload" for="profile-photo">Badili picha</label><input id="profile-photo" type="file" accept="image/jpeg,image/png,image/webp" hidden><small>JPG, PNG au WebP · hadi 1 MB</small></div>
                        <form id="profile-form" class="portal-form profile-form"><div class="portal-form-row"><div><label for="profile-name">Jina kamili</label><input id="profile-name" name="full_name" maxlength="160" required></div><div><label for="profile-phone">Namba ya simu</label><input id="profile-phone" name="phone" type="tel" maxlength="30" required></div></div><label for="profile-email">Barua pepe</label><input id="profile-email" name="email" type="email" readonly><div class="profile-meta"><span>Chumba</span><strong id="profile-room">—</strong></div><button class="portal-button" type="submit">HIFADHI TAARIFA <span>→</span></button><p id="profile-message" class="form-message" aria-live="polite"></p></form>
                    </section>
                <?php elseif ($page === 'contract'): ?>
                    <section class="portal-page-heading"><span class="eyebrow">MKATABA WA MTANDAONI</span><h1>Mkataba wa upangaji</h1><p>Jaza taarifa zote, kagua makubaliano na utie sahihi yako.</p></section>
                    <div id="contract-signed" class="portal-surface signed-contract" hidden><span class="signed-stamp">✓ IMESAINIWA</span><span class="eyebrow">MKATABA WAKO UMETUNZWA</span><h2>Mkataba umekamilika</h2><p id="signed-summary"></p><button type="button" class="portal-button" id="download-contract">PAKUA MKATABA WA PDF <span>↓</span></button></div>
                    <section id="contract-form-panel" class="portal-surface contract-panel">
                        <div class="contract-intro"><span class="surface-icon">▤</span><div><span class="eyebrow">MAKUBALIANO YA UPANGAJI</span><h2>Taarifa za mkataba</h2><p>Taarifa hizi zitatumika kuandaa mkataba wako wa upangaji.</p></div></div>
                        <form id="contract-form" class="portal-form">
                            <div class="portal-form-row"><div><label for="contract-name">Jina kamili</label><input id="contract-name" name="full_name" maxlength="160" required></div><div><label for="contract-phone">Namba ya simu</label><input id="contract-phone" name="phone" type="tel" maxlength="30" required></div></div>
                            <div class="portal-form-row"><div><label for="contract-nida">Namba ya NIDA</label><input id="contract-nida" name="nida_number" maxlength="40" required></div><div><label for="contract-start">Tarehe ya kuanza</label><input id="contract-start" name="lease_start" type="date" required></div></div>
                            <div class="portal-form-row"><div><label for="contract-duration">Muda wa mkataba (miezi)</label><input id="contract-duration" name="lease_duration_months" type="number" min="1" max="120" value="12" required></div><div><label for="contract-kin">Jina la ndugu wa karibu</label><input id="contract-kin" name="next_of_kin_name" maxlength="160" required></div></div>
                            <label for="contract-kin-phone">Simu ya ndugu wa karibu</label><input id="contract-kin-phone" name="next_of_kin_phone" type="tel" maxlength="30" required>
                            <div class="contract-terms"><strong>Makubaliano ya upangaji</strong><p>Mimi, mpangaji niliyetajwa hapo juu, ninathibitisha kuwa taarifa nilizojaza ni sahihi. Nitailipa kodi ya nyumba kwa kiasi na muda uliokubaliwa na nitaitunza nyumba kwa kufuata sheria za nyumba. Makubaliano haya yataanza tarehe niliyochagua na yataendelea kwa muda niliotaja, isipokuwa pande zote zikubaliane vinginevyo kwa maandishi.</p><label class="consent-row"><input id="contract-consent" type="checkbox" required><span>Nimesoma na nakubali masharti haya ya upangaji.</span></label></div>
                            <div class="signature-area"><div class="signature-title"><div><label>Sahihi ya mpangaji</label><small>Chora sahihi yako hapa kwa kidole au kipanya.</small></div><button id="clear-signature" type="button" class="clear-signature">Futa sahihi</button></div><canvas id="signature-pad" aria-label="Sehemu ya kuchora sahihi"></canvas><p id="contract-message" class="form-message" aria-live="polite"></p></div>
                            <button class="portal-button" type="submit">KUBALI NA SAINI MKATABA <span>→</span></button>
                        </form>
                    </section>
                <?php elseif ($page === 'rules'): ?>
                    <section class="portal-page-heading"><span class="eyebrow">TUFANYE NYUMBA IWE SALAMA NA RAFIKI</span><h1>Sheria za nyumba</h1><p>Maelekezo rahisi ya kuishi pamoja kwa amani na usalama.</p></section>
                    <section class="rules-toolbar portal-surface"><div><span class="eyebrow">LUGHA / LANGUAGE</span><strong id="rules-language-label">Kiswahili</strong></div><div class="language-switch" role="group" aria-label="Chagua lugha"><button type="button" class="language-button active" data-language="sw">Kiswahili</button><button type="button" class="language-button" data-language="en">English</button></div></section>
                    <section id="rules-list" class="rules-grid" data-language="sw">
                        <article class="portal-surface rule-card" data-rule="rent"><span class="rule-number">01</span><span class="rule-icon">◷</span><h2>Kulipa kodi kwa wakati</h2><p>Lipa kodi kabla au ifikapo tarehe iliyokubaliwa. Ikiwa utachelewa, mjulishe mwenye nyumba mapema.</p></article>
                        <article class="portal-surface rule-card" data-rule="care"><span class="rule-number">02</span><span class="rule-icon">⌂</span><h2>Utunzaji wa nyumba</h2><p>Weka chumba na maeneo ya pamoja katika hali ya usafi. Ripoti hitilafu au uharibifu unapouona.</p></article>
                        <article class="portal-surface rule-card" data-rule="noise"><span class="rule-number">03</span><span class="rule-icon">♫</span><h2>Heshimu majirani</h2><p>Punguza kelele hasa wakati wa usiku na heshimu faragha ya wapangaji wengine.</p></article>
                        <article class="portal-surface rule-card" data-rule="safety"><span class="rule-number">04</span><span class="rule-icon">◇</span><h2>Usalama wa wote</h2><p>Funga milango, usishirikishe funguo na usiwakaribishe wageni wasiojulikana kuingia maeneo ya pamoja.</p></article>
                        <article class="portal-surface rule-card" data-rule="changes"><span class="rule-number">05</span><span class="rule-icon">✳</span><h2>Mabadiliko na wageni</h2><p>Omba ruhusa kabla ya kufanya mabadiliko ya kudumu au kumruhusu mtu mwingine kuhamia.</p></article>
                        <article class="portal-surface rule-card" data-rule="contact"><span class="rule-number">06</span><span class="rule-icon">♡</span><h2>Wasiliana nasi</h2><p>Kwa msaada au dharura ya nyumba, wasiliana na mwenye nyumba kupitia namba iliyo hapa chini.</p></article>
                    </section>
                <?php else: ?>
                    <section class="portal-page-heading"><span class="eyebrow">TAARIFA ZA KODI YAKO</span><h1>Historia ya malipo</h1><p>Malipo yaliyorekodiwa na kiasi kinachosubiri kwa mwezi huu.</p></section>
                    <section class="portal-surface payments-card"><div class="surface-heading"><div><span class="eyebrow">AKAUNTI YAKO</span><h2>Malipo yote</h2></div><span class="payment-unit">Kiasi kwa TZS</span></div><div id="payment-history" class="portal-table-wrap"><p class="portal-loading">Inapakia malipo...</p></div></section>
                <?php endif; ?>
                <footer class="portal-page-footer"><span>GYUNDA HOUSE</span><span>Taarifa zako zinatunzwa kwa usalama.</span></footer>
            </div>
        </main>
    </div>
    <div id="portal-toast" class="portal-toast" role="status" aria-live="polite"></div>
    <footer class="support-footer app-support"><span>Unahitaji msaada?</span><a href="tel:+255787982159">0787982159</a><a class="support-whatsapp" href="https://wa.me/255787982159" target="_blank" rel="noopener noreferrer" aria-label="Wasiliana nasi WhatsApp"><svg class="whatsapp-icon" viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16.03 3.2c-7.02 0-12.73 5.7-12.73 12.72 0 2.24.59 4.43 1.72 6.37L3.2 28.8l6.66-1.75a12.7 12.7 0 0 0 6.17 1.58h.01c7.02 0 12.73-5.71 12.73-12.73 0-3.4-1.32-6.6-3.73-9a12.65 12.65 0 0 0-9.01-3.7Zm0 23.28h-.01a10.56 10.56 0 0 1-5.38-1.47l-.39-.23-3.95 1.04 1.05-3.85-.26-.4a10.55 10.55 0 0 1-1.62-5.65c0-5.83 4.74-10.57 10.57-10.57 2.82 0 5.48 1.1 7.47 3.1a10.5 10.5 0 0 1 3.1 7.48c0 5.83-4.74 10.57-10.58 10.57Zm5.8-7.92c-.32-.16-1.88-.93-2.17-1.04-.29-.1-.5-.15-.7.16-.2.3-.8 1.04-.98 1.25-.18.2-.36.23-.67.08-.31-.16-1.31-.49-2.5-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.13.31-.36.47-.54.16-.18.21-.31.31-.52.1-.2.05-.39-.03-.54-.08-.16-.7-1.7-.96-2.33-.25-.6-.51-.52-.7-.53l-.6-.01c-.21 0-.54.08-.83.39-.29.31-1.08 1.06-1.08 2.58s1.1 3 1.26 3.2c.16.2 2.17 3.31 5.26 4.64.73.32 1.3.51 1.74.65.73.23 1.4.2 1.93.12.59-.09 1.88-.77 2.15-1.5.26-.75.26-1.38.18-1.51-.08-.13-.29-.21-.6-.36Z"/></svg><span>WhatsApp</span></a></footer>
</body>
</html>
