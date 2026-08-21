<?php
/**
 * Referral link landing: /r.php?c=CODE
 *
 * Sets the attribution cookie and forwards to the site. A dedicated entry point rather
 * than a `?ref=` parameter honoured everywhere, because it keeps the tracking in one
 * auditable file instead of sprinkling cookie writes through every public page.
 */
require __DIR__ . '/../config/bootstrap.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$to = (string) ($_GET['to'] ?? 'index.php');

// Only relative paths within this site — an open redirect here would let an affiliate
// link forward to anywhere while wearing UltrAdemy's domain.
if (!preg_match('#^[a-z0-9._/-]+\.php(\?[^\#]*)?$#i', $to) || str_contains($to, '..') || str_starts_with($to, '/')) {
    $to = 'index.php';
}

if ($code !== '' && Affiliate::enabled() && preg_match('/^[A-Z0-9]{4,20}$/', $code)) {
    $affiliate = Affiliate::findByCode($code);
    if ($affiliate) {
        setcookie(Affiliate::COOKIE, $code, [
            'expires'  => time() + (Affiliate::cookieDays() * 86400),
            'path'     => '/',
            'httponly' => true,   // no script needs to read this
            'samesite' => 'Lax',  // survives the click-through, not a cross-site POST
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
    }
}

header('Location: ' . $to);
exit;
