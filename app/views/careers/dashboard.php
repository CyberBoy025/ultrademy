<?php
/** @var array<int,array<string,mixed>> $savedJobs @var array<int,array<string,mixed>> $applications
 *  @var int $openCount @var int $completionPct */
?>
<div class="cw-wrap cw-dash-head">
  <h1>Welcome back, <?= View::e(Auth::name()) ?>.</h1>
  <p style="color:var(--cw-ink-soft);margin-top:8px">Here's where things stand.</p>
</div>

<div class="cw-wrap cw-dash-grid">
  <div class="cw-dash-card">
    <h3>Profile</h3>
    <div class="cw-dash-stat"><?= $completionPct ?>%</div>
    <p style="font-size:0.85rem;color:var(--cw-ink-faint);margin-top:4px">Complete — <a href="app.php?r=profile.personal">finish your profile</a></p>
  </div>
  <div class="cw-dash-card">
    <h3>Applications</h3>
    <div class="cw-dash-stat"><?= $openCount ?></div>
    <p style="font-size:0.85rem;color:var(--cw-ink-faint);margin-top:4px">Active — <a href="app.php?r=applications">view all</a></p>
  </div>
  <div class="cw-dash-card">
    <h3>Saved Jobs</h3>
    <div class="cw-dash-stat"><?= count($savedJobs) ?></div>
    <p style="font-size:0.85rem;color:var(--cw-ink-faint);margin-top:4px">Positions you've bookmarked to apply for later.</p>
  </div>
</div>

<?php if ($applications): ?>
<div class="cw-wrap" style="padding-bottom:40px">
  <h2 style="font-size:1.2rem;margin-bottom:16px">Recent Applications</h2>
  <div class="cw-tbl-wrap" style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;background:var(--cw-surface);border:1px solid var(--cw-line);border-radius:10px;overflow:hidden">
      <thead><tr style="text-align:left;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--cw-ink-faint)">
        <th style="padding:12px 16px">Reference</th><th style="padding:12px 16px">Position</th><th style="padding:12px 16px">Status</th><th style="padding:12px 16px">Applied</th>
      </tr></thead>
      <tbody>
      <?php foreach (array_slice($applications, 0, 5) as $a): ?>
        <tr style="border-top:1px solid var(--cw-line)">
          <td style="padding:12px 16px"><a href="app.php?r=applications.show&id=<?= (int) $a['id'] ?>"><?= View::e($a['reference']) ?></a></td>
          <td style="padding:12px 16px"><?= View::e($a['job_title']) ?></td>
          <td style="padding:12px 16px"><span class="cw-tag cw-tag-accent"><?= View::e(JobApplication::STATUS_LABELS[$a['status']] ?? $a['status']) ?></span></td>
          <td style="padding:12px 16px"><?= $a['submitted_at'] ? date('M j, Y', strtotime($a['submitted_at'])) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($savedJobs): ?>
<div class="cw-wrap" style="padding-bottom:60px">
  <h2 style="font-size:1.2rem;margin-bottom:16px">Saved Jobs</h2>
  <div class="cw-jobs-grid">
    <?php foreach ($savedJobs as $job): ?>
      <article class="cw-job-card">
        <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
        <div class="cw-tag-row">
          <span class="cw-tag cw-tag-accent"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
          <span class="cw-tag"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
