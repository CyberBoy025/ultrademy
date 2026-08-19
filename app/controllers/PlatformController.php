<?php
declare(strict_types=1);

final class PlatformController
{
    public static function settings(): void
    {
        Auth::requirePermission('platform.setting.update');
        $main = View::render('settings/index', ['settings' => Setting::all()]);
        View::shell('settings', 'Settings', $main);
    }

    public static function settingsUpdate(): void
    {
        Auth::requirePermission('platform.setting.update');
        Csrf::requireValid();
        $key = (string) $_POST['key'];
        $value = (string) $_POST['value'];
        Setting::set($key, $value);
        Audit::log('setting.updated', 'settings', 0, null, ['key' => $key]);
        Session::flash('success', "\"$key\" updated.");
        header('Location: app.php?r=settings');
        exit;
    }

    public static function audit(): void
    {
        Auth::requirePermission('platform.audit.view');
        $main = View::render('audit/index', ['logs' => AuditLog::recent(100), 'total' => AuditLog::count()]);
        View::shell('audit', 'Audit Log', $main);
    }
}
