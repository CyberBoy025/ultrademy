<?php
/** @var bool $enabled @var int $rate @var int $minPayout @var int $cookieDays */
$user = Auth::check() ? Auth::user() : null;
$isAffiliate = $user ? (bool) Affiliate::forUser((int) $user['id']) : false;
?>
<div style="padding:48px 0 8px;text-align:center">
  <h1 style="font-size:34px;line-height:1.15;max-width:20ch;margin:0 auto 14px">
    Earn by referring people to UltrAdemy.
  </h1>
  <p class="cap" style="font-size:15px;max-width:52ch;margin:0 auto 26px">
    Share your link. When someone you referred enrols or subscribes, you earn a
    commission on their first payment — no cap on how many people you refer.
  </p>
  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <?php if ($isAffiliate): ?>
      <a class="btn primary" href="app.php?r=dashboard">Go to your dashboard</a>
    <?php elseif ($user): ?>
      <a class="btn primary" href="app.php?r=dashboard"><?= $enabled ? 'Apply now' : 'View the programme' ?></a>
    <?php else: ?>
      <a class="btn primary" href="login.php">Sign in to apply</a>
      <a class="btn sm" href="<?= View::e(app_url('register.php')) ?>">Create an UltrAdemy account</a>
    <?php endif; ?>
  </div>
  <?php if (!$enabled): ?>
    <p class="cap" style="margin-top:14px">Applications aren't open at the moment — check back soon.</p>
  <?php endif; ?>
</div>

<div class="aff-stats-grid">
  <div class="card" style="text-align:center">
    <div style="font-size:28px;font-weight:700;font-family:var(--font-1)"><?= number_format($rate / 100, 2) ?>%</div>
    <div class="cap" style="margin-top:4px">of their first qualifying payment</div>
  </div>
  <div class="card" style="text-align:center">
    <div style="font-size:28px;font-weight:700;font-family:var(--font-1)">₦<?= number_format($minPayout / 100) ?></div>
    <div class="cap" style="margin-top:4px">minimum before a payout is sent</div>
  </div>
  <div class="card" style="text-align:center">
    <div style="font-size:28px;font-weight:700;font-family:var(--font-1)"><?= (int) $cookieDays ?> days</div>
    <div class="cap" style="margin-top:4px">a referral stays credited to you</div>
  </div>
</div>

<div style="margin:52px 0">
  <h2 style="font-size:22px;text-align:center;margin-bottom:28px">How it works</h2>
  <div class="aff-steps-grid">
    <div>
      <div class="cap" style="font-family:var(--font-1);font-weight:700;color:var(--brand-cyan-text);font-size:12px;margin-bottom:6px">STEP 1</div>
      <h3 style="font-size:15px;margin-bottom:6px">Apply</h3>
      <p class="cap">Sign in with your UltrAdemy account and tell us how you plan to refer people.</p>
    </div>
    <div>
      <div class="cap" style="font-family:var(--font-1);font-weight:700;color:var(--brand-cyan-text);font-size:12px;margin-bottom:6px">STEP 2</div>
      <h3 style="font-size:15px;margin-bottom:6px">Get your link</h3>
      <p class="cap">Once approved, you get a unique referral link and code to share however you like.</p>
    </div>
    <div>
      <div class="cap" style="font-family:var(--font-1);font-weight:700;color:var(--brand-cyan-text);font-size:12px;margin-bottom:6px">STEP 3</div>
      <h3 style="font-size:15px;margin-bottom:6px">They join</h3>
      <p class="cap">Anyone who follows your link and registers is credited to you for <?= (int) $cookieDays ?> days.</p>
    </div>
    <div>
      <div class="cap" style="font-family:var(--font-1);font-weight:700;color:var(--brand-cyan-text);font-size:12px;margin-bottom:6px">STEP 4</div>
      <h3 style="font-size:15px;margin-bottom:6px">You earn</h3>
      <p class="cap">Their first enrolment or subscription payment earns you a commission, tracked from your dashboard.</p>
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="chead"><h3>Good to know</h3></div>
  <ul style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:8px">
    <li class="cap">Referrals must be new to UltrAdemy — you cannot refer yourself or someone who already has an account.</li>
    <li class="cap">Commission is earned once, on the first qualifying payment — enrolments and subscriptions count; donations and application fees do not.</li>
    <li class="cap">Someone else reviews and approves your application, and someone else again approves each commission before it's paid — the same checks-and-balances every payment in UltrAdemy goes through.</li>
  </ul>
</div>
