<?php /** @var array $request @var array $organisations @var array $programmes @var array $centres
 *  @var array $proposals @var bool $canPropose */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=corporate.requests" style="color:var(--text-3)">Requests</a> / <?= View::e($request['reference']) ?></span>
    <h1><?= View::e($request['org_name'] ?? $request['organisation_name']) ?></h1>
    <p><?= View::e($request['contact_name']) ?> · <?= View::e($request['contact_email']) ?><?= $request['contact_phone'] ? ' · ' . View::e($request['contact_phone']) : '' ?></p>
  </div>
  <span class="status-pill <?= $request['status'] === 'won' ? 'success' : ($request['status'] === 'lost' ? 'error' : 'warning') ?>"><?= View::e(ucfirst($request['status'])) ?></span>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>What they asked for</h3></div>
    <p class="cap" style="margin-bottom:4px">Requirements</p>
    <p style="font-size:13px;margin-bottom:14px"><?= nl2br(View::e((string) ($request['requirements'] ?: '—'))) ?></p>
    <p class="cap">
      Programme: <strong><?= View::e($request['programme_title'] ?? 'Bespoke') ?></strong> ·
      Participants: <strong><?= $request['participants'] ? (int) $request['participants'] : 'unspecified' ?></strong> ·
      Mode: <strong><?= View::e(ucfirst($request['delivery_mode'])) ?></strong>
      <?php if ($request['preferred_start']): ?> · Preferred start: <strong><?= View::e(date('d M Y', strtotime((string) $request['preferred_start']))) ?></strong><?php endif; ?>
      · Source: <strong><?= View::e(str_replace('_', ' ', $request['source'])) ?></strong>
    </p>
  </div>

  <div class="card">
    <div class="chead"><h3>Triage</h3></div>
    <form method="post" action="app.php?r=corporate.requests.update">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
      <div class="field">
        <label>Link to organisation <?= $request['organisation_id'] ? '' : '<span class="cap">— required before quoting</span>' ?></label>
        <select name="organisation_id">
          <option value="">— not linked —</option>
          <?php foreach ($organisations as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) $request['organisation_id'] === (int) $o['id'] ? 'selected' : '' ?>><?= View::e($o['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="cap">Not on the list? <a href="app.php?r=corporate.organisations" style="color:var(--brand-cyan-text)">Add it first</a>.</span>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <?php foreach (['reviewing'=>'Reviewing','proposed'=>'Proposed','won'=>'Won','lost'=>'Lost','withdrawn'=>'Withdrawn'] as $k=>$l): ?>
            <option value="<?= $k ?>" <?= $request['status'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Note <span class="cap">(recorded if marking lost)</span></label><input type="text" name="note" maxlength="255"></div>
      <button type="submit" class="btn primary btn-sm">Update</button>
    </form>
  </div>
</div>

<?php if ($canPropose): ?>
<div class="card">
  <div class="chead"><h3>Draft a proposal</h3></div>
  <?php if (!$request['organisation_id']): ?>
    <p class="cap" style="margin:0">Link this request to an organisation first — a proposal needs somebody to send it to.</p>
  <?php else: ?>
  <form method="post" action="app.php?r=corporate.proposals.store">
    <?= Csrf::field() ?>
    <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
    <input type="hidden" name="organisation_id" value="<?= (int) $request['organisation_id'] ?>">
    <div class="field"><label>Title</label><input type="text" name="title" required maxlength="200" value="<?= View::e(($request['programme_title'] ?? 'Bespoke training') . ' for ' . ($request['org_name'] ?? $request['organisation_name'])) ?>"></div>
    <div class="row row-b">
      <div class="field">
        <label>Programme <span class="cap">— required to raise a contract later</span></label>
        <select name="programme_id">
          <option value="">— bespoke, no programme —</option>
          <?php foreach ($programmes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) $request['programme_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= View::e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Participants</label><input type="number" name="participants" min="1" max="5000" value="<?= (int) ($request['participants'] ?: 1) ?>" required></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Price per seat</label><input type="text" name="unit_amount" inputmode="decimal" placeholder="75,000" required></div>
      <div class="field"><label>Discount <span class="cap">(total, optional)</span></label><input type="text" name="discount_amount" inputmode="decimal"></div>
    </div>
    <div class="row row-b">
      <div class="field">
        <label>Delivery</label>
        <select name="delivery_mode">
          <?php foreach (['physical'=>'Physical','online'=>'Online','hybrid'=>'Hybrid'] as $k=>$l): ?>
            <option value="<?= $k ?>" <?= $request['delivery_mode'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Centre <span class="cap">— blank for online</span></label>
        <select name="centre_id">
          <option value="">Online / not centre-based</option>
          <?php foreach ($centres as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $request['centre_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Starts</label><input type="date" name="starts_on" value="<?= View::e((string) $request['preferred_start']) ?>"></div>
      <div class="field"><label>Ends</label><input type="date" name="ends_on"></div>
    </div>
    <div class="field">
      <label>Scope</label>
      <textarea name="scope" rows="4" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px" placeholder="What is delivered, over how long, and what the client gets at the end."></textarea>
    </div>
    <button type="submit" class="btn primary">Create draft proposal</button>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
