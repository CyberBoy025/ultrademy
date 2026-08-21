<?php
declare(strict_types=1);

/**
 * The affiliate programme — the affiliate's own dashboard, and the staff side that
 * reviews applications, approves commissions and pays them (README §25).
 *
 * Three separate permissions, because these are three separate decisions:
 *   affiliate.application.approve  — admin: may this person join?
 *   affiliate.commission.approve   — management: is this commission legitimate?
 *   affiliate.payout.process       — finance: send the money.
 * The person who approves an obligation should not also be the one who settles it.
 */
final class AffiliateController
{
    // ------------------------------------------------------------ affiliate side

    /**
     * Public information page — what the programme is, how it works, the current
     * rate. Same role as JobController::home() on the careers portal: the thing a
     * visitor who is not signed in yet actually lands on.
     */
    public static function home(): void
    {
        $main = View::render('affiliate/home', [
            'enabled'   => Affiliate::enabled(),
            'rate'      => Affiliate::defaultRateBps(),
            'minPayout' => Affiliate::minPayout(),
            'cookieDays' => Affiliate::cookieDays(),
        ]);
        View::affiliateShell('home', 'Affiliate Programme', $main,
            'Earn a commission for every person you refer to UltrAdemy who enrols or subscribes.');
    }

    /** The signed-in user's own affiliate dashboard, or the application form. */
    public static function mine(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $affiliate = Affiliate::forUser($userId);

        if ($affiliate === null || $affiliate['status'] !== 'approved') {
            $main = View::render('affiliate/apply', [
                'affiliate' => $affiliate,
                'enabled'   => Affiliate::enabled(),
                'rate'      => Affiliate::defaultRateBps(),
            ]);
            View::affiliateShell('dashboard', 'Affiliate Programme', $main);
            return;
        }

        $main = View::render('affiliate/mine', [
            'affiliate'   => $affiliate,
            'stats'       => Affiliate::stats((int) $affiliate['id']),
            'referrals'   => Affiliate::referralsFor((int) $affiliate['id']),
            'commissions' => Affiliate::commissionsFor((int) $affiliate['id']),
            'payouts'     => Affiliate::payouts((int) $affiliate['id']),
            'link'        => rtrim((string) config('app.url'), '/') . '/r.php?c=' . $affiliate['code'],
            'minPayout'   => Affiliate::minPayout(),
            'payable'     => Affiliate::payableBalance((int) $affiliate['id']),
        ]);
        View::affiliateShell('dashboard', 'Affiliate Programme', $main);
    }

    public static function apply(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $result = Affiliate::apply(
            (int) Auth::id(),
            trim((string) ($_POST['motivation'] ?? '')),
            trim((string) ($_POST['payout_method'] ?? '')),
            trim((string) ($_POST['payout_details'] ?? ''))
        );
        Session::flash($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Application submitted. We will let you know once it has been reviewed.' : (string) $result['error']);
        // Relative — this is only ever reached via the affiliate portal's own front
        // controller (public/affiliate/app.php) now, never the main app.
        header('Location: app.php');
        exit;
    }

    /**
     * Local rather than Auth::requireLogin(): that helper redirects to
     * app_url('login.php'), the MAIN app's login on the main session, which is the
     * wrong place to send someone back to this portal's own session.
     */
    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            header('Location: login.php');
            exit;
        }
    }

    // ---------------------------------------------------------------- staff side

    public static function index(): void
    {
        Auth::requirePermission('affiliate.referral.view_any');
        $status = (string) ($_GET['status'] ?? '');
        $main = View::render('affiliate/admin', [
            'affiliates' => Affiliate::listing($status !== '' ? $status : null),
            'status'     => $status,
            'enabled'    => Affiliate::enabled(),
            'canApprove' => Auth::can('affiliate.application.approve'),
        ]);
        View::shell('affiliateadmin', 'Affiliates', $main);
    }

    public static function show(): void
    {
        Auth::requirePermission('affiliate.referral.view_any');
        $affiliate = Affiliate::find((int) ($_GET['id'] ?? 0));
        if (!$affiliate) {
            http_response_code(404);
            echo 'Affiliate not found.';
            return;
        }
        $main = View::render('affiliate/show', [
            'affiliate'   => $affiliate,
            'stats'       => Affiliate::stats((int) $affiliate['id']),
            'referrals'   => Affiliate::referralsFor((int) $affiliate['id']),
            'commissions' => Affiliate::commissionsFor((int) $affiliate['id']),
            'payouts'     => Affiliate::payouts((int) $affiliate['id']),
            'payable'     => Affiliate::payableBalance((int) $affiliate['id']),
            'canApprove'  => Auth::can('affiliate.application.approve'),
            'canPay'      => Auth::can('affiliate.payout.process'),
            'minPayout'   => Affiliate::minPayout(),
        ]);
        View::shell('affiliateadmin', $affiliate['name'] ?: 'Affiliate', $main);
    }

    public static function decide(): void
    {
        Auth::requirePermission('affiliate.application.approve');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $rate = ($_POST['commission_rate'] ?? '') !== ''
            ? (int) round(((float) $_POST['commission_rate']) * 100)   // percent → basis points
            : null;

        $error = Affiliate::decide($id, (string) $_POST['status'], trim((string) ($_POST['note'] ?? '')), $rate);
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Updated.' : $error);
        header('Location: app.php?r=affiliate.show&id=' . $id);
        exit;
    }

    /** Commissions awaiting a decision, across every affiliate. */
    public static function commissions(): void
    {
        Auth::requirePermission('affiliate.commission.approve');
        $main = View::render('affiliate/commissions', [
            'commissions' => Affiliate::pendingCommissions(),
        ]);
        View::shell('affiliatecommissions', 'Commission Approvals', $main);
    }

    public static function decideCommission(): void
    {
        Auth::requirePermission('affiliate.commission.approve');
        Csrf::requireValid();
        $error = Affiliate::decideCommission(
            (int) $_POST['id'],
            ($_POST['decision'] ?? '') === 'approve',
            trim((string) ($_POST['reason'] ?? ''))
        );
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Recorded.' : $error);
        header('Location: app.php?r=affiliate.commissions');
        exit;
    }

    public static function createPayout(): void
    {
        Auth::requirePermission('affiliate.payout.process');
        Csrf::requireValid();
        $affiliateId = (int) $_POST['affiliate_id'];
        $result = Affiliate::createPayout($affiliateId, trim((string) ($_POST['method'] ?? '')), trim((string) ($_POST['note'] ?? '')));
        Session::flash($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Payout raised. Send the money, then mark it paid with the bank reference.' : (string) $result['error']);
        header('Location: app.php?r=affiliate.show&id=' . $affiliateId);
        exit;
    }

    public static function markPaid(): void
    {
        Auth::requirePermission('affiliate.payout.process');
        Csrf::requireValid();
        $error = Affiliate::markPayoutPaid((int) $_POST['payout_id'], trim((string) ($_POST['bank_reference'] ?? '')));
        Session::flash($error === '' ? 'success' : 'error', $error === '' ? 'Marked paid.' : $error);
        header('Location: app.php?r=affiliate.show&id=' . (int) $_POST['affiliate_id']);
        exit;
    }
}
