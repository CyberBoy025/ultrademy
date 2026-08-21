<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_affiliate_session');

// Unlike the first version of this file, not every route needs an account: `home` is
// the public information page (what the programme is, how it works, current rate),
// same as careers/app.php's own 'home' is public. `dashboard` and `apply` still need
// a login, enforced locally in AffiliateController (see its requireLogin()) rather
// than blanket here — Auth::requireLogin() itself is never used on this portal because
// it hard-redirects to app_url('login.php'), the MAIN app's login on the main session.
$route = (string) ($_GET['r'] ?? 'home');

match ($route) {
    'home'      => AffiliateController::home(),
    'dashboard' => AffiliateController::mine(),
    'apply'     => AffiliateController::apply(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to the Affiliate Programme</a></p>';
    })(),
};
