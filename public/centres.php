<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
$active = 'centres';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Our Centres — UltrAdemy</title>
<meta name="description" content="UltrAdemy's physical training hubs at Gwagwalada and Kubwa — facilities, programmes and how to visit.">
<link rel="canonical" href="/centres.php">
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
    <div class="breadcrumb"><a href="index.php">Home</a> / Centres</div>
    <span class="eyebrow">Our Centres</span>
    <h1>Physical training hubs</h1>
    <p>UltrAdemy operates physical training hubs designed for hands-on, instructor-led learning. New centres are added as the network grows.</p>
  </div>
</section>

<section class="section" id="gwagwalada">
  <div class="wrap grid grid-2" style="align-items:center;gap:48px">
    <div class="hero-visual" style="aspect-ratio:4/3">
      <div class="grid-lines"></div><div class="glass"></div>
      <div class="hero-float hero-float-1" style="top:12%;left:8%">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <div><b>Gwagwalada Hub</b><span>Gwagwalada, FCT</span></div>
      </div>
    </div>
    <div>
      <span class="eyebrow">Centre 01</span>
      <h2 style="font-size:28px;margin:10px 0 14px">Gwagwalada Hub</h2>
      <p class="muted" style="margin-bottom:20px">A full physical training environment supporting classroom-based instruction and hands-on practical work, with dedicated computer lab access.</p>
      <div class="centre-facilities" style="margin-bottom:24px">
        <span class="pill">Classrooms</span>
        <span class="pill">Computer Lab</span>
        <span class="pill">Instructor-led programmes</span>
        <span class="pill">On-site support</span>
      </div>
      <div class="stack" style="margin-bottom:24px">
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
          <div><h4>Location</h4><p>Gwagwalada, FCT, Nigeria</p></div>
        </div>
      </div>
      <a href="programmes.php" class="btn btn-primary">See Programmes at This Hub</a>
    </div>
  </div>
</section>

<section class="section section-tight" style="background:var(--color-surface-muted)" id="kubwa">
  <div class="wrap grid grid-2" style="align-items:center;gap:48px">
    <div>
      <span class="eyebrow">Centre 02</span>
      <h2 style="font-size:28px;margin:10px 0 14px">Kubwa Hub</h2>
      <p class="muted" style="margin-bottom:20px">A dedicated training hub supporting practical, cohort-based programmes with a focus on structured, hands-on learning.</p>
      <div class="centre-facilities" style="margin-bottom:24px">
        <span class="pill">Classrooms</span>
        <span class="pill">Computer Lab</span>
        <span class="pill">Instructor-led programmes</span>
        <span class="pill">On-site support</span>
      </div>
      <div class="stack" style="margin-bottom:24px">
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
          <div><h4>Location</h4><p>Kubwa, FCT, Nigeria</p></div>
        </div>
      </div>
      <a href="programmes.php" class="btn btn-primary">See Programmes at This Hub</a>
    </div>
    <div class="hero-visual" style="aspect-ratio:4/3">
      <div class="grid-lines"></div><div class="glass"></div>
      <div class="hero-float hero-float-2" style="bottom:12%;right:8%">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <div><b>Kubwa Hub</b><span>Kubwa, FCT</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <h2>More centres are on the way</h2>
      <p>As UltrAdemy grows, new training hubs will open in other locations — the platform is built to support them from day one.</p>
      <div class="hero-cta">
        <a href="contact.php" class="btn btn-primary">Get in Touch</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
