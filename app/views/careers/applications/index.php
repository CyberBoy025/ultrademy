<?php /** @var array<int,array<string,mixed>> $applications */ ?>
<section class="page-hero">
  <div class="wrap">
    <span class="eyebrow">Your Careers Account</span>
    <h1>My Applications</h1>
    <p>Every position you've applied for, and where it stands.</p>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <?php if (!$applications): ?>
      <div class="empty-card">
        <b>You haven't submitted any applications yet.</b>
        <p style="margin-top:14px"><a class="btn btn-primary" href="app.php?r=jobs">Browse Open Positions</a></p>
      </div>
    <?php else: ?>
      <div class="stack">
        <?php foreach ($applications as $a): ?>
          <article class="card job-card">
            <div class="card-body">
              <div class="dept"><?= View::e($a['reference']) ?></div>
              <h3><a href="app.php?r=applications.show&id=<?= (int) $a['id'] ?>"><?= View::e($a['job_title']) ?></a></h3>
              <div class="tag-row">
                <span class="badge"><?= View::e(JobApplication::STATUS_LABELS[$a['status']] ?? $a['status']) ?></span>
              </div>
              <div class="prog-meta">
                <span>Applied <?= $a['submitted_at'] ? date('M j, Y', strtotime($a['submitted_at'])) : '—' ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
