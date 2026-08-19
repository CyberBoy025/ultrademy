<?php
declare(strict_types=1);

/**
 * Session-based auth + the permission/scope model from
 * docs/architecture/03-rbac.md §4: a permission grant is either GLOBAL
 * (centre_id IS NULL on the role assignment) or restricted to specific
 * centres. super_admin short-circuits everything — the only shortcut
 * in the system (§7 of that doc).
 */
final class Auth
{
    /** @var array<string,mixed>|null|false false = "checked, no user" */
    private static array|null|false $user = false;
    /** @var array<int,array<string,mixed>>|null */
    private static ?array $roleRows = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE email = :email', ['email' => $email]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if ($user['status'] !== 'active') {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);
        self::$user = false;
        self::$roleRows = null;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = false;
        self::$roleRows = null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== false) {
            return self::$user;
        }
        $id = self::id();
        if ($id === null) {
            return self::$user = null;
        }
        self::$user = Database::one(
            'SELECT u.id, u.email, u.phone, u.status, u.email_verified_at, u.last_login_at,
                    p.first_name, p.last_name, p.photo_path, p.completion_pct
             FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE u.id = :id',
            ['id' => $id]
        );
        return self::$user;
    }

    public static function name(): string
    {
        $u = self::user();
        if (!$u) {
            return '';
        }
        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        return $name !== '' ? $name : $u['email'];
    }

    public static function initials(): string
    {
        $u = self::user();
        if (!$u) {
            return '?';
        }
        $f = mb_substr((string) ($u['first_name'] ?? ''), 0, 1);
        $l = mb_substr((string) ($u['last_name'] ?? ''), 0, 1);
        $out = strtoupper($f . $l);
        return $out !== '' ? $out : strtoupper(mb_substr($u['email'], 0, 2));
    }

    /** @return array<int,array<string,mixed>> rows: code, name, is_scopable, centre_id */
    private static function roleRows(): array
    {
        if (self::$roleRows !== null) {
            return self::$roleRows;
        }
        $id = self::id();
        if ($id === null) {
            return self::$roleRows = [];
        }
        return self::$roleRows = Database::all(
            'SELECT r.code, r.name, r.is_scopable, ur.centre_id
             FROM user_roles ur JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :id',
            ['id' => $id]
        );
    }

    /** @return array<int,string> unique role codes held, ignoring scope */
    public static function roles(): array
    {
        return array_values(array_unique(array_column(self::roleRows(), 'code')));
    }

    public static function hasRole(string $code): bool
    {
        return in_array($code, self::roles(), true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::hasRole('super_admin');
    }

    /** Roles that are relationships to UltrAdemy rather than jobs within it. */
    public const NON_STAFF_ROLES = ['student', 'applicant', 'affiliate'];

    /**
     * True if the user holds any role that is not purely a customer relationship.
     *
     * This is the line between "listings are centre-scoped" and "listings are
     * ownership-scoped" (03-rbac.md §5: `◐` vs `○`). A user with no roles at all is
     * NOT staff — a bare registration must not inherit staff visibility.
     */
    public static function isStaff(?int $userId = null): bool
    {
        $userId ??= self::id();
        if ($userId === null) {
            return false;
        }
        $ph = implode(',', array_fill(0, count(self::NON_STAFF_ROLES), '?'));
        return Database::query(
            "SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ? AND r.code NOT IN ($ph) LIMIT 1",
            array_merge([$userId], self::NON_STAFF_ROLES)
        )->fetch() !== false;
    }

    /** True if any held role grants this permission at all (no scope filtering — see scopeCentres()). */
    public static function can(string $permission): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        $id = self::id();
        if ($id === null) {
            return false;
        }
        return Database::one(
            'SELECT 1 FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :id AND p.code = :perm LIMIT 1',
            ['id' => $id, 'perm' => $permission]
        ) !== null;
    }

    /**
     * Centre scope for a permission.
     * null = GLOBAL (no WHERE centre_id filter needed)
     * []   = held by no role, or every grant is scoped to zero centres — caller should show nothing
     * [ids]= restrict the query to these centre ids
     */
    public static function scopeCentres(string $permission): ?array
    {
        if (self::isSuperAdmin()) {
            return null;
        }
        $id = self::id();
        if ($id === null) {
            return [];
        }
        $rows = Database::all(
            'SELECT ur.centre_id FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE ur.user_id = :id AND p.code = :perm',
            ['id' => $id, 'perm' => $permission]
        );
        if (empty($rows)) {
            return [];
        }
        $ids = [];
        foreach ($rows as $r) {
            if ($r['centre_id'] === null) {
                return null; // any global grant among the user's roles wins
            }
            $ids[] = (int) $r['centre_id'];
        }
        return array_values(array_unique($ids));
    }

    /** Every centre this user is scoped to via ANY role — for nav/"my centres" display, not authorization. */
    public static function centreIds(): array
    {
        $ids = [];
        foreach (self::roleRows() as $r) {
            if ($r['centre_id'] !== null) {
                $ids[] = (int) $r['centre_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Decision 11 default: "May instructors see student contact details? No — names and
     * progress." Anyone whose only staff role is `instructor` gets the redacted view.
     *
     * Lives here rather than in a controller because two very different screens depend on
     * it — the student roster and the attendance sheet — and they must not drift apart.
     */
    public static function maySeeContactDetails(): bool
    {
        if (self::isSuperAdmin()) {
            return true;
        }
        $staffRoles = array_values(array_diff(self::roles(), self::NON_STAFF_ROLES));
        if ($staffRoles === ['instructor']) {
            return false;
        }
        return self::isStaff();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . app_url('login.php'));
            exit;
        }
    }

    public static function requirePermission(string $permission): void
    {
        if (!self::can($permission)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }
}
