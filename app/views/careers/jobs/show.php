<?php
/** @var array<string,mixed> $job @var array<int,array<string,mixed>> $centres
 *  @var string $locationLabel @var bool $acceptingApplications @var bool $isSaved */
?>
<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="app.php">Careers</a> / <a href="app.php?r=jobs">Open Positions</a> / <?= View::e($job['title']) ?></div>
    <span class="eyebrow"><?= View::e($job['department_name'] ?? 'UltrAdemy') ?></span>
    <h1><?= View::e($job['title']) ?></h1>
    <div class="tag-row">
      <span class="badge"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
      <span class="pill"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
      <span class="pill"><?= View::e($locationLabel) ?></span>
      <?php if ($job['category_name']): ?><span class="pill"><?= View::e($job['category_name']) ?></span><?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap detail-layout">
    <div class="prose">
      <?php if ($job['summary']): ?><p><?= nl2br(View::e($job['summary'])) ?></p><?php endif; ?>

      <?php if ($job['responsibilities']): ?>
        <h2>Responsibilities</h2>
        <ul><?php foreach (array_filter(array_map('trim', explode("\n", $job['responsibilities']))) as $line): ?><li><?= View::e($line) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>

      <?php if ($job['requirements']): ?>
        <h2>Requirements</h2>
        <ul><?php foreach (array_filter(array_map('trim', explode("\n", $job['requirements']))) as $line): ?><li><?= View::e($line) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>

      <?php if ($job['qualifications']): ?>
        <h2>Qualifications</h2>
        <p><?= nl2br(View::e($job['qualifications'])) ?></p>
      <?php endif; ?>

      <?php if ($job['skills']): ?>
        <h2>Skills</h2>
        <p><?= nl2br(View::e($job['skills'])) ?></p>
      <?php endif; ?>

      <?php if ($job['experience_requirements']): ?>
        <h2>Experience</h2>
        <p><?= nl2br(View::e($job['experience_requirements'])) ?></p>
      <?php endif; ?>

      <?php if ($job['benefits']): ?>
        <h2>Benefits</h2>
        <p><?= nl2br(View::e($job['benefits'])) ?></p>
      <?php endif; ?>
    </div>

    <aside class="card card-body sticky-side">
      <dl class="spec-list">
        <dt>Location</dt><dd><?= View::e($locationLabel) ?></dd>
        <dt>Employment Type</dt><dd><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></dd>
        <dt>Work Mode</dt><dd><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></dd>
        <?php if ($job['application_deadline']): ?>
          <dt>Application Deadline</dt><dd><?= date('F j, Y', strtotime($job['application_deadline'])) ?></dd>
        <?php endif; ?>
      </dl>

      <?php if ($acceptingApplications): ?>
        <a class="btn btn-primary btn-block" href="<?= Auth::check() ? 'app.php?r=apply&job=' . urlencode($job['slug']) : 'register.php' ?>">Apply For This Position</a>
        <?php if (Auth::check()): ?>
          <form method="post" action="app.php?r=<?= $isSaved ? 'jobs.unsave' : 'jobs.save' ?>" style="margin-top:10px">
            <?= Csrf::field() ?>
            <input type="hidden" name="job_posting_id" value="<?= (int) $job['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-block"><?= $isSaved ? 'Remove from Saved Jobs' : 'Save This Job' ?></button>
          </form>
        <?php else: ?>
          <p class="empty-hint text-center"><a href="login.php">Sign in</a> to save this job</p>
        <?php endif; ?>
      <?php else: ?>
        <button class="btn btn-secondary btn-block" disabled>Applications Closed</button>
      <?php endif; ?>
    </aside>
  </div>
</section>
