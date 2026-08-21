<?php
/**
 * Affiliate portal page chrome.
 *
 * Deliberately the LMS shell's own visual language (shell.css), not the public site's
 * (site.css, careers' choice) — an affiliate is never a stranger arriving with no
 * account, so there is no reason to invent a marketing-site face for this. What is
 * separate from the main app is the URL and the session (ultrademy_affiliate_session),
 * never the look. Chrome here is deliberately thin: one page (the dashboard, which
 * doubles as the application form until approved), so there is no sidebar to build.
 *
 * Cross-app links go through app_url() rather than a relative "../..", exactly like
 * careers.php, so they survive AFFILIATE_URL moving to a real subdomain later.
 *
 * @var string $title @var string $main
 */
$successMsg = Session::flash('success');
$errorMsg   = Session::flash('error');
$user       = Auth::check() ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title) ?> — UltrAdemy Affiliates</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/shell.css">
<style>
  /* shell.css has no logo-swap rule of its own (the main app uses an initials avatar,
     never a logo) — same mechanism as site.css's .brand-logo.on-dark, scoped to this
     page only rather than added to the shared stylesheet for one header. */
  .on-dark{display:none}
  [data-theme="dark"] .on-light{display:none}
  [data-theme="dark"] .on-dark{display:block}
</style>
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<button class="appearance" id="themeToggle" aria-pressed="false" aria-label="Switch appearance">
  <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
  <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
</button>

<header style="display:flex;align-items:center;justify-content:space-between;gap:16px;
  max-width:1080px;margin:0 auto;padding:20px 24px">
  <a href="app.php" style="display:flex;align-items:center;gap:10px" aria-label="UltrAdemy Affiliates — home">
    <img src="../img/black-logo.png" alt="" width="78" height="32" class="on-light">
    <img src="../img/white-logo.png" alt="" width="78" height="32" class="on-dark">
    <span class="cap" style="font-weight:700;letter-spacing:.02em">Affiliates</span>
  </a>
  <?php if ($user): ?>
    <div style="display:flex;align-items:center;gap:12px">
      <span class="cap"><?= View::e(Auth::name()) ?></span>
      <a class="btn sm" href="logout.php">Log Out</a>
    </div>
  <?php endif; ?>
</header>

<div style="max-width:1080px;margin:0 auto;padding:0 24px 60px">

  <?php if ($successMsg || $errorMsg): ?>
    <?php if ($successMsg): ?><div class="card" style="border-left:3px solid var(--success);margin-bottom:16px"><?= View::e($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="card" style="border-left:3px solid var(--error);margin-bottom:16px"><?= View::e($errorMsg) ?></div><?php endif; ?>
  <?php endif; ?>

  <?= $main ?>

  <p class="cap" style="text-align:center;margin:32px 0 8px">
    <a href="<?= View::e(app_url('index.php')) ?>">UltrAdemy.com</a>
  </p>
</div>

<script src="../js/shell.js"></script>
</body>
</html>
