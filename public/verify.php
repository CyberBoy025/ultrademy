<?php
/**
 * Public certificate verification — Decision 5 ("Public certificate verification by
 * serial? Yes").
 *
 * Deliberately minimal about what it discloses: whether the serial is genuine, what it
 * was awarded for, when, and whether it has been revoked. It does NOT expose the
 * holder's email, student number, centre or any other record — a verification page is
 * for confirming a claim someone has already made to you, not for looking people up.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();

$serial = trim((string) ($_GET['serial'] ?? ''));
$certificate = $serial !== '' ? Certificate::findBySerial($serial) : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify a Certificate — UltrAdemy</title>
<meta name="description" content="Check whether an UltrAdemy certificate serial is genuine.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/site.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<?php $active = ''; require __DIR__ . '/../app/views/partials/header.php'; ?>

<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="index.php">Home</a> / Verify a Certificate</div>
    <span class="eyebrow">Verification</span>
    <h1>Verify a certificate</h1>
    <p>Enter the serial printed on an UltrAdemy certificate to confirm it is genuine.</p>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:640px">
    <div class="card card-body" style="margin-bottom:20px">
      <form method="get" action="verify.php">
        <div class="field">
          <label for="serial">Certificate serial</label>
          <input type="text" id="serial" name="serial" value="<?= View::e($serial) ?>" placeholder="UD-CERT-2026-XXXXXXXXXX" required>
        </div>
        <button type="submit" class="btn btn-primary">Verify</button>
      </form>
    </div>

    <?php if ($serial !== ''): ?>
      <?php if (!$certificate): ?>
        <div class="card card-body" style="border-color:var(--color-error)">
          <h3 style="font-size:17px;margin-bottom:6px;color:var(--color-error)">Not found</h3>
          <p class="muted">No certificate matches that serial. Check for typos — serials look like <code>UD-CERT-2026-A1B2C3D4E5</code>.</p>
        </div>
      <?php elseif ($certificate['revoked_at']): ?>
        <div class="card card-body" style="border-color:var(--color-error)">
          <h3 style="font-size:17px;margin-bottom:6px;color:var(--color-error)">Revoked</h3>
          <p class="muted">This certificate was issued but has since been revoked by UltrAdemy, on
            <?= View::e(date('d M Y', strtotime($certificate['revoked_at']))) ?>. It should not be relied upon.</p>
        </div>
      <?php else: ?>
        <div class="card card-body" style="border-color:var(--color-success)">
          <h3 style="font-size:17px;margin-bottom:10px;color:var(--color-success)">✓ Genuine certificate</h3>
          <p style="font-size:15px;font-weight:600;margin-bottom:4px"><?= View::e($certificate['holder_name'] ?: 'An UltrAdemy learner') ?></p>
          <p class="muted" style="margin-bottom:12px"><?= View::e($certificate['title']) ?></p>
          <p class="muted" style="font-size:13px">
            Issued <?= View::e(date('d M Y', strtotime($certificate['issued_at']))) ?>
            · Serial <?= View::e($certificate['serial']) ?>
          </p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
</body>
</html>
