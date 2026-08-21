<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
$programmes = require __DIR__ . '/../app/views/partials/demo-programmes.php';
$active = 'programmes';

$slug = $_GET['slug'] ?? '';
$programme = null;
foreach ($programmes as $p) {
    if ($p['slug'] === $slug) {
        $programme = $p;
        break;
    }
}
if (!$programme) {
    $programme = $programmes[0];
}
$related = array_values(array_filter($programmes, fn($p) => $p['slug'] !== $programme['slug']));
$related = array_slice($related, 0, 3);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($programme['name']) ?> — UltrAdemy Programmes</title>
<meta name="description" content="<?= htmlspecialchars($programme['summary']) ?>">
<link rel="canonical" href="/programmes/<?= htmlspecialchars($programme['slug']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/site.css">
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

<?php require __DIR__ . '/../app/views/partials/header.php'; ?>

<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="index.php">Home</a> / <a href="programmes.php">Programmes</a> / <?= htmlspecialchars($programme['name']) ?></div>
    <span class="eyebrow"><?= htmlspecialchars($programme['category']) ?></span>
    <h1><?= htmlspecialchars($programme['name']) ?></h1>
    <p><?= htmlspecialchars($programme['summary']) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap grid grid-2" style="grid-template-columns:2fr 1fr;align-items:start;gap:48px">
    <div>
      <h2 style="font-size:20px;margin-bottom:14px">What you will learn</h2>
      <div class="why-list" style="margin-bottom:36px">
        <?php foreach ($programme['outline'] as $i => $item): ?>
        <div class="why-item">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></div>
          <div><h4>Module <?= $i + 1 ?></h4><p><?= htmlspecialchars($item) ?></p></div>
        </div>
        <?php endforeach; ?>
      </div>

      <h2 style="font-size:20px;margin-bottom:14px">Who is this for?</h2>
      <p class="muted" style="margin-bottom:36px"><?= htmlspecialchars($programme['who_for']) ?></p>

      <h2 style="font-size:20px;margin-bottom:14px">Requirements</h2>
      <ul class="stack">
        <?php foreach ($programme['requirements'] as $req): ?>
        <li style="display:flex;gap:10px;align-items:flex-start;font-size:13.5px;color:var(--color-text-secondary)">
          <svg style="width:15px;height:15px;flex:none;margin-top:3px;color:var(--brand-cyan-text)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
          <?= htmlspecialchars($req) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <aside class="sticky-side">
      <div class="card card-body stack">
        <div class="prog-meta" style="font-size:13px;flex-direction:column;gap:10px;align-items:flex-start">
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> Duration: <?= htmlspecialchars($programme['duration']) ?></span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg> Mode: <?= htmlspecialchars($programme['mode']) ?></span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> Available at: <?= htmlspecialchars(implode(', ', $programme['centres'])) ?></span>
        </div>
        <div class="divider"></div>
        <p class="muted" style="font-size:13px">Fees and upcoming start dates are confirmed during application.</p>
        <a href="register.php" class="btn btn-primary btn-block">Apply Now</a>
        <a href="contact.php" class="btn btn-secondary btn-block">Ask a Question</a>
      </div>
    </aside>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Related</span>
      <h2>Other programmes you might like</h2>
    </div>
    <div class="grid grid-3">
      <?php foreach ($related as $p): ?>
      <article class="card prog-card">
        <div class="thumb"><span class="badge mode"><?= htmlspecialchars($p['mode']) ?></span></div>
        <div class="card-body">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p><?= htmlspecialchars($p['summary']) ?></p>
          <a href="programme-detail.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-secondary btn-sm btn-block">View Programme</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
