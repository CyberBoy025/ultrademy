<?php /** @var array $sessions */ ?>
<div class="topbar">
  <div><h1>Timetable</h1><p>Sessions in the next two weeks. This table <em>is</em> the timetable (data-model.md §5) — there's no separate schedule to keep in sync.</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>When</th><th>Programme / Group</th><th>Centre</th><th>Room</th><th>Instructor</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e(date('D, d M', strtotime($s['starts_at']))) ?></span><span class="cell-sub"><?= View::e(date('H:i', strtotime($s['starts_at']))) ?>–<?= View::e(date('H:i', strtotime($s['ends_at']))) ?></span></td>
          <td><span class="cell-main"><?= View::e($s['programme_title']) ?></span><span class="cell-sub"><?= View::e($s['group_name']) ?> · <?= View::e($s['topic'] ?: 'No topic set') ?></span></td>
          <td><?= View::e($s['centre_name'] ?? 'Online') ?></td>
          <td><?= View::e($s['room_name'] ?? '—') ?></td>
          <td><?= View::e($s['instructor_name'] ?: '—') ?></td>
          <td><span class="status-pill info"><?= View::e(ucfirst($s['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No sessions scheduled in this window.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
