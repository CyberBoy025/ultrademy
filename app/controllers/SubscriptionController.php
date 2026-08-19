<?php
declare(strict_types=1);

final class SubscriptionController
{
    /** The signed-in user's own subscription, entitlements, and what else is on offer. */
    public static function mine(): void
    {
        $userId = (int) Auth::id();
        $main = View::render('subscriptions/mine', [
            'active'    => Subscription::activeFor($userId),
            'pending'   => Subscription::pendingFor($userId),
            'history'   => Subscription::historyFor($userId),
            'packages'  => Package::all(true),
            'resolved'  => Entitlements::resolve($userId),
            'allFeatures' => Feature::all(),
            'overrides' => EntitlementOverride::forUser($userId),
        ]);
        View::shell('subscription', 'My Subscription', $main);
    }

    /** Requests a package. Creates a PENDING subscription — it grants nothing yet (§7). */
    public static function request(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $packageId = (int) ($_POST['package_id'] ?? 0);

        $package = Package::find($packageId);
        if (!$package || $package['status'] !== 'active') {
            Session::flash('error', 'That package is not available.');
            header('Location: app.php?r=subscription');
            exit;
        }
        if (Subscription::pendingFor($userId)) {
            Session::flash('error', 'You already have a request awaiting activation.');
            header('Location: app.php?r=subscription');
            exit;
        }

        $id = Subscription::request($userId, $packageId);
        Audit::log('subscription.requested', 'subscriptions', $id, null, ['package_id' => $packageId]);
        Session::flash('success', "Requested {$package['name']}. It activates once payment is confirmed.");
        header('Location: app.php?r=subscription');
        exit;
    }

    public static function cancelMine(): void
    {
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $active = Subscription::activeFor($userId);
        if (!$active) {
            Session::flash('error', 'You have no active subscription to cancel.');
            header('Location: app.php?r=subscription');
            exit;
        }
        Subscription::cancel((int) $active['id']);
        Audit::log('subscription.cancelled', 'subscriptions', (int) $active['id'], null, ['by' => 'user']);
        Session::flash('success', 'Cancelled. You keep access until ' . date('d M Y', strtotime($active['ends_at'])) . '.');
        header('Location: app.php?r=subscription');
        exit;
    }

    // ---------------- admin ----------------

    public static function index(): void
    {
        Auth::requirePermission('subscriptions.subscription.view_any');
        $status = (string) ($_GET['status'] ?? '');
        $main = View::render('subscriptions/index', [
            'subscriptions' => Subscription::all($status ?: null),
            'status' => $status,
            'canActivate' => Auth::can('subscriptions.subscription.activate'),
            'counts' => [
                'active'  => count(Subscription::all('active')),
                'pending' => count(Subscription::all('pending')),
            ],
        ]);
        View::shell('subscriptions', 'Subscriptions', $main);
    }

    public static function activate(): void
    {
        Auth::requirePermission('subscriptions.subscription.activate');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $sub = Subscription::find($id);
        if (!$sub || $sub['status'] !== 'pending') {
            Session::flash('error', 'Only a pending subscription can be activated.');
            header('Location: app.php?r=subscriptions');
            exit;
        }
        Subscription::activate($id);
        Audit::log('subscription.activated', 'subscriptions', $id, ['status' => 'pending'], ['status' => 'active']);
        Session::flash('success', "Activated {$sub['package_name']} for {$sub['email']}.");
        header('Location: app.php?r=subscriptions');
        exit;
    }

    public static function voidSub(): void
    {
        Auth::requirePermission('subscriptions.subscription.activate');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        Subscription::void($id);
        Audit::log('subscription.voided', 'subscriptions', $id, ['status' => 'pending'], ['status' => 'void']);
        Session::flash('success', 'Request voided.');
        header('Location: app.php?r=subscriptions');
        exit;
    }

    // ---------------- overrides ----------------

    public static function overrides(): void
    {
        Auth::requirePermission('subscriptions.override.grant');
        $main = View::render('subscriptions/overrides', [
            'overrides' => EntitlementOverride::all(),
            'features' => Feature::all(),
        ]);
        View::shell('subscriptions', 'Entitlement Overrides', $main);
    }

    public static function overrideStore(): void
    {
        Auth::requirePermission('subscriptions.override.grant');
        Csrf::requireValid();

        $email = trim((string) ($_POST['email'] ?? ''));
        $user = User::findByEmail($email);
        if (!$user) {
            Session::flash('error', "No account found for $email.");
            header('Location: app.php?r=overrides');
            exit;
        }
        $featureId = (int) $_POST['feature_id'];
        $granted = ($_POST['granted'] ?? '1') === '1';
        $rawLimit = trim((string) ($_POST['limit_value'] ?? ''));

        EntitlementOverride::set(
            (int) $user['id'],
            $featureId,
            $granted,
            $rawLimit === '' ? null : max(0, (int) $rawLimit),
            trim((string) ($_POST['expires_at'] ?? '')) ?: null,
            trim((string) ($_POST['reason'] ?? ''))
        );
        Audit::log('entitlement.override_set', 'users', (int) $user['id'], null, [
            'feature_id' => $featureId, 'granted' => $granted,
        ]);
        Session::flash('success', 'Override saved for ' . $email . '.');
        header('Location: app.php?r=overrides');
        exit;
    }

    public static function overrideRemove(): void
    {
        Auth::requirePermission('subscriptions.override.grant');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        EntitlementOverride::remove($id);
        Audit::log('entitlement.override_removed', 'entitlement_overrides', $id);
        Session::flash('success', 'Override removed.');
        header('Location: app.php?r=overrides');
        exit;
    }
}
