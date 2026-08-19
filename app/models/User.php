<?php
declare(strict_types=1);

final class User
{
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT u.*, p.first_name, p.last_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id',
            ['id' => $id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = :e', ['e' => $email]);
    }

    /**
     * @param array<int,int>|null $centreIds null = global scope (no filter).
     *   Scoped (§42 / rbac.md §5 — identity.user.view_any is ◐ for centre_manager):
     *   only users with a role grant or a staff posting at one of these centres.
     * @return array<int,array<string,mixed>>
     */
    public static function allWithRoles(?array $centreIds = null): array
    {
        $sql = 'SELECT DISTINCT u.id, u.email, u.status, u.created_at, p.first_name, p.last_name
                FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id';
        $params = [];
        if ($centreIds !== null) {
            if (empty($centreIds)) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " WHERE u.id IN (
                        SELECT user_id FROM user_roles WHERE centre_id IN ($ph)
                        UNION
                        SELECT user_id FROM staff_centres WHERE centre_id IN ($ph)
                      )";
            $params = array_merge(array_values($centreIds), array_values($centreIds));
        }
        $sql .= ' ORDER BY u.created_at DESC';
        $users = Database::query($sql, $params)->fetchAll();
        foreach ($users as &$u) {
            $u['roles'] = Database::all(
                'SELECT r.code, r.name, ur.centre_id, c.name AS centre_name
                 FROM user_roles ur JOIN roles r ON r.id = ur.role_id
                 LEFT JOIN centres c ON c.id = ur.centre_id
                 WHERE ur.user_id = :id',
                ['id' => $u['id']]
            );
        }
        return $users;
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM users')['c'];
    }

    /** Creates the user + profile + one role grant. Returns the new user id. */
    public static function create(string $email, string $password, string $first, string $last, int $roleId, ?int $centreId): int
    {
        Database::query(
            "INSERT INTO users (email, password_hash, status, email_verified_at) VALUES (:e,:h,'active',NOW())",
            ['e' => $email, 'h' => password_hash($password, PASSWORD_DEFAULT)]
        );
        $id = Database::lastInsertId();
        Database::query('INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)', ['u' => $id, 'f' => $first, 'l' => $last]);
        Database::query('INSERT INTO user_roles (user_id, role_id, centre_id, granted_by) VALUES (:u,:r,:c,:by)', [
            'u' => $id, 'r' => $roleId, 'c' => $centreId, 'by' => Auth::id(),
        ]);
        return $id;
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE users SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }
}
