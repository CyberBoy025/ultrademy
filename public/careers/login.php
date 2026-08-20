<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_careers_session');

if (Auth::check()) {
    header('Location: app.php?r=dashboard');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Two buckets: IP (catches a script trying many accounts) and email (catches
    // credential-stuffing against one account from many IPs). Either exhausted blocks.
    $ipOk = RateLimit::attempt('careers.login', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 900);
    $emailOk = $email === '' || RateLimit::attempt('careers.login', $email, 6, 900);

    if (!$ipOk || !$emailOk) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } elseif (Auth::attempt($email, $password)) {
        header('Location: app.php?r=dashboard');
        exit;
    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In — UltrAdemy Careers</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/site.css">
<link rel="stylesheet" href="css/careers.css">
<script>
  (function () {
    try {
      var t = localStorage.getItem('ultrademy.theme');
      document.documentElement.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
    } catch (e) {}
  })();
</script>
</head>
<body>
<div class="auth-shell">
  <button type="button" class="theme-toggle" id="themeToggle" aria-pressed="false">
    <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
    <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    <span class="sr-only">Toggle dark mode</span>
  </button>
  <div class="auth-brand-pane">
    <a class="brand" href="app.php" aria-label="UltrAdemy Careers — home">
      <img class="brand-logo" src="../img/white-logo.png" alt="" width="165" height="32">
      <span class="badge">Careers</span>
    </a>
    <div class="auth-quote">
      <h2>Welcome back.</h2>
      <p>Sign in to track your applications, save openings, and pick up your profile where you left it.</p>
    </div>
    <p class="auth-copyright">&copy; <?= date('Y') ?> UltrAdemy</p>
  </div>
  <div class="auth-form-pane">
    <div class="auth-card">
      <h1>Sign in</h1>
      <p class="auth-sub">Use your UltrAdemy Careers account.</p>

      <?php if ($error): ?>
        <div class="flash flash-error" role="alert"><?= View::e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <?= Csrf::field() ?>
        <label class="field">
          <span>Email address</span>
          <input type="email" name="email" value="<?= View::e($_POST['email'] ?? '') ?>" required autofocus>
        </label>
        <label class="field">
          <span>Password</span>
          <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
      </form>

      <p class="auth-switch">New to UltrAdemy Careers? <a href="register.php">Create an account</a></p>
      <p class="auth-switch"><a href="app.php">&larr; Back to job listings</a></p>
    </div>
  </div>
</div>
<script src="../js/site.js"></script>
</body>
</html>
