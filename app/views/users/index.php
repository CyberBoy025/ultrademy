<?php /** @var array $users @var bool $canCreate @var array $roles @var array $centres */
$statusColor = ['pending' => 'warning', 'active' => 'success', 'suspended' => 'error', 'closed' => 'neutral'];
?>
<div class="topbar">
  <div><h1>Users</h1><p>Every UltrAdemy account — roles are additive across centres (README §3).</p></div>
</div>

<?php if ($canCreate): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New User</h3></div>
  <form method="post" action="app.php?r=users.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>First name</label><input type="text" name="first_name" required></div>
      <div class="field"><label>Last name</label><input type="text" name="last_name" required></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Temporary password</label><input type="text" name="password" required minlength="8"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Role</label>
        <select name="role_id" required>
          <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Centre (if role is centre-scoped)</label>
        <select name="centre_id">
          <option value="">— None / Global —</option>
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit" class="btn primary">Create User</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Joined</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="cell-main"><?= View::e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '—') ?></span></td>
          <td><?= View::e($u['email']) ?></td>
          <td><?= View::e(implode(', ', array_unique(array_column($u['roles'], 'name'))) ?: '—') ?></td>
          <td><span class="status-pill <?= $statusColor[$u['status']] ?>"><?= View::e(ucfirst($u['status'])) ?></span></td>
          <td><?= View::e(date('d M Y', strtotime($u['created_at']))) ?></td>
          <td>
            <?php if ($canCreate): ?>
            <form method="post" action="app.php?r=users.status" style="display:inline">
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <input type="hidden" name="status" value="<?= $u['status'] === 'suspended' ? 'active' : 'suspended' ?>">
              <button type="submit" class="btn sm"><?= $u['status'] === 'suspended' ? 'Reinstate' : 'Suspend' ?></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
