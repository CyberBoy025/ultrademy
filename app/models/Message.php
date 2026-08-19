<?php
declare(strict_types=1);

final class Message
{
    /**
     * Messages in a thread. Deleted messages are RETURNED (as tombstones) rather than
     * hidden, so a moderated conversation does not silently lose its shape — a reply to
     * a removed message would otherwise read as a non-sequitur.
     */
    public static function forConversation(int $conversationId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        return Database::query(
            "SELECT m.*, CONCAT(pr.first_name,' ',pr.last_name) AS sender_name, u.email AS sender_email,
                    CONCAT(dp.first_name,' ',dp.last_name) AS deleted_by_name
             FROM messages m
             LEFT JOIN users u ON u.id = m.sender_id
             LEFT JOIN user_profiles pr ON pr.user_id = m.sender_id
             LEFT JOIN user_profiles dp ON dp.user_id = m.deleted_by
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC LIMIT $limit",
            [$conversationId]
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT m.*, c.type AS conversation_type FROM messages m
             JOIN conversations c ON c.id = m.conversation_id WHERE m.id = :id',
            ['id' => $id]
        );
    }

    /** @return array{ok:bool,error:?string,id:?int} */
    public static function post(int $conversationId, int $senderId, string $body, array $file = []): array
    {
        $body = trim($body);
        $hasFile = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($body === '' && !$hasFile) {
            return ['ok' => false, 'error' => 'Write something first.', 'id' => null];
        }
        if (mb_strlen($body) > 4000) {
            return ['ok' => false, 'error' => 'That message is too long (4000 characters max).', 'id' => null];
        }

        $stored = null;
        if ($hasFile) {
            $result = Upload::store($file, Conversation::SUBDIR, Upload::MATERIAL_TYPES, 10 * 1024 * 1024);
            if (is_string($result)) {
                return ['ok' => false, 'error' => $result, 'id' => null];
            }
            $stored = $result;
        }

        Database::query(
            'INSERT INTO messages (conversation_id, sender_id, body, attachment_stored_name, attachment_original_name, attachment_mime, attachment_size)
             VALUES (:c,:s,:b,:sn,:on,:mm,:sz)',
            [
                'c' => $conversationId, 's' => $senderId, 'b' => $body,
                'sn' => $stored['stored_name'] ?? null,
                'on' => $stored['original_name'] ?? null,
                'mm' => $stored['mime_type'] ?? null,
                'sz' => $stored['size_bytes'] ?? 0,
            ]
        );
        $id = Database::lastInsertId();

        Database::query('UPDATE conversations SET updated_at = NOW() WHERE id = :c', ['c' => $conversationId]);
        Conversation::markRead($conversationId, $senderId);

        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    /** Soft delete, always attributed. §39 requires moderation to be auditable. */
    public static function moderate(int $messageId, int $moderatorId, string $reason): void
    {
        Database::query(
            'UPDATE messages SET deleted_at = NOW(), deleted_by = :by, deleted_reason = :r
             WHERE id = :id AND deleted_at IS NULL',
            ['by' => $moderatorId, 'r' => mb_substr($reason, 0, 255), 'id' => $messageId]
        );
    }

    /** Recipients of a new message: everyone still in the thread, except the sender and the muted. */
    public static function recipients(int $conversationId, int $senderId): array
    {
        $rows = Database::all(
            'SELECT user_id FROM conversation_participants
             WHERE conversation_id = :c AND user_id <> :s AND left_at IS NULL
               AND (muted_until IS NULL OR muted_until < NOW())',
            ['c' => $conversationId, 's' => $senderId]
        );
        return array_map('intval', array_column($rows, 'user_id'));
    }
}
