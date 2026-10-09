<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the sign-out button to end your session.');
}

$csrf = $_POST['csrf_token'] ?? null;
if (!verify_csrf(is_string($csrf) ? $csrf : null)) {
    if (Auth::user() === null) {
        Auth::logout();
        header('Location: index.php');
        exit;
    }

    http_response_code(419);
    exit('Your sign-out request expired. Refresh the page and try again.');
}

Auth::logout();
header('Location: index.php');
exit;
