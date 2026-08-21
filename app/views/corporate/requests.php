<?php /** @var array $requests @var string $status */ ?>
<div class="topbar">
  <div><h1>Training Requests</h1><p><?= count($requests) ?> request(s).</p></div>
  <a class="btn sm" href="app.php?r=corporate">Pipeline</a>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="r" value="corporate.requests">
    <div class="field" style="margin:0;min-width:180px">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['' => 'All', 'new' => 'New', 'reviewing' => 'Reviewing', 'proposed' => 'Proposed', 'won' => 'Won', 'lost' => 'Lost', 'withdrawn' => 'Withdrawn'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Organisation</th><th>Contact</th><th>Programme</th><th>Seats</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['reference']) ?></span><span class="cap" style="display:block"><?= View::e(date('d M Y', strtotime((string) $r['created_at']))) ?></span></td>
          <td><?= View::e($r['org_name'] ?? $r['organisation_name']) ?><?php if (!$r['organisation_id']): ?><span class="status-pill warning" style="margin-left:6px">unlinked</span><?php endif; ?></td>
          <td class="cap"><?= View::e($r['contact_name']) ?><span style="display:block"><?= View::e($r['contact_email']) ?></span></td>
          <td class="cap"><?= View::e($r['programme_title'] ?? 'Bespoke') ?></td>
          <td class="cap"><?= $r['participants'] ? (int) $r['participants'] : '—' ?></td>
          <td><span class="status-pill <?= $r['status'] === 'won' ? 'success' : ($r['status'] === 'lost' ? 'error' : ($r['status'] === 'new' ? 'warning' : 'neutral')) ?>"><?= View::e(ucfirst($r['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.request&id=<?= (int) $r['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$requests): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No requests.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>Log a request</h3><span class="cap">for enquiries that arrive by phone or email</span></div>
  <form method="post" action="app.php?r=corporate.requests.store">
    <?= Csrf::field() ?>
    <div class="row row-b">
      <div class="field"><label>Organisation</label><input type="text" name="organisation_name" required maxlength="200"></div>
      <div class="field"><label>Participants</label><input type="number" name="participants" min="1" max="5000"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Contact name</label><input type="text" name="contact_name" required maxlength="150"></div>
      <div class="field"><label>Contact email</label><input type="email" name="contact_email" required maxlength="255"></div>
    </div>
    <div class="field">
      <label>What do they need?</label>
      <textarea name="requirements" rows="3" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>
    <button type="submit" class="btn primary">Log request</button>
  </form>
</div>
