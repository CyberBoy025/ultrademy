<?php
/** @var array $centre @var array $counts @var array $rooms @var array $equipment @var array $staff
 *  @var bool $canManage @var bool $canAssignStaff @var array $roles */
$roomStatusColor = ['available' => 'success', 'maintenance' => 'warning', 'retired' => 'neutral'];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=centres" style="color:var(--text-3)">Centres</a> / <?= View::e($centre['name']) ?></span>
    <h1><?= View::e($centre['name']) ?></h1>
    <p><?= View::e($centre['code']) ?> · <?= View::e($centre['city'] ?: 'City TBD') ?>, <?= View::e($centre['state'] ?: '') ?> · Manager: <?= View::e($centre['manager_name'] ?: 'Unassigned') ?></p>
  </div>
</div>

<div class="kpi-grid">
  <div class="card kpi-card"><span class="lab">Rooms</span><span class="val"><?= (int) $counts['rooms'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Staff</span><span class="val"><?= (int) $counts['staff'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Active Students</span><span class="val"><?= (int) $counts['students'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Active Cohorts</span><span class="val"><?= (int) $counts['cohorts'] ?></span></div>
</div>

<?php if ($canManage): ?>
<h2 class="sec-title">Centre Details</h2>
<div class="card" style="margin-bottom:20px">
  <form method="post" action="app.php?r=centres.update">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $centre['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= View::e($centre['name']) ?>" required></div>
      <div class="field"><label>Status</label>
        <select name="status">
          <?php foreach (['planned', 'active', 'inactive'] as $s): ?><option value="<?= $s ?>" <?= $centre['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>City</label><input type="text" name="city" value="<?= View::e($centre['city']) ?>"></div>
      <div class="field"><label>State</label><input type="text" name="state" value="<?= View::e($centre['state']) ?>"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Phone</label><input type="text" name="phone" value="<?= View::e($centre['phone']) ?>"></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= View::e($centre['email']) ?>"></div>
    </div>
    <button type="submit" class="btn primary">Save Changes</button>
  </form>
</div>
<?php endif; ?>

<h2 class="sec-title">Rooms</h2>
<div class="row row-b">
  <div class="card">
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Name</th><th>Type</th><th>Capacity</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($rooms as $r): ?>
          <tr>
            <td><span class="cell-main"><?= View::e($r['name']) ?></span></td>
            <td><?= View::e(ucwords(str_replace('_', ' ', $r['type']))) ?></td>
            <td><?= (int) $r['capacity'] ?></td>
            <td>
              <?php if ($canManage): ?>
              <form method="post" action="app.php?r=rooms.status" style="display:inline-flex;gap:6px;align-items:center">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="centre_id" value="<?= $centre['id'] ?>">
                <select name="status" onchange="this.form.submit()" class="status-pill <?= $roomStatusColor[$r['status']] ?>" style="border:none;cursor:pointer">
                  <?php foreach (['available', 'maintenance', 'retired'] as $s): ?><option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                </select>
              </form>
              <?php else: ?><span class="status-pill <?= $roomStatusColor[$r['status']] ?>"><?= ucfirst($r['status']) ?></span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$rooms): ?><tr><td colspan="4" class="cap" style="padding:14px;text-align:center">No rooms yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($canManage): ?>
  <div class="card">
    <div class="chead"><h3>Add Room</h3></div>
    <form method="post" action="app.php?r=rooms.store">
      <?= Csrf::field() ?>
      <input type="hidden" name="centre_id" value="<?= $centre['id'] ?>">
      <div class="field"><label>Name</label><input type="text" name="name" placeholder="e.g. Room 4" required></div>
      <div class="field"><label>Type</label>
        <select name="type">
          <option value="classroom">Classroom</option><option value="computer_lab">Computer Lab</option>
          <option value="office">Office</option><option value="hall">Hall</option>
        </select>
      </div>
      <div class="field"><label>Capacity</label><input type="number" name="capacity" min="0" value="20"></div>
      <button type="submit" class="btn primary btn-block" style="width:100%;justify-content:center">Add Room</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<h2 class="sec-title">Equipment</h2>
<div class="row row-b">
  <div class="card">
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Asset Tag</th><th>Name</th><th>Room</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($equipment as $e): ?>
          <tr>
            <td><span class="cell-main"><?= View::e($e['asset_tag']) ?></span></td>
            <td><?= View::e($e['name']) ?></td>
            <td><?= View::e($e['room_name'] ?? '—') ?></td>
            <td><span class="status-pill <?= $e['status'] === 'in_service' ? 'success' : 'warning' ?>"><?= View::e(ucwords(str_replace('_', ' ', $e['status']))) ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$equipment): ?><tr><td colspan="4" class="cap" style="padding:14px;text-align:center">No equipment logged yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($canManage): ?>
  <div class="card">
    <div class="chead"><h3>Add Equipment</h3></div>
    <form method="post" action="app.php?r=equipment.store">
      <?= Csrf::field() ?>
      <input type="hidden" name="centre_id" value="<?= $centre['id'] ?>">
      <div class="field"><label>Asset Tag</label><input type="text" name="asset_tag" placeholder="e.g. GWG-PC-014" required></div>
      <div class="field"><label>Name</label><input type="text" name="name" placeholder="e.g. Desktop PC" required></div>
      <div class="field"><label>Room</label>
        <select name="room_id">
          <option value="">— Unassigned —</option>
          <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn primary" style="width:100%;justify-content:center">Add Equipment</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<h2 class="sec-title">Staff</h2>
<div class="row row-b">
  <div class="card">
    <div class="queue">
      <?php foreach ($staff as $s): ?>
      <div class="queue-item">
        <div class="queue-ico" style="background:var(--grad);color:#fff"><?= View::e(strtoupper(mb_substr($s['first_name'] ?? '?', 0, 1) . mb_substr($s['last_name'] ?? '', 0, 1))) ?></div>
        <div class="queue-t"><h4><?= View::e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: $s['email']) ?></h4><p><?= View::e($s['role_names'] ?: 'No role at this centre') ?><?= $s['is_primary'] ? ' · Primary' : '' ?></p></div>
      </div>
      <?php endforeach; ?>
      <?php if (!$staff): ?><p class="cap" style="padding:8px 0">No staff posted here yet.</p><?php endif; ?>
    </div>
  </div>
  <?php if ($canAssignStaff): ?>
  <div class="card">
    <div class="chead"><h3>Assign Staff</h3></div>
    <form method="post" action="app.php?r=staff.assign">
      <?= Csrf::field() ?>
      <input type="hidden" name="centre_id" value="<?= $centre['id'] ?>">
      <div class="field"><label>Staff email</label><input type="email" name="email" placeholder="existing user's email" required></div>
      <div class="field"><label>Role at this centre</label>
        <select name="role_id" required>
          <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);margin-bottom:14px"><input type="checkbox" name="is_primary" style="width:auto"> Primary posting</label>
      <button type="submit" class="btn primary" style="width:100%;justify-content:center">Assign</button>
    </form>
  </div>
  <?php endif; ?>
</div>
