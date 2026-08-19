<?php
declare(strict_types=1);

/**
 * Resolves what a user may actually DO, per docs/architecture/04-subscriptions-entitlements.md §5.
 *
 * The whole point of this class (§1 of that doc) is that application code never names
 * a package. It asks `Entitlements::can('assignments')`, never
 * `if ($package === 'premium')`. Only seed data and the admin UI know tier names.
 *
 * Entitlements are NOT permissions. Permissions answer "may this role do it?" (403 on
 * failure); entitlements answer "does this subscription include it?" (402 on failure).
 * See 03-rbac.md §6 — conflating them turns a paywall into a security error.
 */
final class Entitlements
{
    /** Staff implicitly receive features in these modules regardless of subscription (Decision 16). */
    private const STAFF_IMPLICIT_MODULES = ['operations', 'comms'];

    /** @var array<int,array<string,int|null>> per-request memo: user_id => (feature_code => limit|null) */
    private static array $memo = [];

    /**
     * @return array<string,int|null> feature_code => limit. Key present = granted.
     *                                Value null = unlimited; int = metered ceiling.
     */
    public static function resolve(?int $userId = null): array
    {
        $userId ??= Auth::id();
        if ($userId === null) {
            return [];
        }
        if (isset(self::$memo[$userId])) {
            return self::$memo[$userId];
        }

        $set = [];

        // 1. Features granted by the user's ACTIVE subscription's package.
        $rows = Database::all(
            "SELECT f.code, pf.limit_value
             FROM subscriptions s
             JOIN package_features pf ON pf.package_id = s.package_id
             JOIN features f ON f.id = pf.feature_id
             WHERE s.user_id = :u
               AND s.status = 'active'
               AND (s.starts_at IS NULL OR s.starts_at <= NOW())
               AND (s.ends_at   IS NULL OR s.ends_at   >= NOW())",
            ['u' => $userId]
        );
        foreach ($rows as $r) {
            $set[$r['code']] = $r['limit_value'] === null ? null : (int) $r['limit_value'];
        }

        // 2. Role-based implicit grants for staff. Applied before overrides so that an
        //    explicit revoke in step 3 can still take a feature away from a staff member.
        if (Auth::isStaff($userId)) {
            $ph = implode(',', array_fill(0, count(self::STAFF_IMPLICIT_MODULES), '?'));
            $implicit = Database::query("SELECT code FROM features WHERE module IN ($ph)", self::STAFF_IMPLICIT_MODULES)->fetchAll();
            foreach ($implicit as $r) {
                // Only ADD features the subscription did not already grant. Never touch an
                // existing metered limit — a staff member on Standard keeps their chat_groups
                // ceiling of 2 rather than silently becoming unlimited.
                if (!array_key_exists($r['code'], $set)) {
                    $set[$r['code']] = null;
                }
            }
        }

        // 3. Per-user overrides win over everything. Expired rows are ignored.
        $overrides = Database::all(
            'SELECT f.code, o.granted, o.limit_value
             FROM entitlement_overrides o JOIN features f ON f.id = o.feature_id
             WHERE o.user_id = :u AND (o.expires_at IS NULL OR o.expires_at > NOW())',
            ['u' => $userId]
        );
        foreach ($overrides as $o) {
            if ((int) $o['granted'] === 1) {
                $set[$o['code']] = $o['limit_value'] === null ? null : (int) $o['limit_value'];
            } else {
                unset($set[$o['code']]);
            }
        }

        return self::$memo[$userId] = $set;
    }

    public static function can(string $feature, ?int $userId = null): bool
    {
        return array_key_exists($feature, self::resolve($userId));
    }

    /** null means unlimited *or* not granted — check can() first when that distinction matters. */
    public static function limitFor(string $feature, ?int $userId = null): ?int
    {
        return self::resolve($userId)[$feature] ?? null;
    }

    /** For metered features: is there room for one more, given current consumption? */
    public static function withinLimit(string $feature, int $current, ?int $userId = null): bool
    {
        if (!self::can($feature, $userId)) {
            return false;
        }
        $limit = self::limitFor($feature, $userId);
        return $limit === null || $current < $limit;
    }

    /**
     * Hard gate. 402 Payment Required — deliberately not 403; this is a paywall, not a
     * security failure (03-rbac.md §6).
     *
     * Named `requireFeature`, not `require`, because the latter is a PHP language
     * construct — legal as a method name since PHP 7 but needlessly fragile to read.
     */
    public static function requireFeature(string $feature): void
    {
        if (!self::can($feature)) {
            self::upgradeWall($feature);
        }
    }

    public static function requireWithinLimit(string $feature, int $current): void
    {
        if (!self::withinLimit($feature, $current)) {
            self::upgradeWall($feature, self::limitFor($feature));
        }
    }

    /** Drops the per-request memo. Call after anything that changes a user's entitlements. */
    public static function flush(?int $userId = null): void
    {
        if ($userId === null) {
            self::$memo = [];
        } else {
            unset(self::$memo[$userId]);
        }
    }

    private static function upgradeWall(string $feature, ?int $limit = null): void
    {
        http_response_code(402);
        $featureName = Database::one('SELECT name FROM features WHERE code = :c', ['c' => $feature])['name'] ?? $feature;
        require dirname(__DIR__) . '/views/errors/402.php';
        exit;
    }
}
