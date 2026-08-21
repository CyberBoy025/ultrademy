<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
$active = 'about';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>About Us — UltrAdemy</title>
<meta name="description" content="UltrAdemy combines physical training hubs with online learning to deliver practical, career-focused programmes.">
<link rel="canonical" href="/about.php">
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
    <div class="breadcrumb"><a href="index.php">Home</a> / About</div>
    <span class="eyebrow">About UltrAdemy</span>
    <h1>Practical training, built for real outcomes</h1>
    <p>UltrAdemy brings physical training hubs and online learning together in one platform — so people can build real, career-ready skills however works best for them.</p>
  </div>
</section>

<section class="section">
  <div class="wrap grid grid-2" style="gap:56px;align-items:start">
    <div>
      <span class="eyebrow">Our Mission</span>
      <h2 style="font-size:24px;margin:10px 0 14px">Make practical training accessible</h2>
      <p class="muted">To deliver hands-on, career-focused training that combines the discipline of physical instruction with the flexibility of online learning — accessible to anyone ready to build a new skill.</p>
    </div>
    <div>
      <span class="eyebrow">Our Vision</span>
      <h2 style="font-size:24px;margin:10px 0 14px">One platform, every learner</h2>
      <p class="muted">A learning and training platform where students, applicants, affiliates and organisations all operate within one connected UltrAdemy account — growing with every centre, programme and service we add.</p>
    </div>
  </div>
</section>

<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Our Approach</span>
      <h2>How we train</h2>
    </div>
    <div class="grid grid-3">
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10-3-3"/></svg></div>
        <h3>Practical first</h3>
        <p>Programmes are structured around real, applied work — not just lectures.</p>
      </div>
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg></div>
        <h3>Two learning environments</h3>
        <p>Physical hubs for hands-on instruction, and an online platform for flexible study — often combined.</p>
      </div>
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 12h6M12 9v6"/><circle cx="12" cy="12" r="9"/></svg></div>
        <h3>Structured progress</h3>
        <p>Clear course outlines, assessments and certification, so progress is always visible.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap grid grid-2" style="align-items:center;gap:48px">
    <div>
      <span class="eyebrow">Physical &amp; Online</span>
      <h2 style="font-size:26px;margin:10px 0 14px">Two hubs, and growing</h2>
      <p class="muted" style="margin-bottom:20px">UltrAdemy currently operates two physical training hubs — Gwagwalada and Kubwa — alongside our online learning platform. The platform is built to support more centres, more programmes and more services as UltrAdemy grows.</p>
      <a href="centres.php" class="btn btn-secondary">Visit Our Centres</a>
    </div>
    <div class="hero-visual" style="aspect-ratio:4/3">
      <div class="grid-lines"></div><div class="glass"></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <h2>Want to know more?</h2>
      <p>Reach out and our team will be glad to answer your questions.</p>
      <div class="hero-cta">
        <a href="contact.php" class="btn btn-primary">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
