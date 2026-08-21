<?php /** @var array $pipeline @var array $requests @var array $proposals @var array $contracts @var bool $enabled */ ?>
<div class="topbar">
  <div><h1>Corporate Training</h1><p>Requests, proposals and contracts.</p></div>
  <div style="display:flex;gap:8px">
    <a class="btn sm" href="app.php?r=corporate.organisations">Organisations</a>
    <a class="btn sm" href="app.php?r=corporate.requests">Requests</a>
    <a class="btn sm" href="app.php?r=corporate.contracts">Contracts</a>
  </div>
</div>

<?php if (!$enabled): ?>
<div class="card" style="margin-bottom:20px;border-left:3px solid var(--warning)">
  <p class="cap" style="margin:0">
    <strong>Corporate training is switched off.</strong> The public enquiry form is closed;
    the pipeline here still works so you can record enquiries that arrive by other routes.
    Turn on <code>corporate_enabled</code> in <a href="app.php?r=settings" style="color:var(--brand-cyan-text)">Settings</a> to publish the form.
  </p>
</div>
<?php endif; ?>

<div class="row row-b" style="margin-bottom:16px">
  <div class="card"><div class="chead"><h3>Open requests</h3></div><span class="pct"><?= (int) $pipeline['open_requests'] ?></span><span class="cap"><?= (int) $pipeline['new_requests'] ?> untriaged</span></div>
  <div class="card"><div class="chead"><h3>Proposals out</h3></div><span class="pct"><?= (int) $pipeline['sent_proposals'] ?></span><span class="cap"><?= View::e(Money::formatShort((int) $pipeline['proposal_value'])) ?> quoted</span></div>
</div>
<div class="row row-b" style="margin-bottom:20px">
  <div class="card"><div class="chead"><h3>Active contracts</h3></div><span class="pct"><?= (int) $pipeline['active_contracts'] ?></span><span class="cap"><?= View::e(Money::formatShort((int) $pipeline['contract_value'])) ?> contracted</span></div>
  <div class="card"><div class="chead"><h3>Participants</h3></div><span class="pct"><?= (int) $pipeline['participants'] ?></span><span class="cap">currently training</span></div>
</div>

<h2 class="sec-title">Latest requests</h2>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Organisation</th><th>Programme</th><th>Participants</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['reference']) ?></span><span class="cap" style="display:block"><?= View::e(date('d M Y', strtotime((string) $r['created_at']))) ?></span></td>
          <td><?= View::e($r['org_name'] ?? $r['organisation_name']) ?><?= $r['organisation_id'] ? '' : ' <span class="cap">(unlinked)</span>' ?></td>
          <td class="cap"><?= View::e($r['programme_title'] ?? 'Bespoke') ?></td>
          <td class="cap"><?= $r['participants'] ? (int) $r['participants'] : '—' ?></td>
          <td><span class="status-pill <?= $r['status'] === 'won' ? 'success' : ($r['status'] === 'lost' ? 'error' : ($r['status'] === 'new' ? 'warning' : 'neutral')) ?>"><?= View::e(ucfirst($r['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.request&id=<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$requests): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No requests yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<h2 class="sec-title">Contracts</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Client</th><th>Seats</th><th>Value</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($contracts as $c): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($c['reference']) ?></span></td>
          <td><?= View::e($c['org_name']) ?></td>
          <td class="cap"><?= (int) $c['active_participants'] ?> / <?= (int) $c['participants_cap'] ?></td>
          <td><?= View::e(Money::formatShort((int) $c['total_amount'], $c['currency'])) ?></td>
          <td><span class="status-pill <?= in_array($c['status'], ['active','delivering'], true) ? 'success' : ($c['status'] === 'cancelled' ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.contract&id=<?= (int) $c['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$contracts): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No contracts yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
