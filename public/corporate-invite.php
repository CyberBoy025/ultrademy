<?php
/**
 * A nominated employee accepts their invitation and gets an account.
 *
 * Unauthenticated by necessity — the person has no account yet, which is the point. The
 * link carries a 128-bit token; the token is cleared on acceptance so it cannot be reused
 * or passed on.
 *
 * If an account already exists for the address, its password is NOT changed here. Letting
 * an invitation set the password of an existing account would be an account takeover:
 * anyone who could get an employer to nominate an address could seize the account behind
 * it. They set a password only when the account is being created, or was a placeholder
 * that has never been signed into.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();

$token = trim((string) ($_GET['t'] ?? ($_POST['token'] ?? '')));
$participant = $token !== '' ? Corporate::findParticipantByToken($token) : null;
$error = null;
$done = false;

if ($participant === null) {
    http_response_code(404);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!RateLimit::attempt('corporate_invite', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 10, 3600)) {
        $error = 'Too many attempts. Please try again later.';
    } else {
        $result = Corporate::acceptInvitation($token, (string) ($_POST['password'] ?? ''));
        if ($result['ok']) {
            $done = true;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your training invitation — UltrAdemy</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/site.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>
<?php $active = ''; require __DIR__ . '/../app/views/partials/header.php'; ?>

<section class="section">
  <div class="wrap" style="max-width:520px">
    <div class="card" style="padding:32px">
      <?php if ($participant === null): ?>
        <h1 style="font-size:22px;margin-bottom:10px">This invitation link isn't valid</h1>
        <p class="muted">It may have already been used, or been reissued. Ask whoever organised your training to send you a fresh link.</p>

      <?php elseif ($done): ?>
        <h1 style="font-size:22px;margin-bottom:10px;color:var(--success)">You're enrolled</h1>
        <p style="font-size:15px;line-height:1.7">
          Welcome to <strong><?= View::e($participant['contract_title']) ?></strong>, arranged by
          <?= View::e($participant['org_name']) ?>. Sign in to see your course.
        </p>
        <p style="margin-top:20px"><a class="btn btn-primary" href="login.php">Sign in</a></p>

      <?php else: ?>
        <h1 style="font-size:22px;margin-bottom:8px">You've been enrolled by <?= View::e($participant['org_name']) ?></h1>
        <p class="muted" style="font-size:14px;margin-bottom:20px">
          <?= View::e($participant['contract_title']) ?>. Choose a password to activate your account —
          it's yours, not your employer's, and you keep it if you move on.
        </p>

        <?php if ($error): ?>
          <div class="card" style="border-left:3px solid var(--error);padding:12px 14px;margin-bottom:18px">
            <p style="font-size:13px;margin:0"><?= View::e($error) ?></p>
          </div>
        <?php endif; ?>

        <form method="post" action="corporate-invite.php">
          <?= Csrf::field() ?><input type="hidden" name="token" value="<?= View::e($token) ?>">
          <div class="field"><label>Your email</label><input type="email" value="<?= View::e($participant['email']) ?>" disabled></div>
          <div class="field">
            <label>Choose a password</label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
            <span class="muted" style="font-size:12px">At least 8 characters.</span>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">Activate my account</button>
        </form>
        <p class="muted" style="font-size:12px;margin-top:14px">
          Already have an UltrAdemy account with this address? Use your existing password —
          activating here won't change it.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
<script src="js/site.js"></script>
</body>
</html>
