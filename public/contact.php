<?php
require __DIR__ . '/../config/bootstrap.php';
Session::start();
$active = 'contact';

// Form has no backend yet (README §69 — submissions will enter the admin
// backend once Phase 4/12 build it). This just acknowledges the submit
// so the UX can be reviewed end to end.
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact Us — UltrAdemy</title>
<meta name="description" content="Get in touch with UltrAdemy — questions about programmes, centres, or enrolment.">
<link rel="canonical" href="/contact.php">
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
    <div class="breadcrumb"><a href="index.php">Home</a> / Contact</div>
    <span class="eyebrow">Contact</span>
    <h1>Get in touch</h1>
    <p>Questions about a programme, a centre, or how to get started? Send us a message and we'll respond as soon as we can.</p>
  </div>
</section>

<section class="section">
  <div class="wrap grid grid-2" style="grid-template-columns:1.4fr 1fr;gap:48px;align-items:start">
    <div class="card card-body">
      <?php if ($submitted): ?>
        <div class="empty-card" style="border-style:solid">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
          <b>Message received</b>
          <p>Thanks for reaching out — our team will get back to you shortly.</p>
        </div>
      <?php else: ?>
      <form method="post" action="contact.php">
        <div class="form-grid">
          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required>
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="phone">Phone number</label>
            <input type="tel" id="phone" name="phone">
          </div>
          <div class="field">
            <label for="subject">Subject</label>
            <select id="subject" name="subject">
              <option>Programme enquiry</option>
              <option>Centre / visit enquiry</option>
              <option>Affiliate programme</option>
              <option>Corporate training</option>
              <option>Other</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label for="message">Message</label>
          <textarea id="message" name="message" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>
      <?php endif; ?>
    </div>

    <aside class="stack">
      <div class="card card-body">
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg></div>
          <div><h4>Email</h4><p>info@ultrademy.com</p></div>
        </div>
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"/></svg></div>
          <div><h4>Phone</h4><p>Available on request</p></div>
        </div>
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div>
          <div><h4>Business hours</h4><p>Monday – Friday, standard business hours</p></div>
        </div>
      </div>

      <div class="card card-body">
        <h4 style="font-size:14px;margin-bottom:10px;font-family:var(--font-primary)">Our Centres</h4>
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
          <div><h4>Gwagwalada Hub</h4><p>Gwagwalada, FCT</p></div>
        </div>
        <div class="contact-info-card">
          <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
          <div><h4>Kubwa Hub</h4><p>Kubwa, FCT</p></div>
        </div>
      </div>
    </aside>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
