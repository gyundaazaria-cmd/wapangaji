<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8f4">
    <meta name="description" content="See how GYUNDA HOUSE helps you manage rental rooms, tenants, and exact rent payments in one simple workspace.">
    <title>System overview · GYUNDA HOUSE</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="overview-body">
    <header class="overview-nav">
        <a class="brand" href="index.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a>
        <a class="button button-dark" href="index.php">Go to sign in <span aria-hidden="true">→</span></a>
    </header>
    <main>
        <section class="overview-hero">
            <div class="overview-hero-copy">
                <span class="eyebrow"><span class="live-dot"></span> YOUR PROPERTY, A LITTLE MORE ORGANIZED</span>
                <h1>Meet your new<br>property <em>workspace.</em></h1>
                <p>A quick look at how GYUNDA HOUSE brings rooms, tenants, and rent records together — without making property management complicated.</p>
                <a class="overview-primary" href="#the-tour"><span class="overview-play overview-play-large" aria-hidden="true">▶</span><span><strong>Start the quick tour</strong><small>About 1 minute to read</small></span><span class="overview-link-arrow" aria-hidden="true">↓</span></a>
            </div>
            <div class="overview-preview" aria-label="Illustration of the GYUNDA HOUSE dashboard">
                <div class="preview-window">
                    <div class="preview-topbar"><span class="preview-mini-brand"><i>G</i> GYUNDA HOUSE</span><span class="preview-manager"><i>M</i> Manager</span></div>
                    <div class="preview-content">
                        <div class="preview-caption">YOUR PROPERTY AT A GLANCE</div>
                        <div class="preview-title">Good morning, manager <span>✳</span></div>
                        <div class="preview-subtitle">Here’s what’s happening at your property today.</div>
                        <div class="preview-stats">
                            <div><i>♙</i><small>TENANTS</small><strong>12</strong><span>Currently staying</span></div>
                            <div><i>↗</i><small>COLLECTED</small><strong>1.8m</strong><span>Rent received</span></div>
                            <div><i>⌂</i><small>VACANT</small><strong>3</strong><span>Ready to rent</span></div>
                        </div>
                        <div class="preview-lower">
                            <div class="preview-payments"><strong>Recent payments</strong><span><i>AM</i> Amina Mwinyi <b>TZS 150,000</b></span><span><i>JM</i> Juma Mwakalinga <b>TZS 120,000</b></span><span><i>RN</i> Rehema Nuru <b>TZS 180,000</b></span></div>
                            <div class="preview-occupancy"><strong>Rooms at a glance</strong><div class="preview-donut"><span>80%<small>OCCUPIED</small></span></div><small>24 rooms · 3 available</small></div>
                        </div>
                    </div>
                    <span class="preview-float preview-float-payment">✓ &nbsp; Payment saved</span>
                    <span class="preview-float preview-float-room">⌂ &nbsp; Room 04 · Available</span>
                </div>
                <span class="preview-decoration preview-decoration-one"></span><span class="preview-decoration preview-decoration-two"></span>
            </div>
        </section>
        <section class="tour-section" id="the-tour">
            <div class="tour-heading"><span class="eyebrow">THREE SIMPLE STEPS</span><h2>Less chasing. More clarity.</h2><p>Everything you need to keep the day-to-day running smoothly.</p></div>
            <div class="tour-grid">
                <article class="tour-card">
                    <div class="tour-number">01 <span>YOUR PROPERTY</span></div>
                    <div class="tour-illustration tour-rooms"><div class="mini-room-card"><span>⌂</span><small>ROOM 01</small><strong>Occupied</strong><i class="mini-status"></i></div><div class="mini-room-card"><span>⌂</span><small>ROOM 02</small><strong>Available</strong><i class="mini-status mini-status-empty"></i></div><div class="mini-room-card"><span>⌂</span><small>ROOM 03</small><strong>Occupied</strong><i class="mini-status"></i></div></div>
                    <h3>Know your rooms</h3><p>Add your rooms once. At a glance, see which are occupied, which are available, and the monthly rent for each.</p>
                </article>
                <article class="tour-card">
                    <div class="tour-number">02 <span>YOUR TENANTS</span></div>
                    <div class="tour-illustration tour-tenants"><div class="mini-tenant-row"><i>AM</i><span><strong>Amina Mwinyi</strong><small>Room 01 · +255 700 123 456</small></span><b>↗</b></div><div class="mini-tenant-row"><i class="tenant-avatar-blue">JM</i><span><strong>Juma Mwakalinga</strong><small>Room 03 · +255 712 345 678</small></span><b>↗</b></div><span class="mini-add-tenant">＋ &nbsp; Add a tenant</span></div>
                    <h3>Keep people details handy</h3><p>Register tenants with their phone numbers, assign a room, and update their agreed monthly rent whenever it changes.</p>
                </article>
                <article class="tour-card">
                    <div class="tour-number">03 <span>YOUR RENT</span></div>
                    <div class="tour-illustration tour-payment"><div class="payment-receipt"><span class="receipt-check">✓</span><span><small>PAYMENT RECORDED</small><strong>TZS 150,000</strong><i>Mobile Money · Oct 2026</i></span></div><div class="payment-receipt receipt-muted"><span class="receipt-check receipt-check-muted">✓</span><span><small>PAYMENT RECORDED</small><strong>TZS 75,000</strong><i>Cash · Oct 2026</i></span></div><div class="payment-exact">Partial payments? Record the exact amount received.</div></div>
                    <h3>Track exactly what came in</h3><p>Log full or partial payments with the actual amount, payment date, method, and rent month — and keep a lasting history.</p>
                </article>
            </div>
        </section>
        <section class="overview-closing"><span class="closing-spark">✳</span><span class="eyebrow">THAT’S THE WHOLE IDEA</span><h2>A clearer picture of your property,<br>without the paperwork pile.</h2><p>Sign in to add your rooms, register your tenants, and get started.</p><a class="button button-dark" href="index.php">Back to sign in <span aria-hidden="true">→</span></a></section>
    </main>
    <footer class="overview-footer"><a class="brand" href="index.php"><span class="brand-mark">G</span><span>GYUNDA <b>HOUSE</b></span></a><span>PROPERTY MANAGEMENT, MADE SIMPLE</span><a href="index.php">Manager sign in <span aria-hidden="true">→</span></a></footer>
    <script src="assets/overview.js" defer></script>
</body>
</html>
