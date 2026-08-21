<?php
/**
 * Public corporate training page and enquiry form (README §58, §46).
 *
 * Unauthenticated. It records a training request and nothing else — it does not create an
 * organisation record. A public form that silently creates company records fills the CRM
 * with typos, duplicates and whatever a bot typed; linking is a deliberate act by whoever
 * triages the enquiry.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();

$active = 'corporate';
$enabled = Corporate::enabled();
$error = null;
$sent = false;
$old = ['org' => '', 'name' => '', 'email' => '', 'phone' => '', 'participants' => '', 'requirements' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'org'          => trim((string) ($_POST['organisation_name'] ?? '')),
        'name'         => trim((string) ($_POST['contact_name'] ?? '')),
        'email'        => trim((string) ($_POST['contact_email'] ?? '')),
        'phone'        => trim((string) ($_POST['contact_phone'] ?? '')),
        'participants' => trim((string) ($_POST['participants'] ?? '')),
        'requirements' => trim((string) ($_POST['requirements'] ?? '')),
    ];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!$enabled) {
        $error = 'We are not taking corporate enquiries through this form at the moment.';
    } elseif (!Csrf::verify()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!RateLimit::attempt('corporate_enquiry', $ip, 5, 3600)) {
        $error = 'Too many enquiries from this connection. Please email us instead.';
    } elseif (Captcha::isEnabled() && !Captcha::verify()) {
        $error = 'Please complete the verification and try again.';
    } else {
        $result = Corporate::createRequest([
            'organisation_id'   => null,
            'organisation_name' => $old['org'],
            'contact_name'      => $old['name'],
            'contact_email'     => $old['email'],
            'contact_phone'     => $old['phone'],
            'programme_id'      => (int) ($_POST['programme_id'] ?? 0) ?: null,
            'participants'      => (int) $old['participants'] ?: null,
            'preferred_start'   => $_POST['preferred_start'] ?: null,
            'delivery_mode'     => (string) ($_POST['delivery_mode'] ?? 'unspecified'),
            'centre_id'         => (int) ($_POST['centre_id'] ?? 0) ?: null,
            'requirements'      => $old['requirements'],
        ], 'public_form');

        if ($result['ok']) {
            $sent = true;
        } else {
            $error = $result['error'];
        }
    }
}

$programmes = Programme::all(true);
$centres = Centre::all();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Corporate Training — UltrAdemy</title>
<meta name="description" content="Practical training for banks, government agencies and companies — delivered at our hubs, online, or at your own premises.">
<link rel="canonical" href="/corporate.php">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/site.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<?php require __DIR__ . '/../app/views/partials/header.php'; ?>

<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="index.php">Home</a> / Corporate Training</div>
    <span class="eyebrow">For organisations</span>
    <h1>Training for your team</h1>
    <p>Practical, hands-on programmes for banks, government agencies, parastatals, companies and institutions — at our hubs, online, or blended.</p>
  </div>
</section>

<section class="section">
  <div class="wrap grid grid-2" style="gap:48px;align-items:start">
    <div>
      <h2 style="font-size:22px;margin-bottom:14px">How it works</h2>
      <div class="stack" style="gap:14px">
        <?php foreach ([
          ['Tell us what you need', 'Programme, how many people, and when.'],
          ['We propose', 'Scope, schedule and a per-seat price, valid for 30 days.'],
          ['You nominate your team', 'Send us names; each person gets their own invitation.'],
          ['They train', 'Same platform, same instructors, same certificates as any other student.'],
          ['You get the report', 'Attendance, assessment results and certificates, per participant.'],
        ] as $i => [$t, $d]): ?>
          <div class="card" style="padding:16px">
            <strong style="display:block;font-size:15px;margin-bottom:4px"><?= $i + 1 ?>. <?= View::e($t) ?></strong>
            <span class="muted" style="font-size:13px"><?= View::e($d) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <h2 style="font-size:22px;margin:32px 0 14px">Delivery</h2>
      <p class="muted" style="font-size:14px;line-height:1.8">
        <strong>At our hubs</strong> — Gwagwalada or Kubwa, with the equipment and lab space already set up.<br>
        <strong>Online</strong> — for teams spread across locations.<br>
        <strong>Blended</strong> — online theory, hands-on practice in person.
      </p>
    </div>

    <div>
      <div class="card" style="padding:28px;position:sticky;top:24px">
        <h2 style="font-size:20px;margin-bottom:6px">Request a proposal</h2>
        <p class="muted" style="font-size:13px;margin-bottom:20px">Tell us what you need and we'll come back with scope, schedule and pricing.</p>

        <?php if ($sent): ?>
          <div class="card" style="border-left:3px solid var(--success);padding:14px 16px">
            <p style="font-size:14px;margin:0">
              <strong>Thank you.</strong> We've logged your enquiry and someone will be in touch at
              <?= View::e($old['email']) ?>.
            </p>
          </div>
        <?php else: ?>
          <?php if ($error): ?>
            <div class="card" style="border-left:3px solid var(--error);padding:12px 14px;margin-bottom:18px">
              <p style="font-size:13px;margin:0"><?= View::e($error) ?></p>
            </div>
          <?php endif; ?>

          <?php if (!$enabled): ?>
            <p class="muted" style="font-size:13px">
              Our corporate enquiry form is closed at the moment. Please
              <a href="contact.php">contact us directly</a> and we'll help.
            </p>
          <?php else: ?>
          <form method="post" action="corporate.php">
            <?= Csrf::field() ?>
            <div class="field"><label>Organisation</label><input type="text" name="organisation_name" required maxlength="200" value="<?= View::e($old['org']) ?>"></div>
            <div class="field"><label>Your name</label><input type="text" name="contact_name" required maxlength="150" value="<?= View::e($old['name']) ?>"></div>
            <div class="field"><label>Work email</label><input type="email" name="contact_email" required maxlength="255" value="<?= View::e($old['email']) ?>"></div>
            <div class="field"><label>Phone <span class="muted">(optional)</span></label><input type="tel" name="contact_phone" maxlength="32" value="<?= View::e($old['phone']) ?>"></div>
            <div class="field">
              <label>Programme <span class="muted">(or leave blank for something bespoke)</span></label>
              <select name="programme_id">
                <option value="">Not sure / bespoke</option>
                <?php foreach ($programmes as $p): ?><option value="<?= (int) $p['id'] ?>"><?= View::e($p['title']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="field"><label>How many people?</label><input type="number" name="participants" min="1" max="5000" value="<?= View::e($old['participants']) ?>"></div>
            <div class="field">
              <label>Delivery</label>
              <select name="delivery_mode">
                <option value="unspecified">Not sure yet</option>
                <option value="physical">At an UltrAdemy hub</option>
                <option value="online">Online</option>
                <option value="hybrid">Blended</option>
              </select>
            </div>
            <div class="field"><label>Preferred start <span class="muted">(optional)</span></label><input type="date" name="preferred_start"></div>
            <div class="field">
              <label>What do you need?</label>
              <textarea name="requirements" rows="4"><?= View::e($old['requirements']) ?></textarea>
            </div>
            <?php if (Captcha::isEnabled()): ?><div class="field"><?= Captcha::widget() ?></div><?php endif; ?>
            <button type="submit" class="btn btn-primary" style="width:100%">Send enquiry</button>
            <p class="muted" style="font-size:12px;margin-top:14px">
              We use these details to respond to your enquiry and for our own records. Nothing else.
            </p>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
<script src="js/site.js"></script>
</body>
</html>
