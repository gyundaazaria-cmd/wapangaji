<?php
declare(strict_types=1);
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

Auth::requireUser();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = (string) ($_GET['resource'] ?? '');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if ($method !== 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf($token)) {
        json_response(['error' => 'Your session token is invalid. Refresh the page and try again.'], 419);
    }
}

function request_data(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 1048576) {
        json_response(['error' => 'The request is too large.'], 413);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'The request body must be valid JSON.'], 400);
    }
    return $data;
}

function input_string(mixed $value, string $label): string
{
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        json_response(['error' => $label . ' must be a text value.'], 422);
    }
    return trim((string) $value);
}

function required_text(array $data, string $key, string $label, int $maxLength): string
{
    $value = input_string($data[$key] ?? '', $label);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        json_response(['error' => $label . ' is required and must be no longer than ' . $maxLength . ' characters.'], 422);
    }
    return $value;
}

function valid_money(mixed $value, string $label, bool $allowZero = false): string
{
    $amount = input_string($value, $label);
    if (!preg_match('/^(?:0|[1-9]\d{0,9})(?:\.\d{1,2})?$/', $amount)
        || (!$allowZero && (float) $amount <= 0)) {
        json_response(['error' => $label . ' must be a ' . ($allowZero ? 'non-negative' : 'positive') . ' amount with up to two decimal places.'], 422);
    }
    return $amount;
}

function valid_phone(mixed $value, string $label, bool $optional = false): ?string
{
    $phone = input_string($value ?? '', $label);
    if ($optional && $phone === '') {
        return null;
    }
    if (strlen($phone) > 30 || !preg_match('/^\+?[0-9][0-9\s().-]{5,28}[0-9]$/', $phone)) {
        json_response(['error' => $label . ' must contain 7–30 digits and may include +, spaces, brackets or dashes.'], 422);
    }
    return $phone;
}

function valid_date(mixed $value, string $label): string
{
    $date = input_string($value, $label);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
        json_response(['error' => $label . ' must be a valid date.'], 422);
    }
    return $date;
}

try {
    $pdo = Database::connection();

    if ($method === 'GET' && $resource === 'dashboard') {
        $month = date('Y-m-01');
        $totals = $pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM tenants WHERE active = 1) AS active_tenants,
                (SELECT COUNT(*) FROM rooms) AS total_rooms,
                (SELECT COUNT(*) FROM rooms r WHERE NOT EXISTS (SELECT 1 FROM tenants t WHERE t.room_id = r.id AND t.active = 1)) AS vacant_rooms,
                (SELECT COALESCE(SUM(amount), 0) FROM payments) AS all_time_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE covered_month = ?) AS month_collected"
        );
        $totals->execute([$month]);
        $summary = $totals->fetch();
        $pending = $pdo->prepare(
            'SELECT COALESCE(SUM(GREATEST(t.monthly_rent - COALESCE(p.paid, 0), 0)), 0)
             FROM tenants t
             LEFT JOIN (
                 SELECT tenant_id, SUM(amount) AS paid
                 FROM payments WHERE covered_month = ? GROUP BY tenant_id
             ) p ON p.tenant_id = t.id
             WHERE t.active = 1'
        );
        $pending->execute([$month]);
        $summary['pending_amount'] = $pending->fetchColumn();
        $summary['month'] = $month;
        json_response(['data' => $summary]);
    }

    if ($method === 'GET' && $resource === 'tenants') {
        $statement = $pdo->query(
            'SELECT t.id, t.full_name, t.phone, t.alternate_phone, t.monthly_rent, t.created_at,
                    r.id AS room_id, r.room_number,
                    COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.tenant_id = t.id AND p.covered_month = DATE_FORMAT(CURDATE(), \'%Y-%m-01\')), 0) AS paid_this_month
             FROM tenants t LEFT JOIN rooms r ON r.id = t.room_id
             WHERE t.active = 1 ORDER BY t.full_name'
        );
        json_response(['data' => $statement->fetchAll()]);
    }

    if ($method === 'POST' && $resource === 'tenant-invites') {
        $data = request_data();
        $tenantValue = $data['tenant_id'] ?? null;
        $tenantId = is_string($tenantValue) || is_int($tenantValue) ? filter_var($tenantValue, FILTER_VALIDATE_INT) : false;
        if ($tenantId === false || $tenantId === null || $tenantId < 1) {
            json_response(['error' => 'Choose a valid active tenant.'], 422);
        }
        $invite = Auth::issueTenantInvite($tenantId, Auth::requireUser()['id']);
        if ($invite['status'] === 'missing') {
            json_response(['error' => 'This active tenant could not be found.'], 404);
        }
        if ($invite['status'] === 'registered') {
            json_response(['error' => 'This tenant already has a portal account.'], 409);
        }
        json_response(['data' => ['invite_code' => $invite['invite_code']]], 201);
    }

    if ($method === 'GET' && $resource === 'rooms') {
        $statement = $pdo->query(
            'SELECT r.id, r.room_number, r.monthly_rent, r.notes, t.id AS tenant_id, t.full_name AS tenant_name
             FROM rooms r
             LEFT JOIN tenants t ON t.room_id = r.id AND t.active = 1
             ORDER BY CAST(r.room_number AS UNSIGNED), r.room_number'
        );
        json_response(['data' => $statement->fetchAll()]);
    }

    if ($method === 'GET' && $resource === 'payments') {
        $statement = $pdo->query(
            'SELECT p.id, p.amount, p.payment_date, p.payment_method, p.covered_month, p.reference,
                    t.id AS tenant_id, t.full_name AS tenant_name, r.room_number
             FROM payments p
             INNER JOIN tenants t ON t.id = p.tenant_id
             LEFT JOIN rooms r ON r.id = t.room_id
             ORDER BY p.payment_date DESC, p.id DESC'
        );
        json_response(['data' => $statement->fetchAll()]);
    }

    if ($method === 'POST' && $resource === 'rooms') {
        $data = request_data();
        $roomNumber = required_text($data, 'room_number', 'Room number', 40);
        $monthlyRent = valid_money($data['monthly_rent'] ?? null, 'Monthly rent', true);
        $notes = input_string($data['notes'] ?? '', 'Room notes');
        if (mb_strlen($notes) > 255) {
            json_response(['error' => 'Room notes must not exceed 255 characters.'], 422);
        }
        $statement = $pdo->prepare('INSERT INTO rooms (room_number, monthly_rent, notes) VALUES (?, ?, ?)');
        $statement->execute([$roomNumber, $monthlyRent, $notes === '' ? null : $notes]);
        json_response(['data' => ['id' => (int) $pdo->lastInsertId()]], 201);
    }

    if ($method === 'POST' && $resource === 'tenants' && $id !== false && $id !== null && ($_GET['action'] ?? '') === 'vacate') {
        $update = $pdo->prepare('UPDATE tenants SET active = 0, room_id = NULL WHERE id = ? AND active = 1');
        $update->execute([$id]);
        if ($update->rowCount() === 0) {
            json_response(['error' => 'This tenant could not be found or has already moved out.'], 404);
        }
        json_response(['data' => ['success' => true]]);
    }

    if ($method === 'POST' && $resource === 'tenants') {
        $data = request_data();
        $fullName = required_text($data, 'full_name', 'Full name', 160);
        $phone = valid_phone($data['phone'] ?? '', 'Phone number');
        $alternatePhone = valid_phone($data['alternate_phone'] ?? '', 'Alternative phone', true);
        $monthlyRent = valid_money($data['monthly_rent'] ?? null, 'Monthly rent');
        $roomValue = $data['room_id'] ?? null;
        $roomId = is_string($roomValue) || is_int($roomValue) ? filter_var($roomValue, FILTER_VALIDATE_INT) : false;
        if ($roomId === false || $roomId === null || $roomId < 1) {
            json_response(['error' => 'Choose a valid room.'], 422);
        }

        $pdo->beginTransaction();
        $lockRoom = $pdo->prepare('SELECT id FROM rooms WHERE id = ? FOR UPDATE');
        $lockRoom->execute([$roomId]);
        if ($lockRoom->fetchColumn() === false) {
            $pdo->rollBack();
            json_response(['error' => 'That room no longer exists. Refresh the page and choose another.'], 409);
        }
        $occupancy = $pdo->prepare('SELECT id FROM tenants WHERE room_id = ? AND active = 1 LIMIT 1');
        $occupancy->execute([$roomId]);
        if ($occupancy->fetchColumn() !== false) {
            $pdo->rollBack();
            json_response(['error' => 'That room has already been assigned. Choose another available room.'], 409);
        }
        $insert = $pdo->prepare('INSERT INTO tenants (full_name, phone, alternate_phone, room_id, monthly_rent) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$fullName, $phone, $alternatePhone, $roomId, $monthlyRent]);
        $tenantId = (int) $pdo->lastInsertId();
        $pdo->commit();
        json_response(['data' => ['id' => $tenantId]], 201);
    }

    if ($method === 'PATCH' && $resource === 'tenants' && $id !== false && $id !== null) {
        $data = request_data();
        $monthlyRent = valid_money($data['monthly_rent'] ?? null, 'Monthly rent');
        $update = $pdo->prepare('UPDATE tenants SET monthly_rent = ? WHERE id = ? AND active = 1');
        $update->execute([$monthlyRent, $id]);
        if ($update->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND active = 1');
            $exists->execute([$id]);
            if ($exists->fetchColumn() === false) {
                json_response(['error' => 'This tenant could not be found or has already moved out.'], 404);
            }
        }
        json_response(['data' => ['success' => true]]);
    }

    if ($method === 'POST' && $resource === 'payments') {
        $data = request_data();
        $tenantValue = $data['tenant_id'] ?? null;
        $tenantId = is_string($tenantValue) || is_int($tenantValue) ? filter_var($tenantValue, FILTER_VALIDATE_INT) : false;
        if ($tenantId === false || $tenantId === null || $tenantId < 1) {
            json_response(['error' => 'Choose a valid tenant.'], 422);
        }
        $amount = valid_money($data['amount'] ?? null, 'Amount paid');
        $paymentDate = valid_date($data['payment_date'] ?? '', 'Payment date');
        $monthInput = input_string($data['covered_month'] ?? '', 'Rent month');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthInput)) {
            json_response(['error' => 'Choose a valid rent month.'], 422);
        }
        $methodValue = input_string($data['payment_method'] ?? '', 'Payment method');
        if (!in_array($methodValue, ['Cash', 'Mobile Money', 'Bank Transfer', 'Other'], true)) {
            json_response(['error' => 'Choose a valid payment method.'], 422);
        }
        $reference = input_string($data['reference'] ?? '', 'Transaction reference');
        if (mb_strlen($reference) > 100) {
            json_response(['error' => 'The transaction reference must not exceed 100 characters.'], 422);
        }
        $tenant = $pdo->prepare('SELECT id FROM tenants WHERE id = ? AND active = 1');
        $tenant->execute([$tenantId]);
        if ($tenant->fetchColumn() === false) {
            json_response(['error' => 'Choose a tenant who is currently active.'], 422);
        }
        $insert = $pdo->prepare(
            'INSERT INTO payments (tenant_id, amount, payment_date, payment_method, covered_month, reference)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$tenantId, $amount, $paymentDate, $methodValue, $monthInput . '-01', $reference === '' ? null : $reference]);
        json_response(['data' => ['id' => (int) $pdo->lastInsertId()]], 201);
    }

    if ($method === 'DELETE' && $resource === 'rooms' && $id !== false && $id !== null) {
        $delete = $pdo->prepare(
            'DELETE FROM rooms WHERE id = ?
             AND NOT EXISTS (SELECT 1 FROM tenants WHERE tenants.room_id = rooms.id AND tenants.active = 1)'
        );
        $delete->execute([$id]);
        if ($delete->rowCount() === 0) {
            json_response(['error' => 'This room is occupied or could not be found. Move out its tenant before removing it.'], 409);
        }
        json_response(['data' => ['success' => true]]);
    }

    json_response(['error' => 'The requested API route was not found.'], 404);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($exception->getCode() === '23000') {
        json_response(['error' => 'That room number is already in use, or this change conflicts with existing records.'], 409);
    }
    error_log('GYUNDA HOUSE database error: ' . $exception->getMessage());
    json_response(['error' => 'The request could not be completed. Please try again.'], 500);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('GYUNDA HOUSE application error: ' . $exception->getMessage());
    json_response(['error' => 'The request could not be completed. Please try again.'], 500);
}
