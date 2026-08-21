<?php /** @var array $campaigns @var array $centres @var bool $enabled @var array $totals */ ?>
<div class="topbar">
  <div>
    <h1>Donation Campaigns</h1>
    <p><?= count($campaigns) ?> campaign(s) · <?= View::e(Money::format($totals['total'])) ?> received from <?= (int) $totals['count'] ?> supporter(s)</p>
  </div>
  <?php if (Auth::can('donation.view_any')): ?><a class="btn sm" href="app.php?r=donations">Supporter ledger</a><?php endif; ?>
</div>

<?php if (!$enabled): ?>
<div class="card" style="margin-bottom:20px;border-left:3px solid var(--warning)">
  <p class="cap" style="margin:0">
    <strong>Donations are switched off.</strong> The public page shows a "not accepting
    donations" notice and campaigns cannot be published. Turn on <code>donations_enabled</code>
    in <a href="app.php?r=settings" style="color:var(--brand-cyan-text)">Settings</a> when
    you're ready to collect — and not before the payment webhook has been proven end to end.
  </p>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Campaign</th><th>Fund</th><th>Raised</th><th>Target</th><th>Supporters</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($campaigns as $c): $p = Donation::progressPercent($c); ?>
        <tr>
          <td>
            <span class="cell-main"><?= View::e($c['title']) ?></span>
            <?php if ($p !== null): ?><span class="cap" style="display:block"><?= $p ?>% of target</span><?php endif; ?>
          </td>
          <td class="cap"><?= View::e($c['centre_name'] ?? 'General / online') ?></td>
          <td><span class="cell-main"><?= View::e(Money::formatShort((int) $c['raised_amount'], $c['currency'])) ?></span></td>
          <td class="cap"><?= $c['target_amount'] ? View::e(Money::formatShort((int) $c['target_amount'], $c['currency'])) : '—' ?></td>
          <td class="cap"><?= (int) $c['donor_count'] ?></td>
          <td><span class="status-pill <?= $c['status'] === 'published' ? 'success' : ($c['status'] === 'closed' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=donations.campaign&id=<?= (int) $c['id'] ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$campaigns): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No campaigns yet. Create one below — general-fund giving works without a campaign too.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>New campaign</h3></div>
  <form method="post" action="app.php?r=donations.campaigns.store">
    <?= Csrf::field() ?>
    <div class="field"><label>Title</label><input type="text" name="title" required maxlength="200" placeholder="Scholarship Fund 2026"></div>
    <div class="field">
      <label>Summary <span class="cap">(one line, shown on cards)</span></label>
      <input type="text" name="summary" maxlength="500">
    </div>
    <div class="row row-b">
      <div class="field"><label>Target <span class="cap">(optional — omit for no progress bar)</span></label><input type="text" name="target_amount" inputmode="decimal" placeholder="2,000,000"></div>
      <div class="field">
        <label>Attribute to <span class="cap">(§31)</span></label>
        <select name="centre_id">
          <option value="">General / online fund</option>
          <?php foreach ($centres as $ct): ?><option value="<?= (int) $ct['id'] ?>"><?= View::e($ct['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Opens <span class="cap">(optional)</span></label><input type="date" name="starts_on"></div>
      <div class="field"><label>Closes <span class="cap">(optional)</span></label><input type="date" name="ends_on"></div>
    </div>
    <div class="field">
      <label>The case for support</label>
      <textarea name="story" rows="5" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px" placeholder="What the money does, and for whom. Do not claim anything UltrAdemy cannot substantiate (§60)."></textarea>
    </div>
    <div class="field">
      <label style="display:flex;align-items:center;gap:8px;font-weight:500">
        <input type="checkbox" name="show_donor_wall" value="1" checked style="width:auto"> Show a public supporter list
      </label>
    </div>
    <button type="submit" class="btn primary">Create as draft</button>
  </form>
</div>
