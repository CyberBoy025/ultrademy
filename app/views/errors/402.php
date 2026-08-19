<?php /** @var string $featureName @var string $feature @var int|null $limit */ ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Upgrade Required — UltrAdemy</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= View::e(app_url('css/shell.css')) ?>">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>
<div style="min-height:100vh;display:grid;place-items:center;padding:24px">
  <div class="card" style="max-width:440px;text-align:center;padding:32px">
    <div style="width:52px;height:52px;border-radius:var(--r-md);background:var(--grad);display:grid;place-items:center;margin:0 auto 18px">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:24px;height:24px"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <?php if ($limit !== null): ?>
      <h1 style="font-size:20px;margin-bottom:10px">You've reached your limit</h1>
      <p class="cap" style="margin-bottom:22px">Your current package allows <?= (int) $limit ?> of <strong><?= View::e($featureName) ?></strong>. Upgrading raises or removes that limit.</p>
    <?php else: ?>
      <h1 style="font-size:20px;margin-bottom:10px">Upgrade to use this</h1>
      <p class="cap" style="margin-bottom:22px"><strong><?= View::e($featureName) ?></strong> isn't included in your current package.</p>
    <?php endif; ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <a class="btn primary" href="<?= View::e(app_url('app.php?r=subscription')) ?>">View Packages</a>
      <a class="btn" href="<?= View::e(app_url('app.php')) ?>">Back to Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
