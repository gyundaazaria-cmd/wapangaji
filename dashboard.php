<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

if (Auth::user() === null) {
    header('Location: index.php');
    exit;
}

$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8f4">
    <title>Overview · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="app-body" data-csrf="<?= h(csrf_token()) ?>" data-username="<?= h($user['username']) ?>">
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand sidebar-brand" href="dashboard.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
            <div class="workspace-label">PROPERTY WORKSPACE</div>
            <nav class="main-nav" aria-label="Main navigation">
                <a href="#overview" class="nav-item active" data-view="overview"><span class="nav-icon">▦</span> Overview</a>
                <a href="#tenants" class="nav-item" data-view="tenants"><span class="nav-icon">♙</span> Tenants <span class="nav-count" id="nav-tenant-count">—</span></a>
                <a href="#rooms" class="nav-item" data-view="rooms"><span class="nav-icon">⌂</span> Rooms</a>
                <a href="#payments" class="nav-item" data-view="payments"><span class="nav-icon">↗</span> Payments</a>
            </nav>
            <div class="sidebar-bottom">
                <section class="contact-card" aria-label="Contact GYUNDA HOUSE">
                    <span class="eyebrow">NEED A HAND?</span>
                    <h2>Contact us</h2>
                    <a class="contact-phone" href="tel:+255787982159">0787 982 159</a>
                    <a class="whatsapp-link" href="https://wa.me/255787982159" target="_blank" rel="noopener noreferrer" aria-label="Chat with GYUNDA HOUSE on WhatsApp at 0787 982 159">
                        <svg class="whatsapp-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M16.03 3.2c-7.02 0-12.73 5.7-12.73 12.72 0 2.24.59 4.43 1.72 6.37L3.2 28.8l6.66-1.75a12.7 12.7 0 0 0 6.17 1.58h.01c7.02 0 12.73-5.71 12.73-12.73 0-3.4-1.32-6.6-3.73-9a12.65 12.65 0 0 0-9.01-3.7Zm0 23.28h-.01a10.56 10.56 0 0 1-5.38-1.47l-.39-.23-3.95 1.04 1.05-3.85-.26-.4a10.55 10.55 0 0 1-1.62-5.65c0-5.83 4.74-10.57 10.57-10.57 2.82 0 5.48 1.1 7.47 3.1a10.5 10.5 0 0 1 3.1 7.48c0 5.83-4.74 10.57-10.58 10.57Zm5.8-7.92c-.32-.16-1.88-.93-2.17-1.04-.29-.1-.5-.15-.7.16-.2.3-.8 1.04-.98 1.25-.18.2-.36.23-.67.08-.31-.16-1.31-.49-2.5-1.55-.93-.83-1.56-1.86-1.74-2.17-.18-.31-.02-.48.14-.64.14-.13.31-.36.47-.54.16-.18.21-.31.31-.52.1-.2.05-.39-.03-.54-.08-.16-.7-1.7-.96-2.33-.25-.6-.51-.52-.7-.53l-.6-.01c-.21 0-.54.08-.83.39-.29.31-1.08 1.06-1.08 2.58s1.1 3 1.26 3.2c.16.2 2.17 3.31 5.26 4.64.73.32 1.3.51 1.74.65.73.23 1.4.2 1.93.12.59-.09 1.88-.77 2.15-1.5.26-.75.26-1.38.18-1.51-.08-.13-.29-.21-.6-.36Z"/>
                        </svg>
                        <span>Message us on WhatsApp</span>
                        <span class="whatsapp-external" aria-hidden="true">↗</span>
                    </a>
                </section>
                <div class="profile-row"><span class="avatar"><?= h(strtoupper(substr($user['username'], 0, 1))) ?></span><span class="profile-info"><strong><?= h($user['username']) ?></strong><small>Property manager</small></span><form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="icon-button logout-button" type="submit" aria-label="Sign out" title="Sign out">↗</button></form></div>
            </div>
        </aside>
        <main class="main-content">
            <header class="topbar"><button class="mobile-menu icon-button" id="menu-toggle" aria-label="Toggle navigation">☰</button><div class="breadcrumb">GYUNDA HOUSE <span>/</span> <strong id="breadcrumb-current">Overview</strong></div><div class="topbar-right"><button class="admin-language-toggle" type="button" data-admin-language-toggle aria-label="Switch language">Kiswahili</button><span class="today-label" id="today-label"></span><span class="topbar-avatar"><?= h(strtoupper(substr($user['username'], 0, 1))) ?></span></div></header>
            <div class="page-wrap">
                <section class="view-section active" id="view-overview">
                    <div class="page-heading"><div><span class="eyebrow">YOUR PROPERTY AT A GLANCE</span><h1>Good morning, <?= h($user['username']) ?> <span class="wave">✳</span></h1><p>Here’s what’s happening at your property today.</p></div><button class="button button-dark" data-open-modal="payment-modal"><span>＋</span> Record a payment</button></div>
                    <div class="stats-grid">
                        <article class="stat-card stat-tenants"><div class="stat-top"><span class="stat-icon icon-green">♙</span><span class="stat-caption">CURRENTLY STAYING</span></div><div class="stat-number" id="metric-tenants">—</div><div class="stat-footer"><span>Total active tenants</span><span class="stat-arrow">↗</span></div></article>
                        <article class="stat-card stat-revenue"><div class="stat-top"><span class="stat-icon icon-peach">↗</span><span class="stat-caption">ALL-TIME COLLECTIONS</span></div><div class="stat-number currency-number" id="metric-revenue">—</div><div class="stat-footer"><span id="revenue-caption">Rent received to date</span><span class="stat-arrow">↗</span></div></article>
                        <article class="stat-card stat-vacant"><div class="stat-top"><span class="stat-icon icon-blue">⌂</span><span class="stat-caption">READY FOR SOMEONE NEW</span></div><div class="stat-number" id="metric-vacant">—</div><div class="stat-footer"><span>Vacant rooms</span><span class="stat-arrow">↗</span></div></article>
                        <article class="stat-card stat-pending"><div class="stat-top"><span class="stat-icon icon-yellow">◷</span><span class="stat-caption">THIS MONTH · <span id="metric-period"></span></span></div><div class="stat-number currency-number" id="metric-pending">—</div><div class="stat-footer"><span>Rent still to collect</span><span class="stat-arrow">↗</span></div></article>
                    </div>
                    <div class="overview-grid">
                        <section class="surface recent-surface"><div class="section-heading"><div><span class="eyebrow">THE LATEST</span><h2>Recent payments</h2></div><button class="text-link" data-navigate="payments">See all <span>→</span></button></div><div id="recent-payments" class="table-wrap"></div></section>
                        <section class="surface occupancy-surface"><div class="section-heading"><div><span class="eyebrow">AT A GLANCE</span><h2>Room overview</h2></div><button class="icon-button subtle-button" data-navigate="rooms" aria-label="View rooms">↗</button></div><div class="occupancy-visual"><div class="occupancy-ring" id="occupancy-ring"><div class="ring-inner"><strong id="occupancy-rate">—</strong><span>OCCUPIED</span></div></div><div class="occupancy-key"><div><i class="key-dot occupied-dot"></i><span>Occupied</span><strong id="occupied-count">—</strong></div><div><i class="key-dot vacant-dot"></i><span>Vacant</span><strong id="vacant-count">—</strong></div><div><i class="key-dot total-dot"></i><span>Total rooms</span><strong id="total-count">—</strong></div></div></div><button class="button button-outline full-button" data-navigate="rooms">Manage rooms <span>→</span></button></section>
                    </div>
                    <section class="surface quick-actions"><div><span class="eyebrow">A LITTLE LESS ADMIN</span><h2>What would you like to do?</h2></div><div class="action-buttons"><button class="quick-action" data-open-modal="tenant-modal"><span class="quick-icon">＋</span><span><strong>Add a tenant</strong><small>Register someone new</small></span><b>→</b></button><button class="quick-action" data-open-modal="room-modal"><span class="quick-icon quick-icon-blue">⌂</span><span><strong>Add a room</strong><small>Update your property</small></span><b>→</b></button></div></section>
                </section>
                <section class="view-section" id="view-tenants">
                    <div class="page-heading"><div><span class="eyebrow">PEOPLE MAKE A PLACE</span><h1>Your tenants</h1><p>Keep the people and contact details at your property in one place.</p></div><button class="button button-dark" data-open-modal="tenant-modal"><span>＋</span> Add a tenant</button></div>
                    <section class="surface data-surface"><div class="table-toolbar"><div class="section-heading"><div><span class="eyebrow">TENANT DIRECTORY</span><h2>All tenants <span class="heading-count" id="tenant-list-count">0</span></h2></div></div><label class="search-field"><span>⌕</span><input id="tenant-search" type="search" placeholder="Search tenants..."></label></div><div class="table-wrap" id="tenant-table"></div></section>
                </section>
                <section class="view-section" id="view-rooms">
                    <div class="page-heading"><div><span class="eyebrow">MAKE ROOM FOR MORE</span><h1>Your rooms</h1><p>Know what’s occupied and what’s ready to rent.</p></div><button class="button button-dark" data-open-modal="room-modal"><span>＋</span> Add a room</button></div>
                    <section class="surface data-surface"><div class="section-heading table-toolbar"><div><span class="eyebrow">PROPERTY DIRECTORY</span><h2>All rooms <span class="heading-count" id="room-list-count">0</span></h2></div></div><div class="room-grid" id="room-grid"></div></section>
                </section>
                <section class="view-section" id="view-payments">
                    <div class="page-heading"><div><span class="eyebrow">EVERY SHILLING, ACCOUNTED FOR</span><h1>Rent payments</h1><p>Track exactly what has been paid, and when.</p></div><button class="button button-dark" data-open-modal="payment-modal"><span>＋</span> Record a payment</button></div>
                    <section class="surface data-surface"><div class="table-toolbar"><div class="section-heading"><div><span class="eyebrow">PAYMENT HISTORY</span><h2>All payments <span class="heading-count" id="payment-list-count">0</span></h2></div></div><label class="search-field"><span>⌕</span><input id="payment-search" type="search" placeholder="Search tenant or reference..."></label></div><div class="table-wrap" id="payment-table"></div></section>
                </section>
                <footer class="app-footer"><span>GYUNDA HOUSE</span><span>Property management, made a little simpler.</span></footer>
            </div>
        </main>
    </div>
    <div class="toast-region" id="toast-region" aria-live="polite"></div>
    <div class="modal-backdrop" id="tenant-modal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="tenant-modal-title"><button class="modal-close" data-close-modal aria-label="Close">×</button><span class="eyebrow">A NEW FACE AT HOME</span><h2 id="tenant-modal-title">Add a tenant</h2><p class="modal-intro">Add the tenant’s details and their room to your property directory.</p><form id="tenant-form"><label for="tenant-name">Full name</label><input id="tenant-name" name="full_name" maxlength="160" placeholder="e.g. Amina Mwinyi" required><div class="form-two"><div><label for="tenant-phone">Phone number</label><input id="tenant-phone" name="phone" type="tel" maxlength="30" placeholder="+255 7XX XXX XXX" required></div><div><label for="tenant-alt-phone">Alternative phone <span class="optional">OPTIONAL</span></label><input id="tenant-alt-phone" name="alternate_phone" type="tel" maxlength="30" placeholder="+255 7XX XXX XXX"></div></div><div class="form-two"><div><label for="tenant-room">Room</label><select id="tenant-room" name="room_id" required><option value="">Choose an available room</option></select></div><div><label for="tenant-rent">Monthly rent (TZS)</label><input id="tenant-rent" name="monthly_rent" type="number" min="0.01" step="0.01" placeholder="150000" required></div></div><div class="modal-actions"><button class="button button-outline" type="button" data-close-modal>Cancel</button><button class="button button-dark" type="submit">Save tenant <span>→</span></button></div></form></section></div>
    <div class="modal-backdrop" id="room-modal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="room-modal-title"><button class="modal-close" data-close-modal aria-label="Close">×</button><span class="eyebrow">GROW YOUR PROPERTY</span><h2 id="room-modal-title">Add a room</h2><p class="modal-intro">Add an available room to keep track of your property.</p><form id="room-form"><label for="room-number">Room number or name</label><input id="room-number" name="room_number" maxlength="40" placeholder="e.g. Room 04" required><label for="room-rent">Monthly rent (TZS)</label><input id="room-rent" name="monthly_rent" type="number" min="0" step="0.01" placeholder="150000" required><label for="room-notes">Notes <span class="optional">OPTIONAL</span></label><input id="room-notes" name="notes" maxlength="255" placeholder="e.g. Ground floor, en suite"><div class="modal-actions"><button class="button button-outline" type="button" data-close-modal>Cancel</button><button class="button button-dark" type="submit">Save room <span>→</span></button></div></form></section></div>
    <div class="modal-backdrop" id="payment-modal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title"><button class="modal-close" data-close-modal aria-label="Close">×</button><span class="eyebrow">RENT RECEIVED</span><h2 id="payment-modal-title">Record a payment</h2><p class="modal-intro">Enter the exact amount received and the rent month it covers.</p><form id="payment-form"><label for="payment-tenant">Tenant</label><select id="payment-tenant" name="tenant_id" required><option value="">Choose a tenant</option></select><div class="form-two"><div><label for="payment-amount">Exact amount paid (TZS)</label><input id="payment-amount" name="amount" type="number" min="0.01" step="0.01" placeholder="Enter amount received" required></div><div><label for="payment-date">Payment date</label><input id="payment-date" name="payment_date" type="date" required></div></div><div class="form-two"><div><label for="payment-month">Rent month covered</label><input id="payment-month" name="covered_month" type="month" required></div><div><label for="payment-method">Payment method</label><select id="payment-method" name="payment_method" required><option value="Mobile Money">Mobile Money</option><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Other">Other</option></select></div></div><label for="payment-reference">Transaction reference <span class="optional">OPTIONAL</span></label><input id="payment-reference" name="reference" maxlength="100" placeholder="e.g. M-Pesa transaction ID"><div class="modal-actions"><button class="button button-outline" type="button" data-close-modal>Cancel</button><button class="button button-dark" type="submit">Save payment <span>→</span></button></div></form></section></div>
    <div class="modal-backdrop" id="rent-modal" aria-hidden="true"><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="rent-modal-title"><button class="modal-close" data-close-modal aria-label="Close">×</button><span class="eyebrow">KEEP THE AGREEMENT CURRENT</span><h2 id="rent-modal-title">Update monthly rent</h2><p class="modal-intro">Change the tenant’s current agreed monthly rent. Existing payments remain unchanged.</p><form id="rent-form"><input type="hidden" name="tenant_id"><label for="rent-tenant-label">Tenant</label><input id="rent-tenant-label" type="text" readonly><label for="new-monthly-rent">New monthly rent (TZS)</label><input id="new-monthly-rent" name="monthly_rent" type="number" min="0.01" step="0.01" required><div class="modal-actions"><button class="button button-outline" type="button" data-close-modal>Cancel</button><button class="button button-dark" type="submit">Save new rent <span>→</span></button></div></form></section></div>
    <script src="assets/admin-language.js" defer></script>
    <script src="assets/app.js" defer></script>
</body>
</html>
