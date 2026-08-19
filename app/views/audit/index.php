<?php /** @var array $logs @var int $total */ ?>
<div class="topbar">
  <div><h1>Audit Log</h1><p>Insert-only trail — <?= number_format($total) ?> record(s) total. Showing the most recent 100.</p></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Record</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td><?= View::e(date('d M Y · H:i', strtotime($l['created_at']))) ?></td>
          <td><?= View::e($l['actor_name'] ?: 'System') ?></td>
          <td><span class="status-pill info"><?= View::e($l['action']) ?></span></td>
          <td class="cap"><?= View::e($l['auditable_type']) ?> #<?= (int) $l['auditable_id'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="4" class="cap" style="padding:16px;text-align:center">No activity recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
