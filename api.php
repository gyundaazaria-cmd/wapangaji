<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$tenant = Auth::requireTenant();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = (string) ($_GET['resource'] ?? '');

if ($method !== 'GET' && !verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    json_response(['error' => 'Session token expired. Refresh the page and try again.'], 419);
}

function tenant_request_data(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 2097152) {
        json_response(['error' => 'Request is too large.'], 413);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Request body must be valid JSON.'], 400);
    }
    return $data;
}

function tenant_required_text(array $data, string $key, string $label, int $limit): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value)) {
        json_response(['error' => $label . ' is required.'], 422);
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $limit) {
        json_response(['error' => $label . ' is required and must not exceed ' . $limit . ' characters.'], 422);
    }
    return $value;
}

try {
    $pdo = Database::connection();
    $activeTenant = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND active = 1');
    $activeTenant->execute([$tenant['id']]);
    if ($activeTenant->fetchColumn() === false) {
        Auth::logout();
        json_response(['error' => 'Upangaji wako haupo tena hai. Wasiliana na mwenye nyumba.'], 403);
    }

    if ($method === 'GET' && $resource === 'profile') {
        $statement = $pdo->prepare(
            'SELECT t.full_name, t.phone, r.room_number, a.email, a.photo_mime, a.photo_data
             FROM tenants t
             INNER JOIN tenant_portal_accounts a ON a.tenant_id = t.id
             LEFT JOIN rooms r ON r.id = t.room_id
             WHERE t.id = ? AND t.active = 1'
        );
        $statement->execute([$tenant['id']]);
        $profile = $statement->fetch();
        if ($profile === false) {
            json_response(['error' => 'Tenant profile is unavailable. Contact the property manager.'], 404);
        }
        if ($profile['photo_data'] !== null) {
            $profile['photo_data'] = 'data:' . $profile['photo_mime'] . ';base64,' . base64_encode($profile['photo_data']);
        }
        unset($profile['photo_mime']);
        json_response(['data' => $profile]);
    }

    if ($method === 'GET' && $resource === 'dashboard') {
        $statement = $pdo->prepare(
            'SELECT t.full_name, t.monthly_rent,
                    COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.tenant_id = t.id), 0) AS total_paid,
                    (SELECT COUNT(*) FROM tenant_contracts c WHERE c.tenant_id = t.id) AS contract_count
             FROM tenants t WHERE t.id = ? AND t.active = 1'
        );
        $statement->execute([$tenant['id']]);
        $summary = $statement->fetch();
        if ($summary === false) {
            json_response(['error' => 'Your tenancy is no longer active. Contact the property manager.'], 403);
        }
        $due = new DateTimeImmutable('first day of next month');
        $summary['due_date'] = $due->format('Y-m-d');
        $summary['contract_status'] = (int) $summary['contract_count'] > 0 ? 'Imesainiwa' : 'Inasubiri';
        json_response(['data' => $summary]);
    }

    if ($method === 'GET' && $resource === 'payments') {
        $statement = $pdo->prepare(
            'SELECT payment_date, amount, payment_method, covered_month, reference
             FROM payments WHERE tenant_id = ? ORDER BY payment_date DESC, id DESC'
        );
        $statement->execute([$tenant['id']]);
        $payments = $statement->fetchAll();
        $currentMonth = date('Y-m-01');
        $paid = 0.0;
        foreach ($payments as $payment) {
            if ($payment['covered_month'] === $currentMonth) {
                $paid += (float) $payment['amount'];
            }
        }
        $rentQuery = $pdo->prepare('SELECT monthly_rent FROM tenants WHERE id = ? AND active = 1');
        $rentQuery->execute([$tenant['id']]);
        $rent = $rentQuery->fetchColumn();
        if ($rent !== false && (float) $rent > $paid) {
            array_unshift($payments, [
                'payment_date' => null,
                'amount' => number_format((float) $rent - $paid, 2, '.', ''),
                'payment_method' => null,
                'covered_month' => $currentMonth,
                'reference' => null,
                'status' => 'Pending',
            ]);
        }
        foreach ($payments as &$payment) {
            $payment['status'] = $payment['status'] ?? 'Paid';
        }
        unset($payment);
        json_response(['data' => $payments]);
    }

    if ($method === 'GET' && $resource === 'contract') {
        $statement = $pdo->prepare(
            'SELECT full_name, phone, nida_number, lease_start, lease_duration_months,
                    next_of_kin_name, next_of_kin_phone, monthly_rent, signed_at, signature_data
             FROM tenant_contracts WHERE tenant_id = ? ORDER BY signed_at DESC, id DESC LIMIT 1'
        );
        $statement->execute([$tenant['id']]);
        $contract = $statement->fetch();
        if ($contract !== false) {
            $contract['signature_data'] = (string) $contract['signature_data'];
        }
        json_response(['data' => $contract ?: null]);
    }

    if ($method === 'POST' && $resource === 'profile') {
        $data = tenant_request_data();
        $name = tenant_required_text($data, 'full_name', 'Jina kamili', 160);
        $phone = tenant_required_text($data, 'phone', 'Namba ya simu', 30);
        if (!preg_match('/^\+?[0-9][0-9\s().-]{5,28}[0-9]$/', $phone)) {
            json_response(['error' => 'Ingiza namba sahihi ya simu.'], 422);
        }
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE tenants SET full_name = ?, phone = ? WHERE id = ? AND active = 1');
        $update->execute([$name, $phone, $tenant['id']]);
        if (array_key_exists('photo_data', $data) && $data['photo_data'] !== null && $data['photo_data'] !== '') {
            if (!is_string($data['photo_data']) || strlen($data['photo_data']) > 1500000
                || !preg_match('#^data:(image/(?:jpeg|png|webp));base64,([A-Za-z0-9+/]+={0,2})$#', $data['photo_data'], $matches)) {
                $pdo->rollBack();
                json_response(['error' => 'Picha lazima iwe JPG, PNG au WebP isiyozidi 1 MB.'], 422);
            }
            $image = base64_decode($matches[2], true);
            $imageInfo = $image === false ? false : getimagesizefromstring($image);
            if ($image === false || strlen($image) > 1048576 || $imageInfo === false
                || !in_array($imageInfo['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
                || $imageInfo['mime'] !== $matches[1]) {
                $pdo->rollBack();
                json_response(['error' => 'Faili la picha si halali au limezidi 1 MB.'], 422);
            }
            $photo = $pdo->prepare('UPDATE tenant_portal_accounts SET photo_mime = ?, photo_data = ? WHERE tenant_id = ?');
            $photo->execute([$matches[1], $image, $tenant['id']]);
        }
        $pdo->commit();
        json_response(['data' => ['success' => true]]);
    }

    if ($method === 'POST' && $resource === 'contract') {
        $data = tenant_request_data();
        $name = tenant_required_text($data, 'full_name', 'Jina kamili', 160);
        $phone = tenant_required_text($data, 'phone', 'Namba ya simu', 30);
        $nida = tenant_required_text($data, 'nida_number', 'Namba ya NIDA', 40);
        $start = tenant_required_text($data, 'lease_start', 'Tarehe ya kuanza', 10);
        $kinName = tenant_required_text($data, 'next_of_kin_name', 'Ndugu wa karibu', 160);
        $kinPhone = tenant_required_text($data, 'next_of_kin_phone', 'Simu ya ndugu wa karibu', 30);
        $duration = filter_var($data['lease_duration_months'] ?? null, FILTER_VALIDATE_INT);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        if ($duration === false || $duration < 1 || $duration > 120
            || $date === false || $date->format('Y-m-d') !== $start) {
            json_response(['error' => 'Tarehe au muda wa mkataba si sahihi.'], 422);
        }
        foreach ([$phone, $kinPhone] as $number) {
            if (!preg_match('/^\+?[0-9][0-9\s().-]{5,28}[0-9]$/', $number)) {
                json_response(['error' => 'Ingiza namba sahihi za simu.'], 422);
            }
        }
        $signature = $data['signature_data'] ?? null;
        if (!is_string($signature) || strlen($signature) > 900000) {
            json_response(['error' => 'Sahihi inahitajika. Tafadhali saini kwenye kisanduku.'], 422);
        }
        $strokes = json_decode($signature, true);
        if (!is_array($strokes) || count($strokes) === 0 || count($strokes) > 120
            || !array_is_list($strokes)) {
            json_response(['error' => 'Sahihi haijasomwa vizuri. Tafadhali saini tena.'], 422);
        }
        foreach ($strokes as $stroke) {
            if (!is_array($stroke) || count($stroke) < 2 || count($stroke) > 1500) {
                json_response(['error' => 'Sahihi haijasomwa vizuri. Tafadhali saini tena.'], 422);
            }
            foreach ($stroke as $point) {
                if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0]) || !is_numeric($point[1])
                    || $point[0] < 0 || $point[0] > 1 || $point[1] < 0 || $point[1] > 1) {
                    json_response(['error' => 'Sahihi haijasomwa vizuri. Tafadhali saini tena.'], 422);
                }
            }
        }
        $rentQuery = $pdo->prepare('SELECT monthly_rent FROM tenants WHERE id = ? AND active = 1');
        $rentQuery->execute([$tenant['id']]);
        $rent = $rentQuery->fetchColumn();
        if ($rent === false) {
            json_response(['error' => 'Mkataba haupatikani kwa akaunti hii.'], 403);
        }
        $existingContract = $pdo->prepare('SELECT id FROM tenant_contracts WHERE tenant_id = ? LIMIT 1');
        $existingContract->execute([$tenant['id']]);
        if ($existingContract->fetchColumn() !== false) {
            json_response(['error' => 'Mkataba huu tayari umesainiwa. Wasiliana na mwenye nyumba ikiwa unahitaji marekebisho.'], 409);
        }
        $insert = $pdo->prepare(
            'INSERT INTO tenant_contracts
             (tenant_id, full_name, phone, nida_number, lease_start, lease_duration_months,
              next_of_kin_name, next_of_kin_phone, monthly_rent, signature_data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$tenant['id'], $name, $phone, $nida, $start, $duration, $kinName, $kinPhone, $rent, $signature]);
        json_response(['data' => ['success' => true, 'signed_at' => date('Y-m-d H:i:s')]], 201);
    }

    json_response(['error' => 'Njia hii haipatikani.'], 404);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($exception->getCode() === '23000' && $resource === 'contract') {
        json_response(['error' => 'Mkataba huu tayari umesainiwa. Wasiliana na mwenye nyumba ikiwa unahitaji marekebisho.'], 409);
    }
    error_log('GYUNDA HOUSE tenant database error: ' . $exception->getMessage());
    json_response(['error' => 'Ombi halikukamilika. Jaribu tena baadaye.'], 500);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('GYUNDA HOUSE tenant application error: ' . $exception->getMessage());
    json_response(['error' => 'Ombi halikukamilika. Jaribu tena baadaye.'], 500);
}
