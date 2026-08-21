<?php
require __DIR__ . '/../../config/bootstrap.php';
Session::start('ultrademy_careers_session');

if (Auth::check()) {
    header('Location: app.php?r=dashboard');
    exit;
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $old['first_name'] = trim((string) ($_POST['first_name'] ?? ''));
    $old['last_name']  = trim((string) ($_POST['last_name'] ?? ''));
    $old['email']      = trim((string) ($_POST['email'] ?? ''));
    $password          = (string) ($_POST['password'] ?? '');
    $agreed            = isset($_POST['agree']);

    if ($old['first_name'] === '' || $old['last_name'] === '') {
        $errors[] = 'Enter your first and last name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif (User::findByEmail($old['email'])) {
        // Deliberately the SAME users table as the main platform (brief §4/§10) — someone
        // with an existing UltrAdemy account should sign in, not register a duplicate.
        $errors[] = 'An account with that email already exists — sign in instead.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!$agreed) {
        $errors[] = 'You must agree to the Terms & Conditions and Privacy Policy.';
    }
    if (!RateLimit::attempt('careers.register', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 5, 3600)) {
        $errors[] = 'Too many attempts from this connection. Please try again later.';
    }
    if (!Captcha::verify()) {
        $errors[] = 'Please complete the verification challenge.';
    }

    if (!$errors) {
        // No role granted here — same convention as the main platform's register.php.
        // `job_applicant` is granted automatically the moment an application is
        // submitted (16-careers-portal.md §8), not at bare registration.
        Database::query(
            "INSERT INTO users (email, password_hash, status, email_verified_at) VALUES (:e,:h,'active',NOW())",
            ['e' => $old['email'], 'h' => password_hash($password, PASSWORD_DEFAULT)]
        );
        $userId = Database::lastInsertId();
        Database::query('INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)', [
            'u' => $userId, 'f' => $old['first_name'], 'l' => $old['last_name'],
        ]);
        Audit::log('careers.user_registered', 'users', $userId, null, ['email' => $old['email']]);

        Auth::attempt($old['email'], $password);
        header('Location: app.php?r=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Account — UltrAdemy Careers</title>
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
      <img class="brand-logo" src="../img/white-logo.png" alt="" width="78" height="32">
      <span class="badge">Careers</span>
    </a>
    <div class="auth-quote">
      <h2>Build your career with UltrAdemy.</h2>
      <p>Create one account to save openings, apply, and track every application in one place.</p>
    </div>
    <p class="auth-copyright">&copy; <?= date('Y') ?> UltrAdemy</p>
  </div>
  <div class="auth-form-pane">
    <div class="auth-card">
      <h1>Create your account</h1>
      <p class="auth-sub">Takes a minute — you'll fill in your full profile before you apply.</p>

      <?php if ($errors): ?>
        <div class="flash flash-error" role="alert">
          <?php foreach ($errors as $e): ?><div><?= View::e($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="register.php">
        <?= Csrf::field() ?>
        <div class="form-grid">
          <label class="field">
            <span>First name</span>
            <input type="text" name="first_name" value="<?= View::e($old['first_name']) ?>" required>
          </label>
          <label class="field">
            <span>Last name</span>
            <input type="text" name="last_name" value="<?= View::e($old['last_name']) ?>" required>
          </label>
        </div>
        <label class="field">
          <span>Email address</span>
          <input type="email" name="email" value="<?= View::e($old['email']) ?>" required>
        </label>
        <label class="field">
          <span>Password</span>
          <input type="password" name="password" placeholder="At least 8 characters" required>
        </label>
        <label class="check">
          <input type="checkbox" name="agree">
          <span>I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a></span>
        </label>
        <div class="captcha-slot"><?= Captcha::widget() ?></div>
        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
      <p class="auth-switch"><a href="app.php">&larr; Back to job listings</a></p>
    </div>
  </div>
</div>
<script src="../js/site.js"></script>
</body>
</html>
