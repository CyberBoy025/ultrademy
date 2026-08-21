<?php /** @var array $campaign @var array $centres @var ?int $progress @var array $wall */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=donations.campaigns" style="color:var(--text-3)">Campaigns</a> / <?= View::e($campaign['title']) ?></span>
    <h1><?= View::e($campaign['title']) ?></h1>
    <p>
      <?= View::e(Money::format((int) $campaign['raised_amount'], $campaign['currency'])) ?> raised from
      <?= (int) $campaign['donor_count'] ?> supporter(s)<?= $progress !== null ? ' · ' . $progress . '% of target' : '' ?>
    </p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="status-pill <?= $campaign['status'] === 'published' ? 'success' : ($campaign['status'] === 'closed' ? 'neutral' : 'warning') ?>"><?= View::e(ucfirst($campaign['status'])) ?></span>
    <?php foreach (['draft' => 'Unpublish', 'published' => 'Publish', 'closed' => 'Close'] as $s => $label): ?>
      <?php if ($campaign['status'] !== $s): ?>
      <form method="post" action="app.php?r=donations.campaigns.status" style="display:inline">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>"><input type="hidden" name="status" value="<?= $s ?>">
        <button type="submit" class="btn sm <?= $s === 'published' ? 'primary' : '' ?>"><?= $label ?></button>
      </form>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($campaign['status'] === 'published'): ?>
      <a class="btn sm" href="donate.php?c=<?= View::e($campaign['slug']) ?>" target="_blank" rel="noopener">View public page</a>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Campaign</h3></div>
  <form method="post" action="app.php?r=donations.campaigns.update">
    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $campaign['id'] ?>">
    <div class="field"><label>Title</label><input type="text" name="title" required maxlength="200" value="<?= View::e($campaign['title']) ?>"></div>
    <div class="field"><label>Summary</label><input type="text" name="summary" maxlength="500" value="<?= View::e((string) $campaign['summary']) ?>"></div>
    <div class="row row-b">
      <div class="field"><label>Target</label><input type="text" name="target_amount" inputmode="decimal" value="<?= $campaign['target_amount'] ? Money::toMajorString((int) $campaign['target_amount']) : '' ?>"></div>
      <div class="field">
        <label>Attribute to</label>
        <select name="centre_id">
          <option value="">General / online fund</option>
          <?php foreach ($centres as $ct): ?>
            <option value="<?= (int) $ct['id'] ?>" <?= (int) $campaign['centre_id'] === (int) $ct['id'] ? 'selected' : '' ?>><?= View::e($ct['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Opens</label><input type="date" name="starts_on" value="<?= View::e((string) $campaign['starts_on']) ?>"></div>
      <div class="field"><label>Closes</label><input type="date" name="ends_on" value="<?= View::e((string) $campaign['ends_on']) ?>"></div>
    </div>
    <div class="field">
      <label>The case for support</label>
      <textarea name="story" rows="8" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e((string) $campaign['story']) ?></textarea>
    </div>
    <div class="field">
      <label style="display:flex;align-items:center;gap:8px;font-weight:500">
        <input type="checkbox" name="show_donor_wall" value="1" style="width:auto" <?= (int) $campaign['show_donor_wall'] === 1 ? 'checked' : '' ?>> Show a public supporter list
      </label>
    </div>
    <button type="submit" class="btn primary">Save</button>
  </form>
</div>

<?php if ($wall): ?>
<div class="card">
  <div class="chead"><h3>Recent supporters</h3></div>
  <div class="queue">
    <?php foreach ($wall as $w): ?>
    <div class="queue-item">
      <div class="queue-t">
        <h4><?= $w['donor_name'] !== null ? View::e($w['donor_name']) : 'Anonymous' ?></h4>
        <p><?= View::e(Money::format((int) $w['amount'], $w['currency'])) ?><?= $w['completed_at'] ? ' · ' . View::e(date('d M Y', strtotime((string) $w['completed_at']))) : '' ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
