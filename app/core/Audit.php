<?php
declare(strict_types=1);

/** Insert-only audit trail (README §39, data model §10 — "no update or delete path in application code"). */
final class Audit
{
    /**
     * @param array<string,mixed>|null $old
     * @param array<string,mixed>|null $new
     */
    public static function log(
        string $action,
        string $auditableType,
        int $auditableId,
        ?array $old = null,
        ?array $new = null,
        ?int $centreId = null
    ): void {
        Database::query(
            'INSERT INTO audit_logs
                (actor_user_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, user_agent, centre_id, created_at)
             VALUES (:actor, :action, :type, :id, :old, :new, :ip, :ua, :centre, NOW())',
            [
                'actor'  => Auth::id(),
                'action' => $action,
                'type'   => $auditableType,
                'id'     => $auditableId,
                'old'    => $old === null ? null : json_encode($old),
                'new'    => $new === null ? null : json_encode($new),
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'     => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
                'centre' => $centreId,
            ]
        );
    }
}
