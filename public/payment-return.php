<?php
/**
 * Where the gateway sends the payer's BROWSER back to.
 *
 * 05-finance-payments.md §5, and the single most important rule in this phase:
 *
 *     "The browser callback never marks a payment successful. It is user-controllable —
 *      a student can hit the success URL without paying."
 *
 * So this page does not write a payment status of its own. It asks the gateway
 * server-side (PaymentService::verifyWithGateway) and reports whatever the gateway says.
 * If the gateway cannot confirm it, the page says "confirming" and the webhook settles it
 * — it never guesses in the payer's favour.
 */
require __DIR__ . '/../config/bootstrap.php';
Session::start();
Auth::requireLogin();

$reference = trim((string) ($_GET['reference'] ?? $_GET['tx_ref'] ?? $_GET['trxref'] ?? ''));
$payment = $reference !== '' ? Payment::findByReference($reference) : null;
if (!$payment && $reference !== '') {
    $payment = Payment::findByGatewayReference($reference);
}

$state = 'unknown';
$message = 'We could not identify that payment.';

if ($payment) {
    // Only the payer (or finance) may look at it.
    if ((int) $payment['user_id'] !== (int) Auth::id() && !Auth::can('finance.invoice.view_any')) {
        http_response_code(403);
        exit('Not permitted.');
    }

    if ($payment['status'] === 'successful') {
        $state = 'success';
        $message = 'Payment confirmed.';
    } else {
        $error = PaymentService::verifyWithGateway((int) $payment['id']);
        $payment = Payment::find((int) $payment['id']);
        if ($payment['status'] === 'successful') {
            $state = 'success';
            $message = 'Payment confirmed.';
        } elseif ($payment['status'] === 'failed') {
            $state = 'failed';
            $message = $payment['failure_reason'] ?: 'The payment did not go through.';
        } else {
            $state = 'pending';
            $message = $error !== '' ? $error : 'Still confirming with the payment provider.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment — UltrAdemy</title>
<meta name="robots" content="noindex">
<?php if ($state === 'pending'): ?><meta http-equiv="refresh" content="5"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/shell.css">
<script>(function(){try{var t=localStorage.getItem('ultrademy.theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){}})();</script>
</head>
<body>
<div style="min-height:100vh;display:grid;place-items:center;padding:24px">
  <div class="card" style="max-width:440px;text-align:center;padding:32px">
    <?php if ($state === 'success'): ?>
      <h1 style="font-size:20px;margin-bottom:10px;color:var(--success)">✓ Payment confirmed</h1>
      <p class="cap" style="margin-bottom:20px"><?= View::e($message) ?><?php if ($payment['receipt_number']): ?><br>Receipt <strong><?= View::e($payment['receipt_number']) ?></strong>.<?php endif; ?></p>
    <?php elseif ($state === 'failed'): ?>
      <h1 style="font-size:20px;margin-bottom:10px;color:var(--error)">Payment not completed</h1>
      <p class="cap" style="margin-bottom:20px"><?= View::e($message) ?></p>
    <?php elseif ($state === 'pending'): ?>
      <h1 style="font-size:20px;margin-bottom:10px">Confirming your payment…</h1>
      <p class="cap" style="margin-bottom:20px">
        <?= View::e($message) ?><br>
        This page checks again every few seconds. Your payment is confirmed by our payment
        provider, not by this page, so it is safe to close this window — nothing is lost.
      </p>
    <?php else: ?>
      <h1 style="font-size:20px;margin-bottom:10px">Payment not found</h1>
      <p class="cap" style="margin-bottom:20px"><?= View::e($message) ?></p>
    <?php endif; ?>
    <div class="stack">
      <?php if ($payment): ?>
        <a class="btn primary" href="app.php?r=invoices.show&id=<?= (int) $payment['invoice_id'] ?>">View Invoice</a>
      <?php endif; ?>
      <a class="btn" href="app.php?r=billing">My Payments</a>
    </div>
  </div>
</div>
</body>
</html>
