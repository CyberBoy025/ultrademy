<?php /** @var array $staff */ ?>
<div class="topbar">
  <div><h1>Staff</h1><p>Everyone posted to a centre, across the network. Assign staff from a centre's own page.</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Name</th><th>Email</th><th>Centre</th><th>Role(s)</th><th>Posting</th></tr></thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: '—') ?></span></td>
          <td><?= View::e($s['email']) ?></td>
          <td><a href="app.php?r=centres.show&id=<?= $s['centre_id'] ?>"><?= View::e($s['centre_name']) ?></a></td>
          <td><?= View::e($s['role_names'] ?: '—') ?></td>
          <td><?= $s['is_primary'] ? '<span class="status-pill info">Primary</span>' : '<span class="status-pill neutral">Secondary</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$staff): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No staff postings visible.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
