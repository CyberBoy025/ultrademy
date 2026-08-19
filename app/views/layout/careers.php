<?php
/** @var string $active @var string $title @var string $main @var string $description */
$successMsg = Session::flash('success');
$errorMsg   = Session::flash('error');
$user       = Auth::check() ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title) ?> — UltrAdemy Careers</title>
<?php if ($description !== ''): ?><meta name="description" content="<?= View::e($description) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/careers.css">
</head>
<body>

<header class="cw-header">
  <div class="cw-header-inner">
    <a class="cw-brand" href="app.php">UltrAdemy <span>Careers</span></a>
    <nav class="cw-nav" aria-label="Main">
      <a href="app.php" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
      <a href="app.php?r=jobs" <?= $active === 'jobs' ? 'aria-current="page"' : '' ?>>Open Positions</a>
      <?php if ($user): ?>
        <a href="app.php?r=dashboard" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>>My Dashboard</a>
        <a href="app.php?r=applications" <?= $active === 'applications' ? 'aria-current="page"' : '' ?>>My Applications</a>
        <a href="app.php?r=profile.personal" <?= $active === 'profile' ? 'aria-current="page"' : '' ?>>My Profile</a>
        <?php $unread = Notify::unreadCount((int) $user['id'], 'recruitment'); ?>
        <a href="app.php?r=notifications" <?= $active === 'notifications' ? 'aria-current="page"' : '' ?>>Notifications<?php if ($unread > 0): ?><span class="cw-nav-badge"><?= $unread ?></span><?php endif; ?></a>
      <?php endif; ?>
    </nav>
    <div class="cw-nav-actions">
      <?php if ($user): ?>
        <span style="font-size:0.85rem;color:var(--cw-ink-soft)"><?= View::e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email']) ?></span>
        <a class="cw-btn cw-btn-outline cw-btn-sm" href="logout.php">Log Out</a>
      <?php else: ?>
        <a class="cw-btn cw-btn-outline cw-btn-sm" href="login.php">Sign In</a>
        <a class="cw-btn cw-btn-primary cw-btn-sm" href="register.php">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($successMsg || $errorMsg): ?>
<div class="cw-flash">
  <?php if ($successMsg): ?><div class="cw-flash-msg cw-flash-success"><?= View::e($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="cw-flash-msg cw-flash-error"><?= View::e($errorMsg) ?></div><?php endif; ?>
</div>
<?php endif; ?>

<main><?= $main ?></main>

<footer class="cw-footer">
  <div class="cw-footer-inner">
    <p>&copy; <?= date('Y') ?> UltrAdemy. Gwagwalada Hub &middot; Kubwa Hub.</p>
    <div class="cw-footer-links">
      <a href="app.php">Careers Home</a>
      <a href="app.php?r=jobs">Open Positions</a>
      <a href="<?= View::e(app_url('index.php')) ?>">UltrAdemy.com</a>
    </div>
  </div>
</footer>

</body>
</html>
