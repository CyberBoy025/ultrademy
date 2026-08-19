<?php
/** @var array $stats @var array $myClassGroups @var array $myEnrolments */
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<div class="topbar">
  <div>
    <h1><?= $greeting ?>, <?= View::e(Auth::name()) ?>.</h1>
    <p><?= View::e(implode(' · ', Auth::roles()) ?: 'No role assigned yet') ?></p>
  </div>
</div>

<?php if (array_filter($stats, fn($v) => $v !== null)): ?>
<div class="kpi-grid">
  <?php if ($stats['users'] !== null): ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Total Users</span><div class="kpi-ico c"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4.4 3.6-8 7-8s7 3.6 7 8"/></svg></div></div>
    <span class="val"><?= (int) $stats['users'] ?></span>
  </div>
  <?php endif; ?>
  <?php if ($stats['centres'] !== null): ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Centres</span><div class="kpi-ico m"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg></div></div>
    <span class="val"><?= (int) $stats['centres'] ?></span>
  </div>
  <?php endif; ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Published Programmes</span><div class="kpi-ico c"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"/></svg></div></div>
    <span class="val"><?= (int) $stats['programmes'] ?></span>
  </div>
  <?php if ($stats['enrolled'] !== null): ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Active Enrolments</span><div class="kpi-ico m"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></div></div>
    <span class="val"><?= (int) $stats['enrolled'] ?></span>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($myClassGroups): ?>
<h2 class="sec-title">My Classes</h2>
<div class="courses">
  <?php foreach ($myClassGroups as $g): ?>
  <article class="course">
    <div class="tile c" aria-hidden="true">📚</div>
    <div><p class="cap"><?= View::e($g['cohort_name']) ?></p><h4><?= View::e($g['name']) ?></h4></div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($myEnrolments): ?>
<h2 class="sec-title">My Programmes</h2>
<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Programme</th><th>Cohort</th><th>Status</th><th>Enrolled</th></tr></thead>
      <tbody>
        <?php foreach ($myEnrolments as $e): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($e['programme_title']) ?></span></td>
          <td><?= View::e($e['cohort_name']) ?></td>
          <td><span class="status-pill <?= $e['status'] === 'active' ? 'success' : 'neutral' ?>"><?= View::e(ucfirst($e['status'])) ?></span></td>
          <td><?= View::e(date('d M Y', strtotime($e['enrolled_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<h2 class="sec-title">Quick Links</h2>
<div class="qa-grid">
  <?php foreach (Nav::items() as $item): if ($item['key'] === 'dashboard') continue; ?>
  <a class="qa" href="<?= View::e($item['href']) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><?= $item['icon'] ?></svg>
    <span><?= View::e($item['label']) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!Auth::roles()): ?>
<div class="empty-card" style="margin-top:20px">
  <p><b>No role assigned yet.</b> Roles are granted automatically once you apply to a programme, enrol, or join the affiliate programme — those workflows are part of a later build phase. An administrator can also assign one directly.</p>
</div>
<?php endif; ?>
