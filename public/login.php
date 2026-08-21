<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();

if (Auth::check()) {
    header('Location: app.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::verify()) {
        // A tab left open long enough for PHP's session GC to reap it (session.gc_maxlifetime,
        // 24 min by default) carries a token that no longer matches anything server-side.
        // Re-rendering with a fresh one (Csrf::field() below) lets a single retry succeed,
        // rather than dead-ending on Csrf::requireValid()'s bare 403 with no way back in.
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } elseif (Auth::attempt($email, $password)) {
        header('Location: app.php');
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
<title>Login — UltrAdemy</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/shell.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<button class="appearance" id="themeToggle" aria-pressed="false" aria-label="Switch appearance">
  <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
  <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
</button>

<div class="auth-wrap">
  <div class="auth-side">
    <a class="brand" href="index.php" aria-label="UltrAdemy — home"><img class="brand-logo" src="img/white-logo.png" alt="" width="165" height="32"></a>
    <div class="quote">
      <h2>One account. Every UltrAdemy service.</h2>
      <p>Learning, applications, payments and progress — all in one place, whether you train online, at Gwagwalada Hub, or at Kubwa Hub.</p>
      <div class="role-tags"><span>Students</span><span>Applicants</span><span>Affiliates</span><span>Staff</span></div>
    </div>
    <p class="cap" style="color:rgba(255,255,255,.75)">&copy; <?= date('Y') ?> UltrAdemy</p>
  </div>

  <div class="auth-main">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p>Log in to your UltrAdemy account to continue.</p>

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
        <button type="submit" class="btn primary" style="width:100%;justify-content:center;padding:12px">Log In</button>
      </form>

      <p class="auth-switch">Don't have an account? <a href="register.php">Create one</a></p>
      <p class="auth-switch" style="margin-top:6px"><a href="index.php">← Back to home</a></p>
    </div>
  </div>
</div>

<script src="js/shell.js"></script>
</body>
</html>
