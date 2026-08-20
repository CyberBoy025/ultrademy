<?php
declare(strict_types=1);

final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $path, array $data = []): string
    {
        $file = dirname(__DIR__) . '/views/' . $path . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: $path");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** Full authenticated-app page: head, sidebar (permission-filtered nav), main, optional rail. */
    public static function shell(string $active, string $title, string $main, ?string $rail = null): void
    {
        $navItems = Nav::items();
        $noRail = $rail === null;
        require dirname(__DIR__) . '/views/layout/shell.php';
    }

    /**
     * Careers portal page — the public marketing site's chrome (site.css tokens,
     * .site-header/.site-footer, the shared theme toggle) carrying a careers nav, never
     * the LMS shell (docs/architecture/16-careers-portal.md §1). Careers is the first
     * surface a stranger reaches, so it should look like ultrademy.com rather than invent
     * a third identity; the separation that matters — its own session cookie, its own
     * login — is unaffected by what it looks like. $description feeds the SEO meta tag
     * (brief §43); pass '' on pages that don't need one (e.g. the applicant dashboard).
     */
    public static function careersShell(string $active, string $title, string $main, string $description = ''): void
    {
        require dirname(__DIR__) . '/views/layout/careers.php';
    }
}
