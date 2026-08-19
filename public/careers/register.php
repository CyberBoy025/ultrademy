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
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/careers.css">
</head>
<body>
<div class="auth-shell">
  <div class="auth-brand-pane">
    <a class="cw-brand" href="app.php">UltrAdemy <span>Careers</span></a>
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
        <div class="auth-alert">
          <?php foreach ($errors as $e): ?><div><?= View::e($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="register.php">
        <?= Csrf::field() ?>
        <div class="cw-field-row">
          <label class="cw-field">
            <span>First name</span>
            <input type="text" name="first_name" value="<?= View::e($old['first_name']) ?>" required>
          </label>
          <label class="cw-field">
            <span>Last name</span>
            <input type="text" name="last_name" value="<?= View::e($old['last_name']) ?>" required>
          </label>
        </div>
        <label class="cw-field">
          <span>Email address</span>
          <input type="email" name="email" value="<?= View::e($old['email']) ?>" required>
        </label>
        <label class="cw-field">
          <span>Password</span>
          <input type="password" name="password" placeholder="At least 8 characters" required>
        </label>
        <label class="cw-check">
          <input type="checkbox" name="agree">
          <span>I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a></span>
        </label>
        <?= Captcha::widget() ?>
        <button type="submit" class="cw-btn cw-btn-primary cw-btn-block">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
      <p class="auth-switch"><a href="app.php">&larr; Back to job listings</a></p>
    </div>
  </div>
</div>
</body>
</html>
