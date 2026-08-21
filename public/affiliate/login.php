<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_affiliate_session');

if (Auth::check()) {
    header('Location: app.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::verify()) {
        // Same stale-tab/session-GC recovery as the main login.php — see its own
        // comment for why. A separate session cookie does not change the failure mode.
        $error = 'Your session expired. Please try again.';
    } else {
        // Same two-bucket limiter as careers/login.php: IP catches a script trying many
        // accounts, email catches credential-stuffing against one account from many IPs.
        $ipOk = RateLimit::attempt('affiliate.login', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
        $emailOk = $email === '' || RateLimit::attempt('affiliate.login', $email, 6, 900);

        if (!$ipOk || !$emailOk) {
            $error = 'Too many attempts. Please wait a few minutes and try again.';
        } elseif ($email === '' || $password === '') {
            $error = 'Enter your email and password.';
        } elseif (Auth::attempt($email, $password)) {
            header('Location: app.php');
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — UltrAdemy Affiliates</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/shell.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<button class="appearance" id="themeToggle" aria-pressed="false" aria-label="Switch appearance">
  <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
  <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
</button>

<div class="auth-wrap">
  <div class="auth-side">
    <a class="brand" href="<?= View::e(app_url('index.php')) ?>" aria-label="UltrAdemy — home"><img class="brand-logo" src="../img/white-logo.png" alt="" width="165" height="32"></a>
    <div class="quote">
      <h2>Earn by referring people to UltrAdemy.</h2>
      <p>Your affiliate dashboard, referral link, and commission history — sign in with the same account you use everywhere else on UltrAdemy.</p>
      <div class="role-tags"><span>Referral link</span><span>Commissions</span><span>Payouts</span></div>
    </div>
    <p class="cap" style="color:rgba(255,255,255,.75)">&copy; <?= date('Y') ?> UltrAdemy</p>
  </div>

  <div class="auth-main">
    <div class="auth-card">
      <h1>Sign in</h1>
      <p>Use your UltrAdemy account — the affiliate programme doesn't need a separate one.</p>

      <?php if ($error): ?>
        <div class="card" style="border-color:var(--error);margin-bottom:16px;padding:12px 14px">
          <span style="color:var(--error);font-weight:600">!</span> <?= View::e($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <?= Csrf::field() ?>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= View::e($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center;padding:12px">Sign In</button>
      </form>

      <p class="auth-switch">Don't have an UltrAdemy account yet? <a href="<?= View::e(app_url('register.php')) ?>">Create one</a>, then come back here to apply.</p>
      <p class="auth-switch" style="margin-top:6px"><a href="<?= View::e(app_url('index.php')) ?>">← Back to UltrAdemy.com</a></p>
    </div>
  </div>
</div>

<script src="../js/shell.js"></script>
</body>
</html>
