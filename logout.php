<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    exit('Ombi si halali. Tafadhali rudi nyuma na ujaribu tena.');
}
Auth::logout();
header('Location: loginpage.php');
exit;
