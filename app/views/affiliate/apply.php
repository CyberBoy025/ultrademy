<?php /** @var ?array $affiliate @var bool $enabled @var int $rate */ ?>
<div class="topbar"><div><h1>Affiliate Programme</h1><p>Refer people to UltrAdemy and earn on what they enrol in.</p></div></div>

<?php if (!$enabled): ?>
  <div class="card"><p class="cap" style="margin:0">The affiliate programme isn't open at the moment. Check back soon.</p></div>

<?php elseif ($affiliate && $affiliate['status'] === 'rejected'): ?>
  <div class="card" style="border-left:3px solid var(--error)">
    <div class="chead"><h3>Application not approved</h3></div>
    <p class="cap" style="margin:0"><?= View::e((string) ($affiliate['decision_note'] ?: 'Your application was not approved on this occasion.')) ?></p>
  </div>

<?php elseif ($affiliate && $affiliate['status'] === 'suspended'): ?>
  <div class="card" style="border-left:3px solid var(--error)">
    <div class="chead"><h3>Account suspended</h3></div>
    <p class="cap" style="margin:0"><?= View::e((string) ($affiliate['decision_note'] ?: 'Please contact us.')) ?></p>
  </div>

<?php elseif ($affiliate): ?>
  <div class="card" style="border-left:3px solid var(--warning)">
    <div class="chead"><h3>Application received</h3></div>
    <p class="cap" style="margin:0">
      We're reviewing it. You'll get a notification here once there's a decision, and your
      referral link appears on this page as soon as you're approved.
    </p>
  </div>

<?php else: ?>
  <div class="row row-b">
    <div class="card">
      <div class="chead"><h3>How it works</h3></div>
      <div class="queue">
        <?php foreach ([
          ['1', 'Apply', 'Tell us how you plan to refer people.'],
          ['2', 'Share your link', 'Approved affiliates get a unique link and code.'],
          ['3', 'They enrol', 'Commission is earned on their first programme or subscription payment.'],
          ['4', 'Get paid', 'Once approved, commissions are paid out to your nominated account.'],
        ] as [$n, $t, $d]): ?>
        <div class="queue-item">
          <div class="queue-ico"><strong><?= $n ?></strong></div>
          <div class="queue-t"><h4><?= View::e($t) ?></h4><p><?= View::e($d) ?></p></div>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="cap" style="margin-top:14px">
        The current rate is <?= number_format($rate / 100, 2) ?>% of the first qualifying
        payment. Referrals must be new to UltrAdemy — you cannot refer yourself or someone
        who already has an account.
      </p>
    </div>

    <div class="card">
      <div class="chead"><h3>Apply</h3></div>
      <form method="post" action="app.php?r=apply">
        <?= Csrf::field() ?>
        <div class="field">
          <label>How will you refer people?</label>
          <textarea name="motivation" rows="4" required style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px" placeholder="Your audience, channel, or community."></textarea>
        </div>
        <div class="field">
          <label>How should we pay you?</label>
          <select name="payout_method">
            <option value="bank_transfer">Bank transfer</option>
            <option value="other">Something else — we'll ask</option>
          </select>
        </div>
        <div class="field">
          <label>Account details</label>
          <input type="text" name="payout_details" maxlength="255" placeholder="Bank, account name and number">
        </div>
        <button type="submit" class="btn primary">Submit application</button>
      </form>
    </div>
  </div>
<?php endif; ?>
