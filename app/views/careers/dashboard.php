<?php
/** @var array<int,array<string,mixed>> $savedJobs @var array<int,array<string,mixed>> $applications
 *  @var int $openCount @var int $completionPct */
?>
<section class="page-hero">
  <div class="wrap">
    <span class="eyebrow">Your Careers Account</span>
    <h1>Welcome back, <?= View::e(Auth::name()) ?>.</h1>
    <p>Here's where things stand.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="wrap">
    <div class="grid grid-3">
      <div class="card card-body">
        <div class="stat-label">Profile</div>
        <div class="stat"><?= $completionPct ?>%</div>
        <p class="empty-hint">Complete — <a href="app.php?r=profile.personal">finish your profile</a></p>
      </div>
      <div class="card card-body">
        <div class="stat-label">Applications</div>
        <div class="stat"><?= $openCount ?></div>
        <p class="empty-hint">Active — <a href="app.php?r=applications">view all</a></p>
      </div>
      <div class="card card-body">
        <div class="stat-label">Saved Jobs</div>
        <div class="stat"><?= count($savedJobs) ?></div>
        <p class="empty-hint">Positions you've bookmarked to apply for later.</p>
      </div>
    </div>
  </div>
</section>

<?php if ($applications): ?>
<section class="section section-tight">
  <div class="wrap">
    <div class="section-head"><h2>Recent Applications</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Reference</th><th>Position</th><th>Status</th><th>Applied</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($applications, 0, 5) as $a): ?>
          <tr>
            <td><a href="app.php?r=applications.show&id=<?= (int) $a['id'] ?>"><?= View::e($a['reference']) ?></a></td>
            <td><?= View::e($a['job_title']) ?></td>
            <td><span class="badge"><?= View::e(JobApplication::STATUS_LABELS[$a['status']] ?? $a['status']) ?></span></td>
            <td><?= $a['submitted_at'] ? date('M j, Y', strtotime($a['submitted_at'])) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($savedJobs): ?>
<section class="section section-tight">
  <div class="wrap">
    <div class="section-head"><h2>Saved Jobs</h2></div>
    <div class="grid grid-3">
      <?php foreach ($savedJobs as $job): ?>
        <article class="card job-card">
          <div class="card-body">
            <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
            <div class="tag-row">
              <span class="badge"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
              <span class="pill"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
