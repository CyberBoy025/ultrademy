<?php /** @var array<int,array<string,mixed>> $logs @var string $status */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.emailtemplates" style="color:var(--text-3)">Email Templates</a> / Log</span>
    <h1>Recruitment — Email Log</h1>
    <p>Every recruitment email triggered, and its delivery status.</p>
  </div>
</div>

<div class="callout" style="background:var(--bg-2,rgba(127,127,127,.08));border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px">
  Every entry below is queued in the shared notification system. No mail transport is configured on this platform yet, so nothing actually leaves the building until one is — this log is accurate about that, not a simulation of delivery.
</div>

<div class="filters">
  <?php foreach (['' => 'All', 'queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed'] as $val => $label): ?>
    <a class="chip <?= $status === $val ? 'active' : '' ?>" href="app.php?r=recruitment.emaillogs<?= $val ? '&status=' . $val : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>When</th><th>Recipient</th><th>Subject</th><th>Template</th><th>Application</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="cap"><?= View::e(date('d M Y H:i', strtotime($l['created_at']))) ?></td>
          <td><?= View::e($l['recipient_email']) ?></td>
          <td><?= View::e($l['subject']) ?></td>
          <td class="cap"><?= View::e($l['template_code']) ?></td>
          <td><?= $l['reference'] ? '<a href="app.php?r=recruitment.applications.show&id=' . (int) $l['job_application_id'] . '">' . View::e($l['reference']) . '</a>' : '—' ?></td>
          <td><span class="status-pill <?= $l['status'] === 'sent' ? 'success' : ($l['status'] === 'failed' ? 'error' : 'warning') ?>"><?= ucfirst($l['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No emails logged yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
