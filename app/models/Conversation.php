<?php
declare(strict_types=1);

/** Direct chat, groups, and auto-created cohort groups (README §24). */
final class Conversation
{
    public const SUBDIR = 'attachments';
    private const MAX_ATTACHMENT = 10 * 1024 * 1024;

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT c.*, co.name AS cohort_name FROM conversations c
             LEFT JOIN cohorts co ON co.id = c.cohort_id WHERE c.id = :id',
            ['id' => $id]
        );
    }

    /** Conversations the user is currently in, most recently active first. */
    public static function forUser(int $userId): array
    {
        return Database::all(
            "SELECT c.*, p.last_read_at, p.role AS my_role, p.muted_until,
                    (SELECT COUNT(*) FROM messages m
                       WHERE m.conversation_id = c.id AND m.deleted_at IS NULL
                         AND (p.last_read_at IS NULL OR m.created_at > p.last_read_at)
                         AND m.sender_id <> :u2) AS unread,
                    (SELECT m2.body FROM messages m2
                       WHERE m2.conversation_id = c.id AND m2.deleted_at IS NULL
                       ORDER BY m2.created_at DESC LIMIT 1) AS last_body,
                    (SELECT m3.created_at FROM messages m3
                       WHERE m3.conversation_id = c.id ORDER BY m3.created_at DESC LIMIT 1) AS last_at
             FROM conversation_participants p
             JOIN conversations c ON c.id = p.conversation_id
             WHERE p.user_id = :u AND p.left_at IS NULL AND c.is_archived = 0
             ORDER BY last_at IS NULL, last_at DESC",
            ['u' => $userId, 'u2' => $userId]
        );
    }

    public static function participants(int $conversationId): array
    {
        return Database::all(
            "SELECT p.*, u.email, CONCAT(pr.first_name,' ',pr.last_name) AS name
             FROM conversation_participants p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id
             WHERE p.conversation_id = :c AND p.left_at IS NULL
             ORDER BY p.role DESC, pr.first_name",
            ['c' => $conversationId]
        );
    }

    public static function isParticipant(int $conversationId, int $userId): bool
    {
        return Database::one(
            'SELECT 1 FROM conversation_participants WHERE conversation_id = :c AND user_id = :u AND left_at IS NULL',
            ['c' => $conversationId, 'u' => $userId]
        ) !== null;
    }

    /** The other person, for naming a direct thread. */
    public static function counterpart(int $conversationId, int $userId): ?array
    {
        return Database::one(
            "SELECT u.id, u.email, CONCAT(pr.first_name,' ',pr.last_name) AS name
             FROM conversation_participants p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id
             WHERE p.conversation_id = :c AND p.user_id <> :u LIMIT 1",
            ['c' => $conversationId, 'u' => $userId]
        );
    }

    /** How many group conversations this user is in — what `chat_groups` meters. */
    public static function groupCountFor(int $userId): int
    {
        return (int) Database::one(
            "SELECT COUNT(*) c FROM conversation_participants p
             JOIN conversations cv ON cv.id = p.conversation_id
             WHERE p.user_id = :u AND p.left_at IS NULL AND cv.type <> 'direct'",
            ['u' => $userId]
        )['c'];
    }

    /**
     * Finds or creates the one-to-one thread between two people.
     * `direct_key` is the sorted pair, so clicking "message" twice cannot fork the thread.
     */
    public static function findOrCreateDirect(int $userA, int $userB): int
    {
        $pair = [$userA, $userB];
        sort($pair);
        $key = 'd:' . $pair[0] . ':' . $pair[1];

        $existing = Database::one('SELECT id FROM conversations WHERE direct_key = :k', ['k' => $key]);
        if ($existing) {
            // Re-admit anyone who previously left.
            Database::query(
                'UPDATE conversation_participants SET left_at = NULL WHERE conversation_id = :c AND user_id IN (:a,:b)',
                ['c' => $existing['id'], 'a' => $userA, 'b' => $userB]
            );
            return (int) $existing['id'];
        }

        return Database::transaction(static function () use ($key, $userA, $userB): int {
            Database::query(
                "INSERT INTO conversations (type, direct_key, created_by) VALUES ('direct',:k,:by)",
                ['k' => $key, 'by' => Auth::id()]
            );
            $id = Database::lastInsertId();
            foreach ([$userA, $userB] as $uid) {
                Database::query(
                    'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (:c,:u)',
                    ['c' => $id, 'u' => $uid]
                );
            }
            return $id;
        });
    }

    /** @param array<int,int> $memberIds */
    public static function createGroup(string $title, array $memberIds, ?int $cohortId = null, string $type = 'group'): int
    {
        return Database::transaction(static function () use ($title, $memberIds, $cohortId, $type): int {
            Database::query(
                'INSERT INTO conversations (type, title, cohort_id, created_by, is_moderated)
                 VALUES (:t,:title,:cohort,:by,1)',
                ['t' => $type, 'title' => $title, 'cohort' => $cohortId, 'by' => Auth::id()]
            );
            $id = Database::lastInsertId();

            $creator = Auth::id();
            if ($creator !== null) {
                Database::query(
                    "INSERT INTO conversation_participants (conversation_id, user_id, role) VALUES (:c,:u,'moderator')",
                    ['c' => $id, 'u' => $creator]
                );
            }
            foreach (array_unique($memberIds) as $uid) {
                if ((int) $uid === (int) $creator) {
                    continue;
                }
                Database::query(
                    'INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (:c,:u)',
                    ['c' => $id, 'u' => (int) $uid]
                );
            }
            return $id;
        });
    }

    /**
     * The cohort group from README §24 ("programme → cohort → members"), created on
     * demand and kept in step with who is enrolled.
     */
    public static function ensureCohortGroup(int $cohortId): int
    {
        $cohort = Cohort::find($cohortId);
        if (!$cohort) {
            throw new RuntimeException("Cohort $cohortId not found");
        }
        $existing = Database::one('SELECT id FROM conversations WHERE cohort_id = :c', ['c' => $cohortId]);

        $memberIds = array_map('intval', array_column(
            Database::all("SELECT DISTINCT user_id FROM enrolments WHERE cohort_id = :c AND status IN ('active','pending_payment','completed')", ['c' => $cohortId]),
            'user_id'
        ));
        // Instructors of the cohort's class groups belong in it too.
        foreach (Database::all('SELECT DISTINCT instructor_user_id FROM class_groups WHERE cohort_id = :c AND instructor_user_id IS NOT NULL', ['c' => $cohortId]) as $r) {
            $memberIds[] = (int) $r['instructor_user_id'];
        }

        if ($existing) {
            $id = (int) $existing['id'];
            foreach (array_unique($memberIds) as $uid) {
                Database::query(
                    'INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (:c,:u)',
                    ['c' => $id, 'u' => $uid]
                );
            }
            return $id;
        }

        return self::createGroup($cohort['name'], $memberIds, $cohortId, 'programme_cohort');
    }

    public static function addParticipant(int $conversationId, int $userId, string $role = 'member'): void
    {
        Database::query(
            'INSERT INTO conversation_participants (conversation_id, user_id, role) VALUES (:c,:u,:r)
             ON DUPLICATE KEY UPDATE left_at = NULL, role = VALUES(role)',
            ['c' => $conversationId, 'u' => $userId, 'r' => $role]
        );
    }

    public static function removeParticipant(int $conversationId, int $userId): void
    {
        Database::query(
            'UPDATE conversation_participants SET left_at = NOW() WHERE conversation_id = :c AND user_id = :u',
            ['c' => $conversationId, 'u' => $userId]
        );
    }

    public static function markRead(int $conversationId, int $userId): void
    {
        Database::query(
            'UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = :c AND user_id = :u',
            ['c' => $conversationId, 'u' => $userId]
        );
    }

    public static function setMuted(int $conversationId, int $userId, bool $muted): void
    {
        Database::query(
            'UPDATE conversation_participants SET muted_until = :m WHERE conversation_id = :c AND user_id = :u',
            ['m' => $muted ? date('Y-m-d H:i:s', strtotime('+10 years')) : null, 'c' => $conversationId, 'u' => $userId]
        );
    }

    public static function isMuted(int $conversationId, int $userId): bool
    {
        $row = Database::one(
            'SELECT muted_until FROM conversation_participants WHERE conversation_id = :c AND user_id = :u',
            ['c' => $conversationId, 'u' => $userId]
        );
        return $row && $row['muted_until'] !== null && strtotime($row['muted_until']) > time();
    }

    /** Display title — a direct thread is named after the other person. */
    public static function titleFor(array $conversation, int $viewerId): string
    {
        if ($conversation['type'] === 'direct') {
            $other = self::counterpart((int) $conversation['id'], $viewerId);
            return $other ? ($other['name'] ?: $other['email']) : 'Direct message';
        }
        return (string) ($conversation['title'] ?? 'Group');
    }
}
