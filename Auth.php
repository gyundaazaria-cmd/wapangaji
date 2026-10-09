<?php
declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return ($_SESSION['user_role'] ?? 'admin') === 'admin'
            && isset($_SESSION['user_id'], $_SESSION['username'])
            ? ['id' => (int) $_SESSION['user_id'], 'username' => (string) $_SESSION['username']]
            : null;
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if ($user === null) {
            json_response(['error' => 'Your session has expired. Please sign in again.'], 401);
        }
        return $user;
    }

    public static function authenticate(string $username, string $password): bool
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT id, username, password_hash, failed_login_attempts, locked_until FROM users WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        $user = $statement->fetch();

        if ($user === false) {
            password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            return false;
        }
        if ($user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time()) {
            return false;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
            $update = $pdo->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?');
            $update->execute([$attempts >= 5 ? 0 : $attempts, $lockedUntil, $user['id']]);
            return false;
        }

        $update = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $update->execute([$user['id']]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['user_role'] = 'admin';
        unset($_SESSION['tenant_id']);
        unset($_SESSION['csrf_token']);
        csrf_token();
        return true;
    }

    public static function tenant(): ?array
    {
        return ($_SESSION['user_role'] ?? null) === 'tenant'
            && isset($_SESSION['tenant_id'], $_SESSION['username'])
            ? ['id' => (int) $_SESSION['tenant_id'], 'username' => (string) $_SESSION['username']]
            : null;
    }

    public static function requireTenant(): array
    {
        $tenant = self::tenant();
        if ($tenant === null) {
            json_response(['error' => 'Your session has expired. Please sign in again.'], 401);
        }
        return $tenant;
    }

    public static function authenticateTenant(string $login, string $password): bool
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT a.tenant_id, a.username, a.password_hash, a.failed_login_attempts, a.locked_until
             FROM tenant_portal_accounts a
             INNER JOIN tenants t ON t.id = a.tenant_id
             WHERE (a.username = ? OR a.email = ?) AND t.active = 1
             LIMIT 1'
        );
        $statement->execute([$login, $login]);
        $account = $statement->fetch();

        if ($account === false) {
            password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            return false;
        }
        if ($account['locked_until'] !== null && strtotime((string) $account['locked_until']) > time()) {
            return false;
        }
        if (!password_verify($password, (string) $account['password_hash'])) {
            $attempts = (int) $account['failed_login_attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
            $update = $pdo->prepare('UPDATE tenant_portal_accounts SET failed_login_attempts = ?, locked_until = ? WHERE tenant_id = ?');
            $update->execute([$attempts >= 5 ? 0 : $attempts, $lockedUntil, $account['tenant_id']]);
            return false;
        }

        $update = $pdo->prepare('UPDATE tenant_portal_accounts SET failed_login_attempts = 0, locked_until = NULL WHERE tenant_id = ?');
        $update->execute([$account['tenant_id']]);
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['username'] = (string) $account['username'];
        $_SESSION['tenant_id'] = (int) $account['tenant_id'];
        $_SESSION['user_role'] = 'tenant';
        unset($_SESSION['csrf_token']);
        csrf_token();
        return true;
    }

    public static function registerTenant(
        string $inviteCode,
        string $username,
        string $email,
        string $password
    ): string {
        $normalizedCode = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $inviteCode));
        if (!preg_match('/^GH[A-F0-9]{32}$/', $normalizedCode)) {
            return 'invalid';
        }

        $pdo = Database::connection();
        $tokenHash = hash('sha256', $normalizedCode, true);
        $pdo->beginTransaction();

        try {
            $findInvite = $pdo->prepare(
                'SELECT i.tenant_id
                 FROM tenant_portal_invites i
                 INNER JOIN tenants t ON t.id = i.tenant_id
                 WHERE i.token_hash = ? AND i.used_at IS NULL AND t.active = 1
                 FOR UPDATE'
            );
            $findInvite->execute([$tokenHash]);
            $invite = $findInvite->fetch();
            if ($invite === false) {
                $pdo->rollBack();
                return 'invalid';
            }

            $accountExists = $pdo->prepare('SELECT id FROM tenant_portal_accounts WHERE tenant_id = ?');
            $accountExists->execute([$invite['tenant_id']]);
            if ($accountExists->fetchColumn() !== false) {
                $pdo->rollBack();
                return 'registered';
            }

            $insert = $pdo->prepare(
                'INSERT INTO tenant_portal_accounts (tenant_id, username, email, password_hash) VALUES (?, ?, ?, ?)'
            );
            $insert->execute([
                $invite['tenant_id'],
                $username,
                strtolower($email),
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $markUsed = $pdo->prepare('UPDATE tenant_portal_invites SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL');
            $markUsed->execute([$tokenHash]);
            if ($markUsed->rowCount() !== 1) {
                throw new RuntimeException('The tenant invitation could not be marked as used.');
            }
            $pdo->commit();
            return 'created:' . (int) $invite['tenant_id'];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function issueTenantInvite(int $tenantId, int $managerId): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $tenant = $pdo->prepare('SELECT t.id FROM tenants t WHERE t.id = ? AND t.active = 1 FOR UPDATE');
            $tenant->execute([$tenantId]);
            if ($tenant->fetchColumn() === false) {
                $pdo->rollBack();
                return ['status' => 'missing'];
            }

            $account = $pdo->prepare('SELECT id FROM tenant_portal_accounts WHERE tenant_id = ?');
            $account->execute([$tenantId]);
            if ($account->fetchColumn() !== false) {
                $pdo->rollBack();
                return ['status' => 'registered'];
            }

            $token = 'GH' . strtoupper(bin2hex(random_bytes(16)));
            $tokenHash = hash('sha256', $token, true);
            $insert = $pdo->prepare(
                'INSERT INTO tenant_portal_invites (token_hash, tenant_id, issued_by)
                 VALUES (?, ?, ?)'
            );
            $insert->execute([$tokenHash, $tenantId, $managerId]);
            $pdo->commit();

            return ['status' => 'created', 'invite_code' => $token];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function registerFirstAdmin(string $username, string $password): string
    {
        $pdo = Database::connection();
        $lockName = 'gyunda_initial_admin_setup';
        $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            return 'busy';
        }

        try {
            if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
                return 'exists';
            }

            $insert = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            return 'created';
        } finally {
            $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}
