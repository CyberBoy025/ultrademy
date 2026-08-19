<?php /** @var array<int,array<string,mixed>> $applications */ ?>
<div class="cw-narrow" style="padding:40px 24px 60px">
  <h1 style="font-size:1.6rem;margin-bottom:8px">My Applications</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:26px">Every position you've applied for, and where it stands.</p>

  <?php if (!$applications): ?>
    <div class="cw-empty">
      <p>You haven't submitted any applications yet.</p>
      <p style="margin-top:10px"><a class="cw-btn cw-btn-primary" href="app.php?r=jobs">Browse Open Positions</a></p>
    </div>
  <?php else: ?>
    <?php foreach ($applications as $a): ?>
      <div class="cw-job-card" style="margin-bottom:14px">
        <div class="cw-job-card-dept"><?= View::e($a['reference']) ?></div>
        <h3><a href="app.php?r=applications.show&id=<?= (int) $a['id'] ?>"><?= View::e($a['job_title']) ?></a></h3>
        <div class="cw-tag-row">
          <span class="cw-tag cw-tag-accent"><?= View::e(JobApplication::STATUS_LABELS[$a['status']] ?? $a['status']) ?></span>
        </div>
        <div class="cw-job-card-foot">
          <span>Applied <?= $a['submitted_at'] ? date('M j, Y', strtotime($a['submitted_at'])) : '—' ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
