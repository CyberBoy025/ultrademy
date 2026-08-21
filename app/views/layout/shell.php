<?php
/** @var string $active @var string $title @var string $main @var string|null $rail
 *  @var array<int,array<string,mixed>> $navItems @var bool $noRail */
$successMsg = Session::flash('success');
$errorMsg   = Session::flash('error');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($title) ?> — UltrAdemy</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/shell.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<button class="appearance" id="themeToggle" aria-pressed="false" aria-label="Switch appearance">
  <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
  <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
</button>

<div class="shell<?= $noRail ? ' no-rail' : '' ?>">
  <aside class="side">
    <a class="prof" href="app.php?r=profile">
      <span class="pfp" aria-hidden="true"><?= View::e(Auth::initials()) ?></span>
      <span class="prof-t">
        <span class="prof-n"><?= View::e(Auth::name()) ?></span>
        <span class="cap" style="display:block"><?= View::e(implode(' · ', Auth::roles())) ?></span>
      </span>
    </a>

    <nav class="nav" aria-label="Main">
      <?php foreach ($navItems as $item): ?>
      <a href="<?= View::e($item['href']) ?>" <?= $item['key'] === $active ? 'aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><?= $item['icon'] ?></svg>
        <span><?= View::e($item['label']) ?></span>
        <?php if (!empty($item['badge'])): ?>
          <span class="badge"><?= (int) $item['badge'] ?></span>
          <span class="sr-only">unread</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="rule"></div>
    <a class="meta" href="logout.php" style="cursor:pointer">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
      <span>Log Out</span>
    </a>
  </aside>

  <main>
    <?php if ($successMsg): ?><div class="card" style="border-color:var(--success);margin-bottom:16px"><strong style="color:var(--success)">✓</strong> <?= View::e($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="card" style="border-color:var(--error);margin-bottom:16px"><strong style="color:var(--error)">!</strong> <?= View::e($errorMsg) ?></div><?php endif; ?>
    <?= $main ?>
  </main>

  <?php if (!$noRail): ?>
  <aside class="rail"><?= $rail ?></aside>
  <?php endif; ?>
</div>

<script src="js/shell.js"></script>
</body>
</html>
