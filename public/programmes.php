<?php
require __DIR__ . '/../config/bootstrap.php';
$programmes = require __DIR__ . '/../app/views/partials/demo-programmes.php';
$active = 'programmes';
$initialMode = $_GET['mode'] ?? 'All';
$modes = ['All', 'Physical', 'Online', 'Hybrid', 'Corporate'];
if (!in_array($initialMode, $modes, true)) {
    $initialMode = 'All';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Programmes — UltrAdemy</title>
<meta name="description" content="Browse UltrAdemy training programmes across physical, online, hybrid and corporate learning modes.">
<link rel="canonical" href="/programmes.php">
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
    <div class="breadcrumb"><a href="index.php">Home</a> / Programmes</div>
    <span class="eyebrow">Programmes</span>
    <h1>Explore our programmes</h1>
    <p>Practical, career-focused training across technology, business and creative disciplines — physical, online, hybrid and corporate.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="filters" data-filter-bar>
      <?php foreach ($modes as $m): ?>
        <button type="button" class="chip <?= $m === $initialMode ? 'active' : '' ?>" data-mode="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-3">
      <?php foreach ($programmes as $p): ?>
      <article class="card prog-card" data-programme data-mode="<?= htmlspecialchars($p['mode']) ?>" style="<?= $initialMode !== 'All' && $p['mode'] !== $initialMode ? 'display:none' : '' ?>">
        <div class="thumb">
          <span class="badge mode"><?= htmlspecialchars($p['mode']) ?></span>
        </div>
        <div class="card-body">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p><?= htmlspecialchars($p['summary']) ?></p>
          <div class="prog-meta">
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> <?= htmlspecialchars($p['duration']) ?></span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> <?= htmlspecialchars(implode(', ', $p['centres'])) ?></span>
          </div>
          <a href="programme-detail.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-secondary btn-sm btn-block">View Programme</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="cta-band">
      <h2>Don't see what you're looking for?</h2>
      <p>New programmes are added regularly. Get in touch and we'll help you find the right fit.</p>
      <div class="hero-cta">
        <a href="contact.php" class="btn btn-primary">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
