<?php
require __DIR__ . '/../config/bootstrap.php';
$programmes = require __DIR__ . '/../app/views/partials/demo-programmes.php';
$featured = array_slice($programmes, 0, 3);
$active = 'home';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UltrAdemy — Practical Training &amp; Digital Learning</title>
<meta name="description" content="UltrAdemy is a training and learning platform combining hands-on physical training hubs with flexible online learning. Explore programmes, visit a centre, or start learning today.">
<meta property="og:title" content="UltrAdemy — Practical Training &amp; Digital Learning">
<meta property="og:description" content="Hands-on training at our physical hubs, plus flexible online learning. Explore programmes designed to build real, career-ready skills.">
<meta property="og:type" content="website">
<link rel="canonical" href="/index.php">
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

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Physical Hubs · Online Learning · Hybrid</span>
      <h1>Build your skills.<br>Build your <em>future.</em></h1>
      <p>Practical training and digital learning designed to help you grow — at our physical training hubs, online, or both.</p>
      <div class="hero-cta">
        <a href="programmes.php" class="btn btn-primary">Explore Programmes</a>
        <a href="register.php" class="btn btn-secondary">Get Started</a>
      </div>
      <div class="hero-trust">
        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> Two physical training hubs</div>
        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> Physical, online &amp; hybrid learning</div>
        <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> One account, every service</div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="grid-lines"></div>
      <div class="glass"></div>
      <div class="hero-float hero-float-1">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
        <div><b>6 Programmes</b><span>open for enrolment</span></div>
      </div>
      <div class="hero-float hero-float-2">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <div><b>Gwagwalada &amp; Kubwa</b><span>physical training hubs</span></div>
      </div>
    </div>
  </div>
</section>

<div class="strip">
  <div class="wrap">
    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg> Physical training</div>
    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg> Online learning</div>
    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 9 4.9V17L12 22l-9-5.1V6.9L12 2Z"/></svg> Hybrid programmes</div>
    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M6 21V10l6-6 6 6v11"/></svg> Corporate training</div>
    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg> Affiliate programme</div>
  </div>
</div>

<!-- ============ FEATURED PROGRAMMES ============ -->
<section class="section" id="programmes">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Programmes</span>
      <h2>Explore our programmes</h2>
      <p>A sample of what's currently open for enrolment across our hubs and online.</p>
    </div>
    <div class="grid grid-3">
      <?php foreach ($featured as $p): ?>
      <article class="card prog-card">
        <div class="thumb">
          <span class="badge mode"><?= htmlspecialchars($p['mode']) ?></span>
        </div>
        <div class="card-body">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p><?= htmlspecialchars($p['summary']) ?></p>
          <div class="prog-meta">
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> <?= htmlspecialchars($p['duration']) ?></span>
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> <?= htmlspecialchars($p['centres'][0]) ?><?= count($p['centres']) > 1 ? ' +' . (count($p['centres']) - 1) : '' ?></span>
          </div>
          <a href="programme-detail.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-secondary btn-sm btn-block">View Programme</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:32px">
      <a href="programmes.php" class="btn btn-primary">View All Programmes</a>
    </div>
  </div>
</section>

<!-- ============ TRAINING MODES ============ -->
<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">How you learn</span>
      <h2>Learn the way that works for you</h2>
      <p>Every UltrAdemy programme runs in one or more of these environments.</p>
    </div>
    <div class="grid grid-4">
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <h3>Physical Training</h3>
        <p>Hands-on, instructor-led sessions at our Gwagwalada and Kubwa training hubs.</p>
        <a class="more" href="centres.php">Visit our centres →</a>
      </div>
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg></div>
        <h3>Online Learning</h3>
        <p>Structured courses, lessons and assessments through the UltrAdemy learning platform, wherever you are.</p>
        <a class="more" href="programmes.php?mode=Online">Explore online learning →</a>
      </div>
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="m12 2 9 4.9V17L12 22l-9-5.1V6.9L12 2Z"/></svg></div>
        <h3>Hybrid Learning</h3>
        <p>Combine online coursework with physical sessions at a centre for the best of both.</p>
        <a class="more" href="programmes.php?mode=Hybrid">See hybrid programmes →</a>
      </div>
      <div class="card mode-card">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 21h18M6 21V10l6-6 6 6v11"/></svg></div>
        <h3>Corporate Training</h3>
        <p>Tailored training programmes for organisations, teams and institutions.</p>
        <a class="more" href="contact.php">Talk to us →</a>
      </div>
    </div>
  </div>
</section>

<!-- ============ CENTRES ============ -->
<section class="section" id="centres">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Our Centres</span>
      <h2>Train at one of our physical hubs</h2>
      <p>UltrAdemy currently operates two physical training hubs, with more planned as the network grows.</p>
    </div>
    <div class="grid grid-2">
      <div class="card centre-card">
        <div class="thumb"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <div class="card-body">
          <h3>Gwagwalada Hub</h3>
          <div class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> Gwagwalada, FCT</div>
          <p>A full physical training environment offering classroom-based and practical programmes.</p>
          <div class="centre-facilities">
            <span class="pill">Classrooms</span>
            <span class="pill">Computer Lab</span>
            <span class="pill">Instructor-led</span>
          </div>
          <a href="centres.php#gwagwalada" class="btn btn-secondary btn-sm">Learn More</a>
        </div>
      </div>
      <div class="card centre-card">
        <div class="thumb"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M9 13h1M14 9h1M14 13h1"/></svg></div>
        <div class="card-body">
          <h3>Kubwa Hub</h3>
          <div class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg> Kubwa, FCT</div>
          <p>A dedicated training hub supporting practical, cohort-based programmes.</p>
          <div class="centre-facilities">
            <span class="pill">Classrooms</span>
            <span class="pill">Computer Lab</span>
            <span class="pill">Instructor-led</span>
          </div>
          <a href="centres.php#kubwa" class="btn btn-secondary btn-sm">Learn More</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ WHY ULTRADEMY + HOW IT WORKS ============ -->
<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap grid grid-2" style="align-items:start;gap:56px">
    <div>
      <span class="eyebrow">Why UltrAdemy</span>
      <h2 style="font-size:26px;margin:10px 0 20px">Training built around real outcomes</h2>
      <div class="why-list">
        <div class="why-item">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10-3-3"/></svg></div>
          <div><h4>Practical, project-based learning</h4><p>Programmes are built around real tasks, not just theory.</p></div>
        </div>
        <div class="why-item">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg></div>
          <div><h4>Physical hubs when you need them</h4><p>Hands-on training environments at Gwagwalada and Kubwa.</p></div>
        </div>
        <div class="why-item">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg></div>
          <div><h4>Flexible online learning</h4><p>Structured courses you can follow on your own schedule.</p></div>
        </div>
        <div class="why-item">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M5 21c1-3.5 4-5 7-5s6 1.5 7 5"/></svg></div>
          <div><h4>One account, every service</h4><p>Applications, learning, payments and progress in a single UltrAdemy account.</p></div>
        </div>
      </div>
    </div>
    <div>
      <span class="eyebrow">How it works</span>
      <h2 style="font-size:26px;margin:10px 0 24px">Get started in a few steps</h2>
      <div class="steps">
        <div class="step"><span class="num">01</span><div><h4>Create your account</h4><p>One UltrAdemy account, ready for whichever service you need.</p></div></div>
        <div class="step"><span class="num">02</span><div><h4>Explore programmes</h4><p>Browse by category, mode or centre.</p></div></div>
        <div class="step"><span class="num">03</span><div><h4>Apply or enrol</h4><p>Apply online and track your application status.</p></div></div>
        <div class="step"><span class="num">04</span><div><h4>Start learning</h4><p>Attend at a hub, learn online, or both.</p></div></div>
        <div class="step"><span class="num">05</span><div><h4>Complete your training</h4><p>Assignments, assessments and progress tracking along the way.</p></div></div>
        <div class="step"><span class="num">06</span><div><h4>Achieve your goals</h4><p>Finish with the skills — and certification — to show for it.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ AFFILIATE ============ -->
<section class="section" id="affiliate">
  <div class="wrap grid grid-2" style="align-items:center;gap:56px">
    <div class="hero-visual" style="aspect-ratio:4/2.6">
      <div class="grid-lines"></div>
      <div class="glass"></div>
      <div class="hero-float hero-float-1" style="top:14%;left:8%">
        <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></div>
        <div><b>Referral link</b><span>share &amp; earn</span></div>
      </div>
    </div>
    <div>
      <span class="eyebrow">Affiliate Programme</span>
      <h2 style="font-size:28px;margin:10px 0 14px">Earn by referring learners to UltrAdemy</h2>
      <p class="muted" style="margin-bottom:22px">Share your referral link, and earn commission when the people you refer join a paid UltrAdemy programme or subscription. Commission rules are set by UltrAdemy and shown before you join.</p>
      <a href="register.php" class="btn btn-primary">Become an Affiliate</a>
    </div>
  </div>
</section>

<!-- ============ SUCCESS STORIES (placeholder — CMS managed) ============ -->
<section class="section section-tight" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow">Success Stories</span>
      <h2>Hear from our students</h2>
      <p>Student and graduate stories, added and approved by UltrAdemy.</p>
    </div>
    <div class="empty-card">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-3.4-.7L3 21l1.7-5.1a8.4 8.4 0 0 1-.7-3.4A8.5 8.5 0 0 1 12.5 3 8.5 8.5 0 0 1 21 11.5Z"/></svg>
      <b>Stories coming soon</b>
      <p>Approved student testimonials will appear here once published through the UltrAdemy admin.</p>
    </div>
  </div>
</section>

<!-- ============ BLOG ============ -->
<section class="section" id="blog">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">From the blog</span>
      <h2>Training, career and UltrAdemy news</h2>
      <p>Articles, guides and updates — managed from the UltrAdemy admin.</p>
    </div>
    <div class="grid grid-3">
      <article class="card post-card">
        <div class="thumb"><span class="badge cat">Career</span></div>
        <div class="card-body">
          <h3>Choosing a learning mode: physical, online or hybrid</h3>
          <p class="meta">UltrAdemy Team</p>
        </div>
      </article>
      <article class="card post-card">
        <div class="thumb"><span class="badge cat badge-magenta">Training</span></div>
        <div class="card-body">
          <h3>What to expect in your first week at a training hub</h3>
          <p class="meta">UltrAdemy Team</p>
        </div>
      </article>
      <article class="card post-card">
        <div class="thumb"><span class="badge cat">Education</span></div>
        <div class="card-body">
          <h3>Building a study routine around online coursework</h3>
          <p class="meta">UltrAdemy Team</p>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section section-tight" id="faq">
  <div class="wrap" style="max-width:760px">
    <div class="section-head center">
      <span class="eyebrow">FAQ</span>
      <h2>Common questions</h2>
    </div>
    <div class="faq-list">
      <div class="faq-item open">
        <button class="faq-q">Do I need experience to start a programme? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>It depends on the programme — each programme page lists its requirements and who it's designed for. Many are built for complete beginners.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">Can I switch between online and physical training? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>Where a programme is offered in hybrid mode, yes. Availability depends on the specific programme and centre.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">How do I apply to a programme? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>Create your UltrAdemy account, open the programme you're interested in, and select Apply Now. You can track your application status from your dashboard.</p></div>
      </div>
      <div class="faq-item">
        <button class="faq-q">What payment methods are supported? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>Online payment and manual bank transfer with proof of payment, reviewed by our finance team.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <h2>Ready to start your journey?</h2>
      <p>Create your UltrAdemy account and discover programmes designed for your goals.</p>
      <div class="hero-cta">
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <a href="programmes.php" class="btn btn-secondary">Explore Programmes</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
