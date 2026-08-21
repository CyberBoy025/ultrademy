<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_affiliate_session');

// Unlike careers/app.php, every route here needs an account — there is no anonymous
// browsing equivalent to a job listing. Auth::requireLogin() is not used because it
// hard-redirects to app_url('login.php') (the MAIN app's login, main session cookie);
// this portal needs its own local login.php, on its own session.
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$route = (string) ($_GET['r'] ?? 'dashboard');

match ($route) {
    'dashboard' => AffiliateController::mine(),
    'apply'     => AffiliateController::apply(),

    default => (function () {
        http_response_code(404);
        echo '<p style="font:14px system-ui;padding:40px">Page not found. <a href="app.php">Back to your affiliate dashboard</a></p>';
    })(),
};
