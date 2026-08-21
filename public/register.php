<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();

if (Auth::check()) {
    header('Location: app.php');
    exit;
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::verify()) {
    // A tab left open long enough for PHP's session GC to reap it (session.gc_maxlifetime,
    // 24 min by default) carries a token that no longer matches anything server-side.
    // Re-rendering with a fresh one (Csrf::field() below) lets a single retry succeed,
    // rather than dead-ending on Csrf::requireValid()'s bare 403 with no way back in.
    $errors[] = 'Your session expired. Please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $errors[] = 'An account with that email already exists.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!$agreed) {
        $errors[] = 'You must agree to the Terms & Conditions and Privacy Policy.';
    }

    if (!$errors) {
        Database::query(
            "INSERT INTO users (email, password_hash, status, email_verified_at) VALUES (:e,:h,'active',NOW())",
            ['e' => $old['email'], 'h' => password_hash($password, PASSWORD_DEFAULT)]
        );
        $userId = Database::lastInsertId();
        Database::query('INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (:u,:f,:l)', [
            'u' => $userId, 'f' => $old['first_name'], 'l' => $old['last_name'],
        ]);
        // No role granted here on purpose — README §49/RBAC doc §2: student, applicant and
        // affiliate roles are granted automatically when the corresponding record (an
        // enrolment, an application, an affiliate signup) is created, not at bare registration.
        Audit::log('user.registered', 'users', $userId, null, ['email' => $old['email']]);

        // Attribute the signup if they arrived through an affiliate link. Fails silently
        // on every rejection path (already referred, self-referral, programme closed) —
        // a registration form is not the place to explain a refused referral.
        Affiliate::attributeRegistration($userId, $_COOKIE[Affiliate::COOKIE] ?? null);

        Auth::attempt($old['email'], $password);
        header('Location: app.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Account — UltrAdemy</title>
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
    <a class="brand" href="index.php" aria-label="UltrAdemy — home"><img class="brand-logo" src="img/white-logo.png" alt="" width="171" height="32"></a>
    <div class="quote">
      <h2>Create your UltrAdemy account.</h2>
      <p>One universal account gives you access to programme applications, online learning, payments and — if you choose — the affiliate programme.</p>
      <div class="role-tags"><span>Physical Training</span><span>Online Learning</span><span>Hybrid</span></div>
    </div>
    <p class="cap" style="color:rgba(255,255,255,.75)">&copy; <?= date('Y') ?> UltrAdemy</p>
  </div>

  <div class="auth-main">
    <div class="auth-card">
      <h1>Create your account</h1>
      <p>Start your UltrAdemy journey — one account, every service.</p>

      <?php if ($errors): ?>
        <div class="card" style="border-color:var(--error);margin-bottom:16px;padding:12px 14px">
          <?php foreach ($errors as $e): ?><div><span style="color:var(--error);font-weight:600">!</span> <?= View::e($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="register.php">
        <?= Csrf::field() ?>
        <div class="field-row">
          <div class="field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="<?= View::e($old['first_name']) ?>" required>
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="<?= View::e($old['last_name']) ?>" required>
          </div>
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= View::e($old['email']) ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
        </div>
        <div class="form-foot" style="justify-content:flex-start;gap:8px">
          <label style="display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--text-2)">
            <input type="checkbox" name="agree" style="width:auto;margin-top:2px">
            I agree to the <a href="#" style="color:var(--brand-cyan-text);font-weight:600">Terms &amp; Conditions</a> and <a href="#" style="color:var(--brand-cyan-text);font-weight:600">Privacy Policy</a>
          </label>
        </div>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center;padding:12px">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
      <p class="auth-switch" style="margin-top:6px"><a href="index.php">← Back to home</a></p>
    </div>
  </div>
</div>

<script src="js/shell.js"></script>
</body>
</html>
