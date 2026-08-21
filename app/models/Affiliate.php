<?php
declare(strict_types=1);

/**
 * The affiliate programme (README §25).
 *
 * Three rules the money side depends on, each enforced by a database constraint rather
 * than by remembering to check:
 *
 *   1. A person can be referred once, ever — UNIQUE on referrals.referred_user_id.
 *      Re-attributing an existing customer to a new affiliate is the obvious fraud.
 *   2. A payment earns at most one commission — UNIQUE on commissions.payment_id.
 *      A replayed webhook or a re-run reconciliation cannot pay twice.
 *   3. Rates are basis points, snapshotted at earning. Renegotiating next month must not
 *      silently revalue commissions already earned.
 */
final class Affiliate
{
    public const COOKIE = 'ultrademy_ref';

    /**
     * Which payments earn commission.
     *
     * Donations are deliberately excluded: paying a commission out of a charitable gift
     * spends money the donor intended for the cause. Application fees are excluded as
     * too small to be worth the accounting.
     */
    private const EARNING_PAYABLE_TYPES = ['enrolment', 'subscription'];

    public static function enabled(): bool
    {
        return (string) (Setting::get('affiliate_enabled', '0') ?? '0') === '1';
    }

    public static function defaultRateBps(): int
    {
        return max(0, (int) (Setting::get('affiliate_default_rate_bps', '500') ?? 500));
    }

    public static function cookieDays(): int
    {
        return max(1, (int) (Setting::get('affiliate_cookie_days', '30') ?? 30));
    }

    public static function minPayout(): int
    {
        return max(0, (int) (Setting::get('affiliate_min_payout', '500000') ?? 500000));
    }

    // ------------------------------------------------------------------ accounts

    public static function forUser(int $userId): ?array
    {
        return Database::one('SELECT * FROM affiliates WHERE user_id = :u', ['u' => $userId]);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT a.*, CONCAT(p.first_name,' ',p.last_name) AS name, u.email
             FROM affiliates a JOIN users u ON u.id = a.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id WHERE a.id = :id",
            ['id' => $id]
        );
    }

    public static function findByCode(string $code): ?array
    {
        return Database::one(
            "SELECT * FROM affiliates WHERE code = :c AND status = 'approved'",
            ['c' => strtoupper(trim($code))]
        );
    }

    public static function listing(?string $status = null): array
    {
        $sql = "SELECT a.*, CONCAT(p.first_name,' ',p.last_name) AS name, u.email,
                       (SELECT COUNT(*) FROM referrals r WHERE r.affiliate_id = a.id) AS referral_count,
                       (SELECT COUNT(*) FROM referrals r WHERE r.affiliate_id = a.id AND r.status = 'qualified') AS qualified_count,
                       (SELECT COALESCE(SUM(c.amount),0) FROM commissions c WHERE c.affiliate_id = a.id AND c.status <> 'void') AS earned
                FROM affiliates a JOIN users u ON u.id = a.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id WHERE 1=1";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' AND a.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY a.created_at DESC', $params)->fetchAll();
    }

    /**
     * Applies to join. Returns the affiliate id, or an error string.
     *
     * The code is generated here rather than chosen by the applicant: a self-chosen code
     * lets someone claim a lookalike of a real programme name, or of another affiliate's.
     */
    public static function apply(int $userId, string $motivation, string $payoutMethod, string $payoutDetails): array
    {
        if (!self::enabled()) {
            return ['ok' => false, 'error' => 'The affiliate programme is not open at the moment.', 'id' => null];
        }
        if (self::forUser($userId) !== null) {
            return ['ok' => false, 'error' => 'You have already applied.', 'id' => null];
        }

        $id = Database::transaction(static function () use ($userId, $motivation, $payoutMethod, $payoutDetails): int {
            Database::query(
                "INSERT INTO affiliates (user_id, code, status, commission_rate_bps, payout_method, payout_details, motivation)
                 VALUES (:u,:c,'applied',:r,:pm,:pd,:m)",
                [
                    'u' => $userId, 'c' => self::generateCode(), 'r' => self::defaultRateBps(),
                    'pm' => $payoutMethod ?: null, 'pd' => $payoutDetails ?: null,
                    'm' => $motivation ?: null,
                ]
            );
            return Database::lastInsertId();
        });

        Audit::log('affiliate.applied', 'affiliates', $id, null, ['user_id' => $userId]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    private static function generateCode(): string
    {
        // Ambiguous characters removed: someone will read this code aloud or copy it off
        // a screen, and 0/O and 1/I/L are where that goes wrong.
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Database::one('SELECT 1 FROM affiliates WHERE code = :c', ['c' => $code]));
        return $code;
    }

    /** Approve, reject or suspend. Grants or revokes the `affiliate` role to match. */
    public static function decide(int $affiliateId, string $status, string $note, ?int $rateBps = null): string
    {
        if (!in_array($status, ['under_review', 'approved', 'rejected', 'suspended'], true)) {
            return 'Unknown status.';
        }
        $affiliate = self::find($affiliateId);
        if (!$affiliate) {
            return 'Affiliate not found.';
        }

        Database::transaction(static function () use ($affiliateId, $affiliate, $status, $note, $rateBps): void {
            Database::query(
                'UPDATE affiliates SET status = :s, decision_note = :n,
                        commission_rate_bps = COALESCE(:r, commission_rate_bps),
                        approved_by = :by,
                        approved_at = CASE WHEN :s2 = \'approved\' THEN NOW() ELSE approved_at END
                 WHERE id = :id',
                [
                    's' => $status, 'n' => $note !== '' ? mb_substr($note, 0, 255) : null,
                    'r' => $rateBps, 'by' => Auth::id(), 's2' => $status, 'id' => $affiliateId,
                ]
            );

            // 03-rbac.md §2: the affiliate role is granted by the system when the record
            // warrants it, never assigned by hand.
            $roleId = (int) (Database::one("SELECT id FROM roles WHERE code = 'affiliate'")['id'] ?? 0);
            if ($roleId > 0) {
                if ($status === 'approved') {
                    Database::query(
                        'INSERT IGNORE INTO user_roles (user_id, role_id, centre_id, granted_at, granted_by)
                         VALUES (:u,:r,NULL,NOW(),:by)',
                        ['u' => (int) $affiliate['user_id'], 'r' => $roleId, 'by' => Auth::id()]
                    );
                } else {
                    Database::query(
                        'DELETE FROM user_roles WHERE user_id = :u AND role_id = :r AND centre_id IS NULL',
                        ['u' => (int) $affiliate['user_id'], 'r' => $roleId]
                    );
                }
            }
        });

        Audit::log('affiliate.' . $status, 'affiliates', $affiliateId,
            ['status' => $affiliate['status']], ['status' => $status, 'note' => $note]);

        Notify::send((int) $affiliate['user_id'], 'affiliate.' . $status, 'general',
            match ($status) {
                'approved' => 'Your affiliate application was approved',
                'rejected' => 'Your affiliate application was not approved',
                'suspended' => 'Your affiliate account has been suspended',
                default => 'Your affiliate application is under review',
            },
            $note !== '' ? $note : null,
            affiliate_url('app.php'));

        return '';
    }

    // ----------------------------------------------------------------- referrals

    /**
     * Records that a newly registered user came from an affiliate link.
     *
     * Called from registration. Returns silently on every rejection rather than telling
     * the caller why — a registration form is not a place to explain that a referral was
     * refused, and the reasons (already referred, self-referral) are not the new user's
     * business.
     */
    public static function attributeRegistration(int $newUserId, ?string $code): void
    {
        if (!self::enabled() || $code === null || trim($code) === '') {
            return;
        }
        $affiliate = self::findByCode($code);
        if (!$affiliate) {
            return;
        }
        // Self-referral: signing up through your own link to collect on your own purchase.
        if ((int) $affiliate['user_id'] === $newUserId) {
            return;
        }
        // UNIQUE(referred_user_id) would reject this anyway; checking first avoids
        // throwing an exception into the middle of a registration.
        if (Database::one('SELECT 1 FROM referrals WHERE referred_user_id = :u', ['u' => $newUserId])) {
            return;
        }

        try {
            Database::query(
                "INSERT INTO referrals (affiliate_id, referred_user_id, landed_at, registered_at, status)
                 VALUES (:a,:u,NOW(),NOW(),'pending')",
                ['a' => (int) $affiliate['id'], 'u' => $newUserId]
            );
            Audit::log('referral.created', 'referrals', Database::lastInsertId(), null,
                ['affiliate_id' => $affiliate['id'], 'referred_user_id' => $newUserId]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
        }
    }

    public static function referralsFor(int $affiliateId): array
    {
        return Database::all(
            "SELECT r.*, CONCAT(p.first_name,' ',p.last_name) AS referred_name,
                    (SELECT COALESCE(SUM(c.amount),0) FROM commissions c WHERE c.referral_id = r.id AND c.status <> 'void') AS earned
             FROM referrals r
             JOIN users u ON u.id = r.referred_user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE r.affiliate_id = :a ORDER BY r.registered_at DESC",
            ['a' => $affiliateId]
        );
    }

    // --------------------------------------------------------------- commissions

    /**
     * Earning hook — called after any payment is marked successful.
     *
     * Decision 6 default: commission is paid on the referred user's FIRST qualifying
     * payment only, not on everything they ever buy. That is the narrower reading, and
     * widening it later is a one-line change; narrowing it after affiliates have been
     * paid on repeat purchases is a conversation nobody wants.
     *
     * Every rejection path returns quietly. This runs inside the payment flow, and an
     * affiliate problem must never break someone's enrolment.
     */
    public static function onPaymentSuccessful(int $paymentId): void
    {
        if (!self::enabled()) {
            return;
        }
        try {
            $payment = Database::one(
                'SELECT p.*, i.payable_type FROM payments p JOIN invoices i ON i.id = p.invoice_id WHERE p.id = :id',
                ['id' => $paymentId]
            );
            if (!$payment || $payment['status'] !== 'successful') {
                return;
            }
            if (!in_array((string) $payment['payable_type'], self::EARNING_PAYABLE_TYPES, true)) {
                return;
            }

            $referral = Database::one(
                "SELECT r.*, a.commission_rate_bps, a.status AS affiliate_status
                 FROM referrals r JOIN affiliates a ON a.id = r.affiliate_id
                 WHERE r.referred_user_id = :u",
                ['u' => (int) $payment['user_id']]
            );
            if (!$referral || $referral['status'] === 'void' || $referral['affiliate_status'] !== 'approved') {
                return;
            }
            // First qualifying payment only.
            if ($referral['qualified_at'] !== null) {
                return;
            }

            $rate = (int) $referral['commission_rate_bps'];
            $base = (int) $payment['amount'];
            // intdiv, not round: fractions of a kobo are never created, and the house
            // rounds down rather than paying out money it did not receive.
            $amount = intdiv($base * $rate, 10000);
            if ($amount <= 0) {
                return;
            }

            Database::transaction(static function () use ($referral, $paymentId, $base, $rate, $amount, $payment): void {
                Database::query(
                    "INSERT INTO commissions (affiliate_id, referral_id, payment_id, base_amount, rate_bps, amount, currency, status)
                     VALUES (:a,:r,:p,:base,:rate,:amt,:cur,'pending')",
                    [
                        'a' => (int) $referral['affiliate_id'], 'r' => (int) $referral['id'], 'p' => $paymentId,
                        'base' => $base, 'rate' => $rate, 'amt' => $amount, 'cur' => $payment['currency'],
                    ]
                );
                Database::query(
                    "UPDATE referrals SET status = 'qualified', qualified_at = NOW() WHERE id = :id AND qualified_at IS NULL",
                    ['id' => (int) $referral['id']]
                );
            });

            Audit::log('commission.earned', 'commissions', Database::lastInsertId(), null,
                ['affiliate_id' => $referral['affiliate_id'], 'amount' => $amount, 'payment_id' => $paymentId]);

            $affiliate = self::find((int) $referral['affiliate_id']);
            if ($affiliate) {
                Notify::send((int) $affiliate['user_id'], 'commission.earned', 'general',
                    'You earned a commission',
                    Money::format($amount, $payment['currency']) . ' from a referral, pending approval.',
                    affiliate_url('app.php'));
            }
        } catch (PDOException $e) {
            // 1062 = the UNIQUE on payment_id, i.e. a replayed webhook. Expected, ignored.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                error_log('[affiliate] commission hook: ' . $e->getMessage());
            }
        } catch (Throwable $e) {
            error_log('[affiliate] commission hook: ' . $e->getMessage());
        }
    }

    public static function commissionsFor(int $affiliateId, ?string $status = null): array
    {
        $sql = "SELECT c.*, CONCAT(p.first_name,' ',p.last_name) AS referred_name
                FROM commissions c
                JOIN referrals r ON r.id = c.referral_id
                JOIN users u ON u.id = r.referred_user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id
                WHERE c.affiliate_id = ?";
        $params = [$affiliateId];
        if ($status !== null && $status !== '') {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY c.created_at DESC', $params)->fetchAll();
    }

    public static function pendingCommissions(): array
    {
        return Database::all(
            "SELECT c.*, a.code, CONCAT(p.first_name,' ',p.last_name) AS affiliate_name
             FROM commissions c
             JOIN affiliates a ON a.id = c.affiliate_id
             JOIN users u ON u.id = a.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id
             WHERE c.status = 'pending' ORDER BY c.created_at"
        );
    }

    public static function decideCommission(int $commissionId, bool $approve, string $reason): string
    {
        $c = Database::one('SELECT * FROM commissions WHERE id = :id', ['id' => $commissionId]);
        if (!$c) {
            return 'Commission not found.';
        }
        if ($c['status'] !== 'pending') {
            return 'Only a pending commission can be decided.';
        }
        Database::query(
            'UPDATE commissions SET status = :s, approved_by = :by, approved_at = NOW(), void_reason = :r WHERE id = :id',
            [
                's' => $approve ? 'approved' : 'void', 'by' => Auth::id(),
                'r' => $approve ? null : mb_substr($reason !== '' ? $reason : 'Voided by review.', 0, 255),
                'id' => $commissionId,
            ]
        );
        Audit::log($approve ? 'commission.approved' : 'commission.voided', 'commissions', $commissionId,
            ['status' => 'pending'], ['status' => $approve ? 'approved' : 'void', 'reason' => $reason]);
        return '';
    }

    /**
     * Reverses the commission earned on a payment that has just been refunded —
     * Decision 34 (19-affiliate.md §10, confirmed 21 Aug 2026: reverse automatically,
     * previously "the real gap"). Called from Refund::decide() the moment a refund is
     * approved, in the same request as the payment being marked reversed.
     *
     * A commission still `pending` or `approved` (not yet swept into a payout) is voided
     * outright — no money has left the business, so there is nothing to recover.
     *
     * A commission already `paid` is the harder case: the affiliate already has the
     * money. Automatically debiting a future payout is a materially bigger feature — a
     * running balance, the possibility of a negative payout — than "reverse
     * automatically" was scoped to cover here. This voids the commission (it stops
     * counting toward the affiliate's totals) and records that it was already paid, so
     * recovery is a deliberate manual finance decision, not a silently adjusted number.
     */
    public static function clawback(int $paymentId, string $reason): void
    {
        $commission = Database::one(
            "SELECT * FROM commissions WHERE payment_id = :p AND status <> 'void'",
            ['p' => $paymentId]
        );
        if (!$commission) {
            return; // no commission was ever earned on this payment
        }

        $wasPaid = $commission['status'] === 'paid';
        Database::query(
            "UPDATE commissions SET status = 'void', void_reason = :r WHERE id = :id",
            ['r' => mb_substr($reason, 0, 255), 'id' => $commission['id']]
        );
        Audit::log('commission.clawed_back', 'commissions', (int) $commission['id'],
            ['status' => $commission['status']],
            ['status' => 'void', 'reason' => $reason, 'was_already_paid' => $wasPaid]);
    }

    /** Approved but not yet attached to a payout. */
    public static function payableBalance(int $affiliateId): int
    {
        return (int) (Database::one(
            "SELECT COALESCE(SUM(amount),0) AS t FROM commissions
             WHERE affiliate_id = :a AND status = 'approved' AND payout_id IS NULL",
            ['a' => $affiliateId]
        )['t'] ?? 0);
    }

    // ------------------------------------------------------------------- payouts

    /**
     * Sweeps every approved, unpaid commission into one payout.
     *
     * Wrapped in a transaction and re-read inside it: without that, two operators
     * clicking "pay" at the same time would each sweep the same commissions into a
     * separate payout, and the affiliate is paid twice.
     */
    public static function createPayout(int $affiliateId, string $method, string $note): array
    {
        $balance = self::payableBalance($affiliateId);
        $min = self::minPayout();
        if ($balance <= 0) {
            return ['ok' => false, 'error' => 'Nothing approved and unpaid for this affiliate.', 'id' => null];
        }
        if ($balance < $min) {
            return ['ok' => false, 'error' => 'Balance is below the ' . Money::format($min) . ' payout minimum.', 'id' => null];
        }

        $id = Database::transaction(static function () use ($affiliateId, $method, $note): ?int {
            $rows = Database::all(
                "SELECT id, amount, currency FROM commissions
                 WHERE affiliate_id = :a AND status = 'approved' AND payout_id IS NULL FOR UPDATE",
                ['a' => $affiliateId]
            );
            if ($rows === []) {
                return null;
            }
            $total = 0;
            foreach ($rows as $r) {
                $total += (int) $r['amount'];
            }
            Database::query(
                "INSERT INTO affiliate_payouts (reference, affiliate_id, amount, currency, method, status, note)
                 VALUES (:ref,:a,:amt,:cur,:m,'requested',:n)",
                [
                    'ref' => 'ULA-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'a' => $affiliateId, 'amt' => $total, 'cur' => $rows[0]['currency'],
                    'm' => $method ?: null, 'n' => $note !== '' ? mb_substr($note, 0, 255) : null,
                ]
            );
            $payoutId = Database::lastInsertId();
            $ids = implode(',', array_map('intval', array_column($rows, 'id')));
            Database::query("UPDATE commissions SET payout_id = $payoutId WHERE id IN ($ids)");
            return $payoutId;
        });

        if ($id === null) {
            return ['ok' => false, 'error' => 'Nothing left to pay — another operator may have just swept it.', 'id' => null];
        }
        Audit::log('affiliate.payout_created', 'affiliate_payouts', $id, null, ['affiliate_id' => $affiliateId, 'amount' => $balance]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    public static function markPayoutPaid(int $payoutId, string $bankReference): string
    {
        $p = Database::one('SELECT * FROM affiliate_payouts WHERE id = :id', ['id' => $payoutId]);
        if (!$p) {
            return 'Payout not found.';
        }
        if ($p['status'] === 'paid') {
            return 'That payout is already marked paid.';
        }
        Database::transaction(static function () use ($payoutId, $bankReference): void {
            Database::query(
                "UPDATE affiliate_payouts SET status = 'paid', paid_at = NOW(), processed_by = :by, bank_reference = :ref
                 WHERE id = :id",
                ['by' => Auth::id(), 'ref' => $bankReference !== '' ? $bankReference : null, 'id' => $payoutId]
            );
            Database::query("UPDATE commissions SET status = 'paid' WHERE payout_id = :id", ['id' => $payoutId]);
        });

        Audit::log('affiliate.payout_paid', 'affiliate_payouts', $payoutId,
            ['status' => $p['status']], ['status' => 'paid', 'bank_reference' => $bankReference]);

        $affiliate = self::find((int) $p['affiliate_id']);
        if ($affiliate) {
            Notify::send((int) $affiliate['user_id'], 'affiliate.paid', 'payment',
                'Your commission has been paid',
                Money::format((int) $p['amount'], $p['currency']) . ' has been sent.',
                affiliate_url('app.php'));
        }
        return '';
    }

    public static function payouts(?int $affiliateId = null): array
    {
        $sql = "SELECT po.*, a.code, CONCAT(p.first_name,' ',p.last_name) AS affiliate_name
                FROM affiliate_payouts po
                JOIN affiliates a ON a.id = po.affiliate_id
                JOIN users u ON u.id = a.user_id
                LEFT JOIN user_profiles p ON p.user_id = u.id WHERE 1=1";
        $params = [];
        if ($affiliateId !== null) {
            $sql .= ' AND po.affiliate_id = ?';
            $params[] = $affiliateId;
        }
        return Database::query($sql . ' ORDER BY po.requested_at DESC', $params)->fetchAll();
    }

    /** @return array{referrals:int,qualified:int,pending:int,approved:int,paid:int} */
    public static function stats(int $affiliateId): array
    {
        $r = Database::one(
            'SELECT COUNT(*) AS n, SUM(status = \'qualified\') AS q FROM referrals WHERE affiliate_id = :a',
            ['a' => $affiliateId]
        ) ?: ['n' => 0, 'q' => 0];
        $c = Database::one(
            "SELECT COALESCE(SUM(status = 'pending'  ) * 0, 0) AS z,
                    COALESCE(SUM(CASE WHEN status = 'pending'  THEN amount ELSE 0 END),0) AS pending,
                    COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END),0) AS approved,
                    COALESCE(SUM(CASE WHEN status = 'paid'     THEN amount ELSE 0 END),0) AS paid
             FROM commissions WHERE affiliate_id = :a",
            ['a' => $affiliateId]
        ) ?: [];
        return [
            'referrals' => (int) ($r['n'] ?? 0),
            'qualified' => (int) ($r['q'] ?? 0),
            'pending'   => (int) ($c['pending'] ?? 0),
            'approved'  => (int) ($c['approved'] ?? 0),
            'paid'      => (int) ($c['paid'] ?? 0),
        ];
    }

    /** Commission on an amount, in minor units. Rounds down — see onPaymentSuccessful. */
    public static function calculate(int $baseMinor, int $rateBps): int
    {
        if ($baseMinor <= 0 || $rateBps <= 0) {
            return 0;
        }
        return intdiv($baseMinor * $rateBps, 10000);
    }
}
