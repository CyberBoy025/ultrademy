<?php /** @var array $org @var array $contacts @var array $proposals @var array $contracts */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=corporate.organisations" style="color:var(--text-3)">Organisations</a> / <?= View::e($org['name']) ?></span>
    <h1><?= View::e($org['name']) ?></h1>
    <p><?= View::e(ucfirst($org['type'])) ?><?= $org['industry'] ? ' · ' . View::e($org['industry']) : '' ?><?= $org['city'] ? ' · ' . View::e($org['city']) : '' ?></p>
  </div>
  <span class="status-pill <?= $org['status'] === 'active' ? 'success' : ($org['status'] === 'blocked' ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($org['status'])) ?></span>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>Details</h3></div>
    <form method="post" action="app.php?r=corporate.organisations.update">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $org['id'] ?>">
      <div class="row row-b">
        <div class="field"><label>Name</label><input type="text" name="name" required value="<?= View::e($org['name']) ?>"></div>
        <div class="field">
          <label>Status</label>
          <select name="status">
            <?php foreach (['prospect'=>'Prospect','active'=>'Active','dormant'=>'Dormant','blocked'=>'Blocked'] as $k=>$l): ?>
              <option value="<?= $k ?>" <?= $org['status'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row row-b">
        <div class="field">
          <label>Type</label>
          <select name="type">
            <?php foreach (['company'=>'Company','bank'=>'Bank','government'=>'Government agency','parastatal'=>'Parastatal','ngo'=>'NGO','institution'=>'Institution','other'=>'Other'] as $k=>$l): ?>
              <option value="<?= $k ?>" <?= $org['type'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Industry</label><input type="text" name="industry" value="<?= View::e((string) $org['industry']) ?>"></div>
      </div>
      <div class="row row-b">
        <div class="field"><label>RC number</label><input type="text" name="registration_no" value="<?= View::e((string) $org['registration_no']) ?>"></div>
        <div class="field"><label>Website</label><input type="text" name="website" value="<?= View::e((string) $org['website']) ?>"></div>
      </div>
      <div class="row row-b">
        <div class="field"><label>City</label><input type="text" name="city" value="<?= View::e((string) $org['city']) ?>"></div>
        <div class="field"><label>State</label><input type="text" name="state" value="<?= View::e((string) $org['state']) ?>"></div>
      </div>
      <div class="field"><label>Address</label><input type="text" name="address_line" value="<?= View::e((string) $org['address_line']) ?>"></div>
      <div class="field">
        <label>Notes</label>
        <textarea name="notes" rows="3" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e((string) $org['notes']) ?></textarea>
      </div>
      <button type="submit" class="btn primary">Save</button>
    </form>
  </div>

  <div class="card">
    <div class="chead"><h3>Contacts</h3></div>
    <?php if ($contacts): ?>
    <div class="queue" style="margin-bottom:14px">
      <?php foreach ($contacts as $c): ?>
      <div class="queue-item">
        <div class="queue-t">
          <h4><?= View::e($c['name']) ?><?= (int) $c['is_billing'] === 1 ? ' · billing' : '' ?><?= (int) $c['is_primary'] === 1 ? ' · primary' : '' ?></h4>
          <p><?= View::e($c['email']) ?><?= $c['job_title'] ? ' · ' . View::e($c['job_title']) : '' ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="cap" style="margin-bottom:14px">
        No contacts yet. A contract cannot be raised without one — the invoice needs somebody to address.
      </p>
    <?php endif; ?>

    <form method="post" action="app.php?r=corporate.contacts.store">
      <?= Csrf::field() ?><input type="hidden" name="organisation_id" value="<?= (int) $org['id'] ?>">
      <div class="row row-b">
        <div class="field"><label>Name</label><input type="text" name="name" required maxlength="150"></div>
        <div class="field"><label>Email</label><input type="email" name="email" required maxlength="255"></div>
      </div>
      <div class="row row-b">
        <div class="field"><label>Job title</label><input type="text" name="job_title" maxlength="120"></div>
        <div class="field"><label>Phone</label><input type="tel" name="phone" maxlength="32"></div>
      </div>
      <div class="field" style="flex-direction:row;gap:18px">
        <label style="display:flex;align-items:center;gap:8px;font-weight:500"><input type="checkbox" name="is_primary" value="1" style="width:auto"> Primary</label>
        <label style="display:flex;align-items:center;gap:8px;font-weight:500"><input type="checkbox" name="is_billing" value="1" style="width:auto"> Receives invoices</label>
      </div>
      <button type="submit" class="btn primary btn-sm">Add contact</button>
    </form>
  </div>
</div>

<?php if ($proposals): ?>
<h2 class="sec-title">Proposals</h2>
<div class="card" style="margin-bottom:16px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Title</th><th>Seats</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($proposals as $p): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['reference']) ?></span></td>
          <td><?= View::e($p['title']) ?></td>
          <td class="cap"><?= (int) $p['participants'] ?></td>
          <td><?= View::e(Money::format((int) $p['total_amount'], $p['currency'])) ?></td>
          <td><span class="status-pill <?= $p['status'] === 'accepted' ? 'success' : (in_array($p['status'], ['declined','expired'], true) ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($p['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.proposal&id=<?= (int) $p['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($contracts): ?>
<h2 class="sec-title">Contracts</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Title</th><th>Seats</th><th>Value</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($contracts as $c): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($c['reference']) ?></span></td>
          <td><?= View::e($c['title']) ?></td>
          <td class="cap"><?= (int) $c['active_participants'] ?> / <?= (int) $c['participants_cap'] ?></td>
          <td><?= View::e(Money::format((int) $c['total_amount'], $c['currency'])) ?></td>
          <td><span class="status-pill <?= in_array($c['status'], ['active','delivering'], true) ? 'success' : 'neutral' ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.contract&id=<?= (int) $c['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
