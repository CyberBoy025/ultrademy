<?php /** @var array<string,mixed> $app @var array<int,array<string,mixed>> $interviews */ ?>
<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="app.php?r=applications">My Applications</a> / <?= View::e($app['reference']) ?></div>
    <span class="eyebrow"><?= View::e($app['reference']) ?></span>
    <h1><?= View::e($app['job_title']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <?php /* .filters/.chip is the site's pill row; .active marks where the candidate is
             now and .is-past marks stages already cleared. */ ?>
    <div class="filters">
      <?php
      $order = ['submitted', 'received', 'under_review', 'shortlisted', 'interview', 'assessment', 'final_review', 'selected'];
      $terminal = in_array($app['status'], ['rejected', 'withdrawn', 'closed'], true);
      $currentIdx = array_search($app['status'], $order, true);
      foreach ($order as $i => $s):
          $cls = $terminal ? '' : ($i < $currentIdx ? 'is-past' : ($i === $currentIdx ? 'active' : ''));
      ?>
        <span class="chip <?= $cls ?>"><?= View::e(JobApplication::STATUS_LABELS[$s]) ?></span>
      <?php endforeach; ?>
      <?php if ($terminal): ?><span class="chip active"><?= View::e(JobApplication::STATUS_LABELS[$app['status']]) ?></span><?php endif; ?>
    </div>

    <div class="card card-body form-card">
      <dl class="spec-grid">
        <div><dt>Status</dt><dd><?= View::e(JobApplication::STATUS_LABELS[$app['status']] ?? $app['status']) ?></dd></div>
        <div><dt>Applied</dt><dd><?= $app['submitted_at'] ? date('F j, Y', strtotime($app['submitted_at'])) : '—' ?></dd></div>
        <div><dt>Last Updated</dt><dd><?= date('F j, Y', strtotime($app['updated_at'])) ?></dd></div>
      </dl>
    </div>

    <?php if ($interviews): ?>
      <?php foreach ($interviews as $iv): ?>
        <div class="card card-body form-card">
          <h2>Interview <?= View::e(Interview::STATUSES[$iv['status']] ?? $iv['status']) ?></h2>
          <?php if (in_array($iv['status'], ['cancelled'], true)): ?>
            <p class="form-hint" style="margin:0">This interview was cancelled. We'll be in touch if a new time is scheduled.</p>
          <?php else: ?>
            <dl class="spec-grid">
              <div><dt>Date &amp; Time</dt><dd><?= $iv['scheduled_at'] ? View::e(date('F j, Y — g:ia', strtotime($iv['scheduled_at']))) : 'To be confirmed' ?></dd></div>
              <div><dt>Type</dt><dd><?= View::e(Interview::TYPES[$iv['type']] ?? $iv['type']) ?></dd></div>
              <?php if ($iv['type'] === 'online' && $iv['meeting_link']): ?>
                <div class="field-full"><dt>Meeting Link</dt><dd><a href="<?= View::e($iv['meeting_link']) ?>" target="_blank" rel="noopener"><?= View::e($iv['meeting_link']) ?></a></dd></div>
              <?php elseif ($iv['location']): ?>
                <div class="field-full"><dt>Location</dt><dd><?= View::e($iv['location']) ?></dd></div>
              <?php endif; ?>
              <?php if ($iv['instructions']): ?>
                <div class="field-full"><dt>Instructions</dt><dd><?= nl2br(View::e($iv['instructions'])) ?></dd></div>
              <?php endif; ?>
            </dl>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (in_array($app['status'], JobApplication::OPEN_STATUSES, true)): ?>
      <form method="post" action="app.php?r=applications.withdraw" onsubmit="return confirm('Withdraw this application? This cannot be undone.')">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
        <button type="submit" class="btn btn-secondary">Withdraw Application</button>
      </form>
    <?php endif; ?>

    <p style="margin-top:20px"><a href="app.php?r=applications">&larr; Back to My Applications</a></p>
  </div>
</section>
