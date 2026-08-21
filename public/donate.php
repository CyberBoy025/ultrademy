<?php
/**
 * Public giving page — README §9b.
 *
 * Unauthenticated by design. Requiring registration before someone can give is the
 * single largest cause of abandoned donations, so a supporter needs only a name, an
 * email and an amount. A `users` row is created behind the scenes (Donation::
 * resolveDonorUser) because the invoice and payment tables require one — see
 * migration 088 for why that is the right answer rather than making three financial
 * tables nullable.
 *
 * WHAT THIS PAGE IS: an appeal for a gift. Nothing is given in return — no equity, no
 * share of revenue, no promise of repayment. The copy says so plainly and deliberately,
 * because "donate" on a button does not change what an arrangement legally is.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();

$active = 'donate';
$enabled = Donation::enabled();
$campaigns = $enabled ? Donation::campaigns(true) : [];
$slug = trim((string) ($_GET['c' ] ?? ''));
$campaign = $slug !== '' ? Donation::findCampaignBySlug($slug) : null;
if ($campaign && !Donation::campaignIsOpen($campaign)) {
    $campaign = null;
}

$error = null;
$prefill = ['name' => '', 'email' => '', 'phone' => '', 'message' => '', 'amount' => ''];

// ------------------------------------------------------------------ POST: give
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefill = [
        'name'    => trim((string) ($_POST['donor_name'] ?? '')),
        'email'   => trim((string) ($_POST['donor_email'] ?? '')),
        'phone'   => trim((string) ($_POST['donor_phone'] ?? '')),
        'message' => trim((string) ($_POST['message'] ?? '')),
        'amount'  => trim((string) ($_POST['amount'] ?? '')),
    ];

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!$enabled) {
        $error = 'Donations are not currently being accepted.';
    } elseif (!Csrf::verify()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!RateLimit::attempt('donate', $ip, 8, 3600)) {
        // An open endpoint that creates users and invoices is worth rate limiting:
        // otherwise it is a free way to spray junk accounts and enumerate emails.
        $error = 'Too many attempts from this connection. Please try again later.';
    } elseif (Captcha::isEnabled() && !Captcha::verify()) {
        $error = 'Please complete the verification and try again.';
    } elseif ($prefill['name'] === '') {
        $error = 'Please tell us your name.';
    } elseif (!filter_var($prefill['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address so we can send your receipt.';
    } elseif (($_POST['method'] ?? '') === '') {
        $error = 'Choose how you would like to give.';
    } else {
        $amountMinor = Money::toMinor($prefill['amount']);
        $campaignId = null;
        if (($_POST['campaign_id'] ?? '') !== '') {
            $campaignId = (int) $_POST['campaign_id'];
        }

        $result = Donation::start($campaignId, [
            'name'         => $prefill['name'],
            'email'        => $prefill['email'],
            'phone'        => $prefill['phone'],
            'message'      => $prefill['message'],
            'is_anonymous' => isset($_POST['is_anonymous']),
        ], $amountMinor);

        if (!$result['ok']) {
            $error = $result['error'];
        } else {
            $donation = $result['donation'];
            $method = (string) $_POST['method'];

            if ($method === 'bank_transfer') {
                header('Location: donate-status.php?t=' . urlencode($donation['public_token']));
                exit;
            }

            $init = PaymentService::initialise((int) $donation['invoice_id'], $method, (int) $donation['donor_user_id']);
            if ($init['ok'] && $init['url']) {
                header('Location: ' . $init['url']);
                exit;
            }
            $error = $init['error'] ?? 'We could not reach the payment provider. Please try again.';
        }
    }
}

$methods = $enabled ? PaymentService::availableForPayer() : [];
$totals = Donation::totals($campaign ? (int) $campaign['id'] : null);
$wall = $campaign && (int) $campaign['show_donor_wall'] === 1 ? Donation::wall((int) $campaign['id'], 12) : [];
$progress = $campaign ? Donation::progressPercent($campaign) : null;
$intro = (string) (Setting::get('donations_intro', '') ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $campaign ? View::e($campaign['title']) . ' — Support UltrAdemy' : 'Support UltrAdemy' ?></title>
<meta name="description" content="<?= View::e($campaign['summary'] ?? 'Support practical training at UltrAdemy. Your gift funds learning for people who could not otherwise afford it.') ?>">
<link rel="canonical" href="/donate.php<?= $campaign ? '?c=' . View::e($campaign['slug']) : '' ?>">
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
    <div class="breadcrumb"><a href="index.php">Home</a> / <?= $campaign ? '<a href="donate.php">Support Us</a> / ' . View::e($campaign['title']) : 'Support Us' ?></div>
    <span class="eyebrow">Support UltrAdemy</span>
    <h1><?= View::e($campaign['title'] ?? 'Help someone learn a skill that changes their work') ?></h1>
    <p><?= View::e($campaign['summary'] ?? $intro) ?></p>
  </div>
</section>

<?php if (!$enabled): ?>
<section class="section">
  <div class="wrap" style="max-width:640px">
    <div class="card" style="padding:28px;text-align:center">
      <h2 style="font-size:20px;margin-bottom:10px">Not accepting donations right now</h2>
      <p class="muted">We're not collecting gifts at the moment. If you'd like to support UltrAdemy, please <a href="contact.php">get in touch</a> and we'll tell you how.</p>
    </div>
  </div>
</section>

<?php else: ?>
<section class="section">
  <div class="wrap grid grid-2" style="gap:48px;align-items:start">

    <!-- ------------------------------------------------ the case for support -->
    <div>
      <?php if ($campaign): ?>
        <?php if ($progress !== null): ?>
          <div class="card" style="padding:22px;margin-bottom:24px">
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:10px">
              <strong style="font-size:22px"><?= View::e(Money::formatShort((int) $campaign['raised_amount'], $campaign['currency'])) ?></strong>
              <span class="muted" style="font-size:13px">of <?= View::e(Money::formatShort((int) $campaign['target_amount'], $campaign['currency'])) ?> goal</span>
            </div>
            <div class="bar"><span style="width:<?= $progress ?>%"></span></div>
            <p class="muted" style="font-size:13px;margin-top:10px">
              <?= (int) $campaign['donor_count'] ?> supporter<?= (int) $campaign['donor_count'] === 1 ? '' : 's' ?>
              <?php if ($campaign['ends_on']): ?> · closes <?= View::e(date('d M Y', strtotime((string) $campaign['ends_on']))) ?><?php endif; ?>
            </p>
          </div>
        <?php endif; ?>

        <?php if ($campaign['story']): ?>
          <div style="font-size:15px;line-height:1.75;color:var(--text-2)"><?= nl2br(View::e($campaign['story'])) ?></div>
        <?php endif; ?>

      <?php else: ?>
        <p style="font-size:15px;line-height:1.75;color:var(--text-2)">
          UltrAdemy runs practical training hubs and online courses. Every gift goes toward
          places on those programmes for people who cannot currently afford one — equipment,
          instruction, and the seat itself.
        </p>
        <?php if ($totals['count'] > 0): ?>
          <p class="muted" style="margin-top:16px;font-size:14px">
            <?= (int) $totals['count'] ?> supporters have given <?= View::e(Money::formatShort($totals['total'])) ?> so far.
          </p>
        <?php endif; ?>

        <?php if ($campaigns): ?>
          <h2 style="font-size:20px;margin:32px 0 14px">Current appeals</h2>
          <div class="stack" style="gap:12px">
            <?php foreach ($campaigns as $c): ?>
              <a class="card" style="padding:18px;display:block;text-decoration:none" href="donate.php?c=<?= View::e($c['slug']) ?>">
                <strong style="display:block;font-size:16px;margin-bottom:4px"><?= View::e($c['title']) ?></strong>
                <span class="muted" style="font-size:13px"><?= View::e((string) $c['summary']) ?></span>
                <?php $p = Donation::progressPercent($c); if ($p !== null): ?>
                  <div class="bar" style="margin-top:12px"><span style="width:<?= $p ?>%"></span></div>
                  <span class="muted" style="font-size:12px"><?= View::e(Money::formatShort((int) $c['raised_amount'])) ?> raised of <?= View::e(Money::formatShort((int) $c['target_amount'])) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($wall): ?>
        <h2 style="font-size:20px;margin:32px 0 14px">Recent supporters</h2>
        <div class="stack" style="gap:10px">
          <?php foreach ($wall as $w): ?>
            <div class="card" style="padding:14px 16px">
              <strong style="font-size:14px"><?= $w['donor_name'] !== null ? View::e($w['donor_name']) : 'Anonymous' ?></strong>
              <span class="muted" style="font-size:13px"> · <?= View::e(Money::formatShort((int) $w['amount'], $w['currency'])) ?></span>
              <?php if ($w['message']): ?><p class="muted" style="font-size:13px;margin-top:6px">“<?= View::e($w['message']) ?>”</p><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ---------------------------------------------------------- the form -->
    <div>
      <div class="card" style="padding:28px;position:sticky;top:24px">
        <h2 style="font-size:20px;margin-bottom:6px">Make a donation</h2>
        <p class="muted" style="font-size:13px;margin-bottom:20px">
          A gift, not an investment — nothing is offered in return, and no repayment or
          share of revenue is promised.
        </p>

        <?php if ($error): ?>
          <div class="card" style="border-left:3px solid var(--error);padding:12px 14px;margin-bottom:18px">
            <p style="font-size:13px;margin:0"><?= View::e($error) ?></p>
          </div>
        <?php endif; ?>

        <form method="post" action="donate.php<?= $campaign ? '?c=' . View::e($campaign['slug']) : '' ?>">
          <?= Csrf::field() ?>
          <?php if ($campaign): ?><input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>"><?php endif; ?>

          <div class="field">
            <label>Amount</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
              <?php foreach (Donation::PRESETS as $preset): ?>
                <button type="button" class="btn btn-secondary btn-sm preset" data-amount="<?= Money::toMajorString($preset) ?>">
                  <?= View::e(Money::formatShort($preset)) ?>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="text" name="amount" id="amount" inputmode="decimal" required
                   placeholder="Other amount" value="<?= View::e($prefill['amount']) ?>">
          </div>

          <div class="field">
            <label>Your name</label>
            <input type="text" name="donor_name" required maxlength="150" value="<?= View::e($prefill['name']) ?>">
          </div>
          <div class="field">
            <label>Email <span class="muted">— your receipt goes here</span></label>
            <input type="email" name="donor_email" required maxlength="255" value="<?= View::e($prefill['email']) ?>">
          </div>
          <div class="field">
            <label>Phone <span class="muted">(optional)</span></label>
            <input type="tel" name="donor_phone" maxlength="32" value="<?= View::e($prefill['phone']) ?>">
          </div>
          <div class="field">
            <label>Message <span class="muted">(optional, shown publicly)</span></label>
            <input type="text" name="message" maxlength="500" value="<?= View::e($prefill['message']) ?>">
          </div>

          <div class="field">
            <label style="display:flex;align-items:center;gap:9px;font-weight:500">
              <input type="checkbox" name="is_anonymous" value="1" style="width:auto">
              Give anonymously — hide my name from the supporter list
            </label>
          </div>

          <div class="field">
            <label>How would you like to give?</label>
            <?php foreach ($methods as $code => $label): ?>
              <label style="display:flex;align-items:center;gap:9px;padding:8px 0;font-size:14px">
                <input type="radio" name="method" value="<?= View::e($code) ?>" style="width:auto" required>
                <?= View::e($label) ?>
              </label>
            <?php endforeach; ?>
            <label style="display:flex;align-items:center;gap:9px;padding:8px 0;font-size:14px">
              <input type="radio" name="method" value="bank_transfer" style="width:auto">
              Bank transfer — we'll show you the details
            </label>
          </div>

          <?php if (Captcha::isEnabled()): ?>
            <div class="field"><?= Captcha::widget() ?></div>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary" style="width:100%">Continue</button>

          <p class="muted" style="font-size:12px;margin-top:14px;line-height:1.6">
            We'll email you a numbered receipt. Your details are used to acknowledge the gift
            and for our own accounting — nothing else. See our
            <a href="contact.php">contact page</a> if you'd like your gift removed from the
            public supporter list.
          </p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
<script src="js/site.js"></script>
<script>
document.querySelectorAll('.preset').forEach(function (b) {
  b.addEventListener('click', function () {
    document.getElementById('amount').value = b.dataset.amount;
  });
});
</script>
</body>
</html>
