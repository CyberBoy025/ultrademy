<?php
declare(strict_types=1);

/**
 * Donations and the campaigns they fund (README §9b).
 *
 * The design rule, stated once so it survives future edits: **a donation does not have
 * its own money path.** It raises an invoice with `payable_type = 'donation'` and is
 * settled by the existing gateways. Everything downstream — webhook verification,
 * receipts, refunds, reconciliation, centre attribution, the accountant's dashboard —
 * therefore works with no donation-specific code at all.
 *
 * A donation is a GIFT. Nothing is given in return: no equity, no revenue share, no
 * promise of repayment. If UltrAdemy ever wants to offer any of those, that is a
 * security under SEC Nigeria rules and is emphatically not this feature.
 */
final class Donation
{
    /** Suggested amounts on the public form, in minor units. */
    public const PRESETS = [500000, 1000000, 2500000, 5000000];

    // ------------------------------------------------------------------ campaigns

    public static function campaigns(bool $publishedOnly = false): array
    {
        $sql = 'SELECT c.*, ct.name AS centre_name,
                       (SELECT COALESCE(SUM(d.amount),0) FROM donations d
                         WHERE d.campaign_id = c.id AND d.status = \'completed\') AS raised_amount,
                       (SELECT COUNT(*) FROM donations d
                         WHERE d.campaign_id = c.id AND d.status = \'completed\') AS donor_count
                FROM donation_campaigns c
                LEFT JOIN centres ct ON ct.id = c.centre_id';
        if ($publishedOnly) {
            $sql .= " WHERE c.status = 'published'
                        AND (c.starts_on IS NULL OR c.starts_on <= CURDATE())
                        AND (c.ends_on   IS NULL OR c.ends_on   >= CURDATE())";
        }
        return Database::all($sql . ' ORDER BY c.status = \'published\' DESC, c.id DESC');
    }

    public static function findCampaign(int $id): ?array
    {
        return Database::one(
            'SELECT c.*, ct.name AS centre_name,
                    (SELECT COALESCE(SUM(d.amount),0) FROM donations d WHERE d.campaign_id = c.id AND d.status = \'completed\') AS raised_amount,
                    (SELECT COUNT(*) FROM donations d WHERE d.campaign_id = c.id AND d.status = \'completed\') AS donor_count
             FROM donation_campaigns c LEFT JOIN centres ct ON ct.id = c.centre_id WHERE c.id = :id',
            ['id' => $id]
        );
    }

    public static function findCampaignBySlug(string $slug): ?array
    {
        $row = Database::one('SELECT id FROM donation_campaigns WHERE slug = :s', ['s' => $slug]);
        return $row ? self::findCampaign((int) $row['id']) : null;
    }

    /** Open for giving right now — status plus the date window. */
    public static function campaignIsOpen(array $c): bool
    {
        if ($c['status'] !== 'published') {
            return false;
        }
        $today = date('Y-m-d');
        if ($c['starts_on'] && $c['starts_on'] > $today) {
            return false;
        }
        if ($c['ends_on'] && $c['ends_on'] < $today) {
            return false;
        }
        return true;
    }

    public static function createCampaign(array $d): int
    {
        Database::query(
            'INSERT INTO donation_campaigns
                (slug, title, summary, story, target_amount, currency, centre_id, starts_on, ends_on,
                 show_donor_wall, status, created_by)
             VALUES (:slug,:t,:sum,:story,:target,:cur,:centre,:starts,:ends,:wall,:status,:by)',
            [
                'slug' => self::uniqueSlug($d['title']), 't' => $d['title'], 'sum' => $d['summary'] ?: null,
                'story' => $d['story'] ?: null, 'target' => $d['target_amount'] ?: null,
                'cur' => $d['currency'] ?? 'NGN', 'centre' => $d['centre_id'] ?: null,
                'starts' => $d['starts_on'] ?: null, 'ends' => $d['ends_on'] ?: null,
                'wall' => $d['show_donor_wall'], 'status' => 'draft', 'by' => Auth::id(),
            ]
        );
        return Database::lastInsertId();
    }

    public static function updateCampaign(int $id, array $d): void
    {
        Database::query(
            'UPDATE donation_campaigns SET title=:t, summary=:sum, story=:story, target_amount=:target,
                    centre_id=:centre, starts_on=:starts, ends_on=:ends, show_donor_wall=:wall
             WHERE id=:id',
            [
                't' => $d['title'], 'sum' => $d['summary'] ?: null, 'story' => $d['story'] ?: null,
                'target' => $d['target_amount'] ?: null, 'centre' => $d['centre_id'] ?: null,
                'starts' => $d['starts_on'] ?: null, 'ends' => $d['ends_on'] ?: null,
                'wall' => $d['show_donor_wall'], 'id' => $id,
            ]
        );
    }

    public static function setCampaignStatus(int $id, string $status): void
    {
        Database::query('UPDATE donation_campaigns SET status = :s WHERE id = :id', ['s' => $status, 'id' => $id]);
    }

    private static function uniqueSlug(string $title): string
    {
        $base = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($title))), '-');
        $base = $base !== '' ? mb_substr($base, 0, 100) : 'campaign';
        $slug = $base;
        $n = 2;
        while (Database::one('SELECT 1 FROM donation_campaigns WHERE slug = :s', ['s' => $slug])) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    // ------------------------------------------------------------------ donations

    /**
     * Resolves the donor to a `users` row.
     *
     * Two rules matter here, and both are about not leaking:
     *
     *   1. An existing account is reused, never duplicated (README §3). The donor's name
     *      on the existing profile is left alone — a donation form is not a place to
     *      overwrite someone's identity.
     *   2. The caller gets no signal about whether the address already existed. A public
     *      form that answers "this email is registered" differently is an account
     *      enumeration oracle.
     *
     * The created row has an unusable password and `status = 'pending'`, so it cannot be
     * signed into. It becomes a real account only if the person registers properly.
     */
    public static function resolveDonorUser(string $email, string $name): int
    {
        $email = mb_strtolower(trim($email));
        $existing = Database::one('SELECT id FROM users WHERE email = :e', ['e' => $email]);
        if ($existing) {
            return (int) $existing['id'];
        }

        return Database::transaction(static function () use ($email, $name): int {
            // Unusable by construction: a 64-byte random secret nobody holds, hashed.
            // Not an empty string, which some password_verify paths would accept.
            Database::query(
                "INSERT INTO users (email, password_hash, status) VALUES (:e,:p,'pending')",
                ['e' => $email, 'p' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]
            );
            $userId = Database::lastInsertId();

            $parts = preg_split('/\s+/', trim($name), 2) ?: [];
            Database::query(
                'INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)',
                ['u' => $userId, 'f' => $parts[0] ?? 'Supporter', 'l' => $parts[1] ?? '']
            );
            return $userId;
        });
    }

    public static function newReference(): string
    {
        return 'ULD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Records the intent and raises the invoice it will be paid against.
     *
     * The donation row is created BEFORE any gateway is contacted, so a supporter who
     * abandons the payment leaves a `pending` record rather than nothing — which is what
     * makes an abandoned-giving report possible later.
     *
     * @return array{ok:bool,error:?string,donation:?array}
     */
    public static function start(?int $campaignId, array $donor, int $amountMinor, string $currency = 'NGN'): array
    {
        if ($amountMinor < 10000) {
            return ['ok' => false, 'error' => 'The smallest donation we can process is ' . Money::format(10000, $currency) . '.', 'donation' => null];
        }
        if ($amountMinor > 500000000) {
            // Not a limit on generosity — a guard against a fat-fingered extra zero, and
            // against a card test using a large amount.
            return ['ok' => false, 'error' => 'For gifts above ' . Money::format(500000000, $currency) . ' please contact us directly so we can arrange it properly.', 'donation' => null];
        }

        $campaign = null;
        if ($campaignId !== null) {
            $campaign = self::findCampaign($campaignId);
            if (!$campaign || !self::campaignIsOpen($campaign)) {
                return ['ok' => false, 'error' => 'That campaign is not currently accepting donations.', 'donation' => null];
            }
        }

        $userId = self::resolveDonorUser($donor['email'], $donor['name']);
        $centreId = $campaign['centre_id'] ?? null;

        return Database::transaction(static function () use ($campaignId, $campaign, $donor, $amountMinor, $currency, $userId, $centreId): array {
            $reference = self::newReference();
            Database::query(
                'INSERT INTO donations
                    (reference, public_token, campaign_id, donor_user_id, donor_name, donor_email, donor_phone,
                     amount, currency, is_anonymous, message, centre_id, status)
                 VALUES (:ref,:tok,:c,:u,:n,:e,:ph,:amt,:cur,:anon,:msg,:centre,\'pending\')',
                [
                    'ref' => $reference, 'tok' => bin2hex(random_bytes(16)), 'c' => $campaignId, 'u' => $userId,
                    'n' => $donor['name'], 'e' => mb_strtolower(trim($donor['email'])), 'ph' => $donor['phone'] ?: null,
                    'amt' => $amountMinor, 'cur' => $currency, 'anon' => $donor['is_anonymous'] ? 1 : 0,
                    'msg' => $donor['message'] ?: null, 'centre' => $centreId,
                ]
            );
            $donationId = Database::lastInsertId();

            $label = $campaign ? 'Donation — ' . $campaign['title'] : 'Donation to UltrAdemy';
            $invoiceId = Invoice::issue(
                $userId,
                [['description' => $label, 'quantity' => 1, 'unit_amount' => $amountMinor]],
                'donation',
                $donationId,
                $centreId !== null ? (int) $centreId : null,
                null,
                0,
                $currency
            );
            Database::query('UPDATE donations SET invoice_id = :i WHERE id = :id', ['i' => $invoiceId, 'id' => $donationId]);

            Audit::log('donation.started', 'donations', $donationId, null,
                ['amount' => $amountMinor, 'campaign_id' => $campaignId], $centreId !== null ? (int) $centreId : null);

            return ['ok' => true, 'error' => null, 'donation' => self::find($donationId)];
        });
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT d.*, c.title AS campaign_title, c.slug AS campaign_slug, i.number AS invoice_number
             FROM donations d
             LEFT JOIN donation_campaigns c ON c.id = d.campaign_id
             LEFT JOIN invoices i ON i.id = d.invoice_id
             WHERE d.id = :id',
            ['id' => $id]
        );
    }

    public static function findByToken(string $token): ?array
    {
        $row = Database::one('SELECT id FROM donations WHERE public_token = :t', ['t' => $token]);
        return $row ? self::find((int) $row['id']) : null;
    }

    public static function findByInvoice(int $invoiceId): ?array
    {
        $row = Database::one('SELECT id FROM donations WHERE invoice_id = :i', ['i' => $invoiceId]);
        return $row ? self::find((int) $row['id']) : null;
    }

    /**
     * Called by PaymentService::fulfil() when a donation invoice is paid in full.
     * Idempotent — a replayed webhook must not send a second thank-you.
     */
    public static function markCompleted(int $donationId): void
    {
        $donation = self::find($donationId);
        if (!$donation || $donation['status'] === 'completed') {
            return;
        }
        Database::query(
            "UPDATE donations SET status = 'completed', completed_at = NOW() WHERE id = :id AND status <> 'completed'",
            ['id' => $donationId]
        );
        Audit::log('donation.completed', 'donations', $donationId,
            ['status' => $donation['status']], ['status' => 'completed'],
            $donation['centre_id'] !== null ? (int) $donation['centre_id'] : null);

        Notify::send(
            (int) $donation['donor_user_id'],
            'donation.received',
            'payment',
            'Thank you for your gift',
            'We have received your donation of ' . Money::format((int) $donation['amount'], $donation['currency']) . '. Thank you.',
            null
        );
    }

    public static function markFailed(int $donationId): void
    {
        Database::query("UPDATE donations SET status = 'failed' WHERE id = :id AND status = 'pending'", ['id' => $donationId]);
    }

    // -------------------------------------------------------------------- reading

    /** @param array<int,int>|null $centreIds null = unscoped (global finance) */
    public static function listing(?array $centreIds = null, ?string $status = null): array
    {
        $sql = 'SELECT d.*, c.title AS campaign_title FROM donations d
                LEFT JOIN donation_campaigns c ON c.id = d.campaign_id WHERE 1=1';
        $params = [];
        if ($centreIds !== null) {
            if ($centreIds === []) {
                return [];
            }
            $ph = implode(',', array_fill(0, count($centreIds), '?'));
            $sql .= " AND d.centre_id IN ($ph)";
            $params = array_values($centreIds);
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND d.status = ?';
            $params[] = $status;
        }
        return Database::query($sql . ' ORDER BY d.created_at DESC LIMIT 500', $params)->fetchAll();
    }

    /** Completed gifts for the public donor wall — honours `is_anonymous`. */
    public static function wall(?int $campaignId, int $limit = 20): array
    {
        $sql = "SELECT CASE WHEN d.is_anonymous = 1 THEN NULL ELSE d.donor_name END AS donor_name,
                       d.amount, d.currency, d.message, d.completed_at, d.is_anonymous
                FROM donations d WHERE d.status = 'completed'";
        $params = [];
        if ($campaignId !== null) {
            $sql .= ' AND d.campaign_id = ?';
            $params[] = $campaignId;
        }
        $sql .= ' ORDER BY d.completed_at DESC LIMIT ' . max(1, min(100, $limit));
        return Database::query($sql, $params)->fetchAll();
    }

    /** @return array{total:int,count:int,average:int} completed only */
    public static function totals(?int $campaignId = null): array
    {
        $sql = "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS n FROM donations WHERE status = 'completed'";
        $params = [];
        if ($campaignId !== null) {
            $sql .= ' AND campaign_id = ?';
            $params[] = $campaignId;
        }
        $row = Database::query($sql, $params)->fetch() ?: ['total' => 0, 'n' => 0];
        $count = (int) $row['n'];
        return [
            'total' => (int) $row['total'],
            'count' => $count,
            'average' => $count > 0 ? (int) round(((int) $row['total']) / $count) : 0,
        ];
    }

    /** Progress toward a campaign target, capped at 100 for the bar. */
    public static function progressPercent(array $campaign): ?int
    {
        $target = (int) ($campaign['target_amount'] ?? 0);
        if ($target <= 0) {
            return null;
        }
        return (int) min(100, round(((int) $campaign['raised_amount']) * 100 / $target));
    }

    public static function enabled(): bool
    {
        return (string) (Setting::get('donations_enabled', '0') ?? '0') === '1';
    }
}
