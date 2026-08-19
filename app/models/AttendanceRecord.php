<?php
declare(strict_types=1);

final class AttendanceRecord
{
    /** @return array<int,string> enrolment_id => status, for whichever records already exist on this session */
    public static function forSession(int $sessionId): array
    {
        $rows = Database::all('SELECT enrolment_id, status FROM attendance_records WHERE class_session_id = :s', ['s' => $sessionId]);
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['enrolment_id']] = $r['status'];
        }
        return $out;
    }

    /** Idempotent per (session, enrolment) — matches the unique key, so this doubles as "mark or correct". */
    public static function mark(int $sessionId, int $enrolmentId, string $status): void
    {
        Database::query(
            'INSERT INTO attendance_records (class_session_id, enrolment_id, status, marked_by, marked_at)
             VALUES (:s,:e,:st,:by,NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = NOW()',
            ['s' => $sessionId, 'e' => $enrolmentId, 'st' => $status, 'by' => Auth::id()]
        );
    }

    public static function rateForSession(int $sessionId): ?float
    {
        $row = Database::one(
            "SELECT COUNT(*) total, SUM(status IN ('present','late')) present FROM attendance_records WHERE class_session_id = :s",
            ['s' => $sessionId]
        );
        if (!$row || (int) $row['total'] === 0) {
            return null;
        }
        return round(((int) $row['present']) / ((int) $row['total']) * 100);
    }
}
