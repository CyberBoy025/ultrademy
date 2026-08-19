<?php /** @var array $sessions @var bool $canMark */ ?>
<div class="topbar">
  <div><h1>Attendance</h1><p>Sessions in the last day through the next 30 days.</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>When</th><th>Programme / Group</th><th>Centre</th><th>Attendance</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e(date('D, d M · H:i', strtotime($s['starts_at']))) ?></span></td>
          <td><span class="cell-main"><?= View::e($s['programme_title']) ?></span><span class="cell-sub"><?= View::e($s['group_name']) ?></span></td>
          <td><?= View::e($s['centre_name'] ?? 'Online') ?></td>
          <td><?= $s['rate'] === null ? '<span class="status-pill neutral">Not marked</span>' : '<span class="status-pill success">' . $s['rate'] . '% present</span>' ?></td>
          <td><?php if ($canMark): ?><a class="btn sm primary" href="app.php?r=attendance.mark&session_id=<?= $s['id'] ?>">Mark</a><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No sessions in this window.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
