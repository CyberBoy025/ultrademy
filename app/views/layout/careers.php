<?php
/**
 * Careers portal page chrome.
 *
 * Careers is a public marketing surface — a stranger reaches it without an account,
 * and the ultrademy.com footer links straight to it — so it wears the *public site's*
 * template: site.css tokens, the .site-header / .site-footer components, and the shared
 * theme toggle (docs/architecture/16-careers-portal.md §1). It is still not the LMS
 * shell; that separation, and the separate session cookie behind it, are unchanged.
 *
 * Cross-app links go through app_url() rather than "../index.php" (config/bootstrap.php
 * §app_url) so they survive the subdomain cutover. The two asset paths below stay
 * relative because every careers entry point sits at exactly one depth —
 * careers/{index,app,login,register,logout}.php — and no careers URL nests deeper.
 *
 * @var string $active one of: home|jobs|dashboard|applications|profile|notifications
 * @var string $title @var string $main @var string $description SEO meta (brief §43); '' to omit
 */
$successMsg = Session::flash('success');
$errorMsg   = Session::flash('error');
$user       = Auth::check() ? Auth::user() : null;

$navItems = ['home' => ['Home', 'app.php'], 'jobs' => ['Open Positions', 'app.php?r=jobs']];
if ($user) {
    $navItems['dashboard']     = ['My Dashboard', 'app.php?r=dashboard'];
    $navItems['applications']  = ['My Applications', 'app.php?r=applications'];
    $navItems['profile']       = ['My Profile', 'app.php?r=profile.personal'];
    $navItems['notifications'] = ['Notifications', 'app.php?r=notifications'];
}
$unread = $user ? Notify::unreadCount((int) $user['id'], 'recruitment') : 0;
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

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="app.php" aria-label="UltrAdemy Careers — home">
      <img class="brand-logo on-light" src="../img/black-logo.png" alt="" width="78" height="32">
      <img class="brand-logo on-dark" src="../img/white-logo.png" alt="" width="78" height="32" style="display:none">
      <span class="badge">Careers</span>
    </a>

    <nav class="nav-main" id="primaryNav" aria-label="Primary">
      <?php foreach ($navItems as $key => [$label, $href]): ?>
        <a href="<?= View::e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
          <?= View::e($label) ?><?php if ($key === 'notifications' && $unread > 0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
      <a href="<?= View::e(app_url('index.php')) ?>">UltrAdemy.com</a>
    </nav>

    <div class="header-actions">
      <button type="button" class="theme-toggle" id="themeToggle" aria-pressed="false">
        <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
        <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <span class="sr-only">Toggle dark mode</span>
      </button>
      <?php if ($user): ?>
        <?php /* First name only, not the full name: this row already carries seven nav
                 items inside a 1200px container, and a long full name ("System
                 Administrator") pushed it past the edge, which showed up as nav labels
                 breaking mid-word. The dashboard still greets them in full. */ ?>
        <span class="nav-user"><?= View::e(($user['first_name'] ?? '') ?: (string) strstr((string) $user['email'], '@', true) ?: $user['email']) ?></span>
        <?php /* .btn-ghost, not .btn-secondary: site.css:142 hides .header-actions .btn-secondary
                 below 960px, which is acceptable for a Login link and not for Log Out. */ ?>
        <a class="btn btn-ghost btn-sm" href="logout.php">Log Out</a>
      <?php else: ?>
        <a class="btn btn-secondary btn-sm" href="login.php">Sign In</a>
        <a class="btn btn-primary btn-sm" href="register.php">Create Account</a>
      <?php endif; ?>
      <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="primaryNav">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <span class="sr-only">Menu</span>
      </button>
    </div>
  </div>
</header>

<?php if ($successMsg || $errorMsg): ?>
<div class="wrap">
  <?php if ($successMsg): ?><div class="flash flash-success" role="status"><?= View::e($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="flash flash-error" role="alert"><?= View::e($errorMsg) ?></div><?php endif; ?>
</div>
<?php endif; ?>

<main><?= $main ?></main>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-brand">
        <a class="brand" href="app.php" aria-label="UltrAdemy Careers — home">
          <img class="brand-logo on-light" src="../img/black-logo.png" alt="" width="78" height="32">
          <img class="brand-logo on-dark" src="../img/white-logo.png" alt="" width="78" height="32" style="display:none">
          <span class="badge">Careers</span>
        </a>
        <p>Roles across technology, education, administration and centre operations — at Gwagwalada Hub, Kubwa Hub, or remote.</p>
        <div class="footer-social">
          <a href="#" aria-label="UltrAdemy on Facebook"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v6h3v-6h3l1-3h-4V9c0-.6.4-1 1-1Z"/></svg></a>
          <a href="#" aria-label="UltrAdemy on Instagram"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
          <a href="#" aria-label="UltrAdemy on LinkedIn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 11v5M8 8v.01M12 16v-3c0-1.1.9-2 2-2s2 .9 2 2v3M12 13v3"/></svg></a>
          <a href="#" aria-label="UltrAdemy on X"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l16 16M20 4 4 20"/></svg></a>
        </div>
      </div>

      <div class="footer-col">
        <h5>Careers</h5>
        <ul>
          <li><a href="app.php">Careers Home</a></li>
          <li><a href="app.php?r=jobs">Open Positions</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Your Account</h5>
        <ul>
          <?php if ($user): ?>
            <li><a href="app.php?r=dashboard">My Dashboard</a></li>
            <li><a href="app.php?r=applications">My Applications</a></li>
            <li><a href="app.php?r=profile.personal">My Profile</a></li>
            <li><a href="app.php?r=notifications">Notifications</a></li>
          <?php else: ?>
            <li><a href="login.php">Sign In</a></li>
            <li><a href="register.php">Create Account</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h5>UltrAdemy</h5>
        <ul>
          <li><a href="<?= View::e(app_url('index.php')) ?>">Home</a></li>
          <li><a href="<?= View::e(app_url('programmes.php')) ?>">Programmes</a></li>
          <li><a href="<?= View::e(app_url('centres.php')) ?>">Centres</a></li>
          <li><a href="<?= View::e(app_url('about.php')) ?>">About</a></li>
          <li><a href="<?= View::e(app_url('contact.php')) ?>">Contact</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Centres</h5>
        <ul>
          <li><a href="<?= View::e(app_url('centres.php#gwagwalada')) ?>">Gwagwalada Hub</a></li>
          <li><a href="<?= View::e(app_url('centres.php#kubwa')) ?>">Kubwa Hub</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> UltrAdemy. Gwagwalada Hub &middot; Kubwa Hub.</span>
      <div class="legal">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms &amp; Conditions</a>
        <a href="#">Cookie Policy</a>
      </div>
    </div>
  </div>
</footer>

<script src="../js/site.js"></script>
</body>
</html>
