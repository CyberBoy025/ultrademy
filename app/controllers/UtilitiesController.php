<?php
declare(strict_types=1);

final class UtilitiesController
{
    private const PERMISSION = 'platform.utilities.manage';

    public static function index(): void
    {
        Auth::requirePermission(self::PERMISSION);
        $main = View::render('utilities/index', [
            'appDebug'   => (bool) config('app.debug'),
            'forceHttps' => (bool) config('app.force_https'),
            'isProduction' => config('app.env') === 'production',
        ]);
        View::shell('utilities', 'Utilities', $main);
    }

    public static function clearCache(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        if (function_exists('opcache_reset') && opcache_reset()) {
            Audit::log('utilities.cache_cleared', 'utilities', 0);
            Session::flash('success', 'Cache cleared.');
        } else {
            Session::flash('error', 'OPcache is not enabled on this server — nothing to clear.');
        }
        header('Location: app.php?r=utilities');
        exit;
    }

    public static function clearLog(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        $path = ini_get('error_log');
        if ($path && is_file($path) && is_writable($path)) {
            file_put_contents($path, '');
            Audit::log('utilities.log_cleared', 'utilities', 0);
            Session::flash('success', 'Log cleared.');
        } else {
            Session::flash('error', 'No writable PHP error log is configured on this server.');
        }
        header('Location: app.php?r=utilities');
        exit;
    }

    public static function toggleDebug(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        $new = !config('app.debug');
        EnvFile::set('APP_DEBUG', $new ? 'true' : 'false');
        Audit::log('utilities.debug_toggled', 'utilities', 0, ['app_debug' => !$new], ['app_debug' => $new]);
        Session::flash('success', 'App Debug ' . ($new ? 'enabled' : 'disabled') . '.');
        header('Location: app.php?r=utilities');
        exit;
    }

    public static function toggleHttps(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        $new = !config('app.force_https');
        EnvFile::set('FORCE_HTTPS', $new ? 'true' : 'false');
        Audit::log('utilities.force_https_toggled', 'utilities', 0, ['force_https' => !$new], ['force_https' => $new]);
        Session::flash('success', 'Force HTTPS ' . ($new ? 'enabled' : 'disabled') . '.');
        header('Location: app.php?r=utilities');
        exit;
    }

    public static function migrate(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        try {
            $result = Migrator::run();
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: app.php?r=utilities');
            exit;
        }

        Audit::log('utilities.migrated', 'utilities', 0, null, $result);
        Session::flash('success', $result['ran'] === 0
            ? 'Nothing to migrate — already up to date.'
            : "{$result['ran']} migration(s) applied.");
        header('Location: app.php?r=utilities');
        exit;
    }

    /** Demo/dev data only — never available once APP_ENV=production. */
    public static function importDemo(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        if (config('app.env') === 'production') {
            Session::flash('error', 'Import Demo Database is disabled in production.');
            header('Location: app.php?r=utilities');
            exit;
        }

        Seeder::run();
        Audit::log('utilities.demo_imported', 'utilities', 0);
        Session::flash('success', 'Demo database imported.');
        header('Location: app.php?r=utilities');
        exit;
    }

    /** Destructive and irreversible — never available once APP_ENV=production. */
    public static function resetDb(): void
    {
        Auth::requirePermission(self::PERMISSION);
        Csrf::requireValid();

        if (config('app.env') === 'production') {
            Session::flash('error', 'Reset Database is disabled in production.');
            header('Location: app.php?r=utilities');
            exit;
        }

        // No Audit::log here: the reset just dropped audit_logs (and users) along with
        // everything else, so the current actor no longer exists in the freshly rebuilt
        // users table — logging against it would fail the audit_logs FK constraint.
        $result = Migrator::reset();
        Session::flash('success', "Database reset — {$result['dropped']} table(s) dropped, {$result['ran']} migration(s) reapplied. "
            . 'Your account no longer exists — run "php database/seed.php" from the CLI to get a working login back.');
        header('Location: app.php?r=utilities');
        exit;
    }
}
