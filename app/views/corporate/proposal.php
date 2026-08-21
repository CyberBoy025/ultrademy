<?php /** @var array $proposal @var array $contacts @var ?int $contractId @var bool $canSign */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=corporate.organisation&id=<?= (int) $proposal['organisation_id'] ?>" style="color:var(--text-3)"><?= View::e($proposal['org_name']) ?></a> / <?= View::e($proposal['reference']) ?></span>
    <h1><?= View::e($proposal['title']) ?></h1>
    <p>
      <?= (int) $proposal['participants'] ?> seat(s) at <?= View::e(Money::format((int) $proposal['unit_amount'], $proposal['currency'])) ?>
      <?php if ((int) $proposal['discount_amount'] > 0): ?> less <?= View::e(Money::format((int) $proposal['discount_amount'], $proposal['currency'])) ?> discount<?php endif; ?>
    </p>
  </div>
  <span class="status-pill <?= $proposal['status'] === 'accepted' ? 'success' : (in_array($proposal['status'], ['declined','expired'], true) ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($proposal['status'])) ?></span>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>Proposal</h3></div>
    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:14px">
      <span class="pct"><?= View::e(Money::format((int) $proposal['total_amount'], $proposal['currency'])) ?></span>
      <span class="cap">total</span>
    </div>
    <?php if ($proposal['scope']): ?>
      <p class="cap" style="margin-bottom:4px">Scope</p>
      <p style="font-size:13px;margin-bottom:14px"><?= nl2br(View::e($proposal['scope'])) ?></p>
    <?php endif; ?>
    <p class="cap">
      Programme: <strong><?= View::e($proposal['programme_title'] ?? 'Bespoke') ?></strong> ·
      Delivery: <strong><?= View::e(ucfirst($proposal['delivery_mode'])) ?></strong> ·
      Centre: <strong><?= View::e($proposal['centre_name'] ?? 'Online') ?></strong><br>
      <?php if ($proposal['starts_on']): ?>Runs <?= View::e(date('d M Y', strtotime((string) $proposal['starts_on']))) ?><?= $proposal['ends_on'] ? ' – ' . View::e(date('d M Y', strtotime((string) $proposal['ends_on']))) : '' ?> · <?php endif; ?>
      <?php if ($proposal['valid_until']): ?>Valid until <strong><?= View::e(date('d M Y', strtotime((string) $proposal['valid_until']))) ?></strong><?php endif; ?>
    </p>
    <?php if ($proposal['decision_note']): ?>
      <p class="cap" style="margin-top:12px">Note: <?= View::e($proposal['decision_note']) ?></p>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Progress</h3></div>
    <?php if ($proposal['status'] === 'draft'): ?>
      <p class="cap" style="margin-bottom:12px">Send it to the client, then record their answer here.</p>
      <form method="post" action="app.php?r=corporate.proposals.status">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $proposal['id'] ?>"><input type="hidden" name="status" value="sent">
        <button type="submit" class="btn primary btn-sm">Mark as sent</button>
      </form>

    <?php elseif ($proposal['status'] === 'sent'): ?>
      <p class="cap" style="margin-bottom:12px">Sent <?= $proposal['sent_at'] ? View::e(date('d M Y', strtotime((string) $proposal['sent_at']))) : '' ?>. Record the client's decision.</p>
      <form method="post" action="app.php?r=corporate.proposals.status" style="margin-bottom:10px">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $proposal['id'] ?>"><input type="hidden" name="status" value="accepted">
        <button type="submit" class="btn primary btn-sm">Client accepted</button>
      </form>
      <form method="post" action="app.php?r=corporate.proposals.status">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $proposal['id'] ?>"><input type="hidden" name="status" value="declined">
        <div class="field"><label>Reason</label><input type="text" name="note" maxlength="255"></div>
        <button type="submit" class="btn sm">Client declined</button>
      </form>

    <?php elseif ($proposal['status'] === 'accepted'): ?>
      <?php if ($contractId): ?>
        <p class="cap" style="margin-bottom:12px">A contract has been raised from this proposal.</p>
        <a class="btn primary btn-sm" href="app.php?r=corporate.contract&id=<?= (int) $contractId ?>">Open contract</a>
      <?php elseif ($canSign): ?>
        <p class="cap" style="margin-bottom:12px">
          Raising the contract creates a private cohort for this client and issues the invoice
          to their billing contact. All three happen together or not at all.
        </p>
        <?php if (!$contacts): ?>
          <p class="cap" style="color:var(--error)">Add a contact to <?= View::e($proposal['org_name']) ?> first — the invoice needs somebody to address.</p>
        <?php elseif (empty($proposal['programme_id'])): ?>
          <p class="cap" style="color:var(--error)">This proposal has no programme attached. The cohort needs one.</p>
        <?php else: ?>
          <form method="post" action="app.php?r=corporate.contracts.create"
                onsubmit="return confirm('Raise the contract, create the cohort and issue the invoice?')">
            <?= Csrf::field() ?><input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
            <button type="submit" class="btn primary">Raise contract</button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <p class="cap" style="margin:0">Accepted. Raising the contract commits the company, so it needs management.</p>
      <?php endif; ?>

    <?php else: ?>
      <p class="cap" style="margin:0">This proposal is <?= View::e($proposal['status']) ?> and no longer live.</p>
    <?php endif; ?>
  </div>
</div>
