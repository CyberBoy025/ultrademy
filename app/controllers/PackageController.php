<?php
declare(strict_types=1);

final class PackageController
{
    public static function index(): void
    {
        Auth::requirePermission('subscriptions.package.manage');
        $packages = Package::all();
        foreach ($packages as &$p) {
            $p['feature_count'] = count(Package::featureMap((int) $p['id']));
            $p['subscribers'] = Package::subscriberCount((int) $p['id']);
        }
        $main = View::render('packages/index', ['packages' => $packages]);
        View::shell('packages', 'Packages', $main);
    }

    public static function store(): void
    {
        Auth::requirePermission('subscriptions.package.manage');
        Csrf::requireValid();

        $name = trim((string) ($_POST['name'] ?? ''));
        $code = strtolower(trim((string) ($_POST['code'] ?? '')));
        if ($name === '' || $code === '') {
            Session::flash('error', 'Name and code are required.');
            header('Location: app.php?r=packages');
            exit;
        }

        $id = Package::create([
            'code' => $code,
            'name' => $name,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price_amount' => ((int) ($_POST['price_naira'] ?? 0)) * 100,
            'currency' => 'NGN',
            'billing_period' => in_array($_POST['billing_period'] ?? '', ['monthly', 'quarterly', 'annual', 'one_off'], true) ? $_POST['billing_period'] : 'monthly',
            'duration_days' => max(1, (int) ($_POST['duration_days'] ?? 30)),
            'status' => 'draft',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        Audit::log('package.created', 'packages', $id, null, ['code' => $code, 'name' => $name]);
        Session::flash('success', "Package \"$name\" created as a draft.");
        header('Location: app.php?r=packages.show&id=' . $id);
        exit;
    }

    public static function show(): void
    {
        Auth::requirePermission('subscriptions.package.manage');
        $id = (int) ($_GET['id'] ?? 0);
        $package = Package::find($id);
        if (!$package) {
            http_response_code(404);
            echo 'Package not found.';
            return;
        }
        $main = View::render('packages/show', [
            'package' => $package,
            'grouped' => Feature::groupedByModule(),
            'featureMap' => Package::featureMap($id),
            'subscribers' => Package::subscriberCount($id),
        ]);
        View::shell('packages', $package['name'], $main);
    }

    public static function update(): void
    {
        Auth::requirePermission('subscriptions.package.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $old = Package::find($id);
        Package::update($id, [
            'name' => trim((string) $_POST['name']),
            'description' => trim((string) $_POST['description']),
            'price_amount' => ((int) $_POST['price_naira']) * 100,
            'billing_period' => $_POST['billing_period'],
            'duration_days' => max(1, (int) $_POST['duration_days']),
            'status' => $_POST['status'],
            'sort_order' => (int) $_POST['sort_order'],
        ]);
        Audit::log('package.updated', 'packages', $id, ['status' => $old['status'] ?? null], ['status' => $_POST['status']]);
        Session::flash('success', 'Package updated.');
        header('Location: app.php?r=packages.show&id=' . $id);
        exit;
    }

    /**
     * Saves the whole feature matrix for one package.
     * This form IS the §7 "administrators should be able to determine what each package
     * provides" requirement — adding a tier or moving a feature between tiers is done
     * here, never in code.
     */
    public static function features(): void
    {
        Auth::requirePermission('subscriptions.package.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];

        $enabled = $_POST['enabled'] ?? [];      // feature_id => "1"
        $limits  = $_POST['limit'] ?? [];        // feature_id => "" | number

        $map = [];
        foreach (array_keys($enabled) as $featureId) {
            $raw = trim((string) ($limits[$featureId] ?? ''));
            $map[(int) $featureId] = $raw === '' ? null : max(0, (int) $raw);
        }

        Package::setFeatures($id, $map);
        Audit::log('package.features_updated', 'packages', $id, null, ['features' => count($map)]);

        // Package features changed → every entitlement set derived from it is stale (§5).
        Entitlements::flush();
        Session::flash('success', count($map) . ' feature(s) saved for this package.');
        header('Location: app.php?r=packages.show&id=' . $id);
        exit;
    }
}
