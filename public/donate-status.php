<?php
/**
 * Donation status — the page a supporter lands on after giving, and the one that shows
 * bank-transfer instructions.
 *
 * Reachable WITHOUT a session, because most donors are guests. Access is by the
 * donation's `public_token` (128 bits of randomness), and the page deliberately shows
 * only what the person who made the gift already knows: their own amount, reference and
 * status. No other donor, no ledger, no account details.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();

$active = 'donate';
$token = trim((string) ($_GET['t'] ?? ''));
$donation = $token !== '' ? Donation::findByToken($token) : null;

if (!$donation) {
    http_response_code(404);
}

$payment = null;
$state = 'unknown';

if ($donation) {
    // A card payment may still be settling. Ask the gateway server-side rather than
    // trusting the fact that the browser arrived here — 05-finance-payments.md §5.
    $payment = Database::one(
        "SELECT * FROM payments WHERE invoice_id = :i ORDER BY id DESC LIMIT 1",
        ['i' => (int) $donation['invoice_id']]
    );
    if ($payment && $payment['status'] === 'initiated' && $payment['gateway_reference'] !== null) {
        PaymentService::verifyWithGateway((int) $payment['id']);
        $donation = Donation::findByToken($token);
        $payment = Database::one('SELECT * FROM payments WHERE id = :id', ['id' => (int) $payment['id']]);
    }

    $state = match (true) {
        $donation['status'] === 'completed' => 'complete',
        $donation['status'] === 'failed' => 'failed',
        $payment !== null && $payment['method'] === 'bank_transfer' => 'awaiting_transfer',
        $payment === null => 'awaiting_transfer',
        default => 'pending',
    };
}

$bank = [
    'name'    => (string) (Setting::get('bank_name', '') ?? ''),
    'account' => (string) (Setting::get('bank_account_name', '') ?? ''),
    'number'  => (string) (Setting::get('bank_account_number', '') ?? ''),
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your donation — UltrAdemy</title>
<meta name="robots" content="noindex">
<?php if ($state === 'pending'): ?><meta http-equiv="refresh" content="6"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/site.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>

<?php require __DIR__ . '/../app/views/partials/header.php'; ?>

<section class="section">
  <div class="wrap" style="max-width:620px">
    <div class="card" style="padding:32px">

    <?php if (!$donation): ?>
      <h1 style="font-size:22px;margin-bottom:10px">We couldn't find that donation</h1>
      <p class="muted">The link may be incomplete. If you've given and are worried it didn't go through, <a href="contact.php">contact us</a> with the date and amount and we'll find it.</p>

    <?php elseif ($state === 'complete'): ?>
      <h1 style="font-size:22px;margin-bottom:10px;color:var(--success)">Thank you</h1>
      <p style="font-size:15px;line-height:1.7">
        Your gift of <strong><?= View::e(Money::format((int) $donation['amount'], $donation['currency'])) ?></strong>
        <?= $donation['campaign_title'] ? 'to ' . View::e($donation['campaign_title']) : '' ?> has been received.
      </p>
      <p class="muted" style="margin-top:14px;font-size:13px">
        Reference <strong><?= View::e($donation['reference']) ?></strong><?php if ($donation['invoice_number']): ?> ·
        Invoice <?= View::e($donation['invoice_number']) ?><?php endif; ?><br>
        A receipt is on its way to <?= View::e($donation['donor_email']) ?>.
      </p>

    <?php elseif ($state === 'awaiting_transfer'): ?>
      <h1 style="font-size:22px;margin-bottom:10px">Almost there</h1>
      <p style="font-size:15px;line-height:1.7">
        Please transfer <strong><?= View::e(Money::format((int) $donation['amount'], $donation['currency'])) ?></strong>
        using the reference below, and we'll confirm it once it lands.
      </p>

      <?php if ($bank['number'] !== ''): ?>
        <div class="card" style="background:var(--surface-muted);padding:18px;margin:18px 0">
          <p class="muted" style="font-size:12px;margin-bottom:8px">Transfer to</p>
          <p style="font-size:14px;line-height:1.9;margin:0">
            <strong><?= View::e($bank['account']) ?></strong><br>
            <?= View::e($bank['name']) ?><br>
            <?= View::e($bank['number']) ?>
          </p>
        </div>
      <?php else: ?>
        <p class="muted" style="margin:16px 0">
          Our bank details aren't published here yet — please <a href="contact.php">contact us</a>
          and quote your reference, and we'll send them to you.
        </p>
      <?php endif; ?>

      <div class="card" style="border-left:3px solid var(--brand-cyan-text);padding:14px 16px">
        <p style="font-size:13px;margin:0">
          Use <strong><?= View::e($donation['reference']) ?></strong> as the transfer narration.
          It's how we match your gift to you — without it, confirming takes much longer.
        </p>
      </div>

      <p class="muted" style="font-size:13px;margin-top:18px">
        Nothing is charged automatically. Save this page or note the reference; we'll email
        <?= View::e($donation['donor_email']) ?> once finance has confirmed it.
      </p>

    <?php elseif ($state === 'failed'): ?>
      <h1 style="font-size:22px;margin-bottom:10px;color:var(--error)">That payment didn't go through</h1>
      <p style="font-size:15px;line-height:1.7">Nothing was taken. You're welcome to try again whenever suits.</p>
      <p style="margin-top:18px"><a class="btn btn-primary" href="donate.php">Try again</a></p>

    <?php else: ?>
      <h1 style="font-size:22px;margin-bottom:10px">Confirming your donation…</h1>
      <p style="font-size:15px;line-height:1.7">
        We're checking with the payment provider. This page refreshes itself every few
        seconds — and it's safe to close, because your gift is confirmed by our payment
        provider rather than by this page. Nothing is lost either way.
      </p>
      <p class="muted" style="margin-top:14px;font-size:13px">Reference <strong><?= View::e($donation['reference']) ?></strong></p>
    <?php endif; ?>

      <p style="margin-top:24px"><a href="index.php" class="btn btn-secondary btn-sm">Back to UltrAdemy</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>
<script src="js/site.js"></script>
</body>
</html>
