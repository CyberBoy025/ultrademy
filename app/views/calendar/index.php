<?php /** @var array $sessions */
$byDay = [];
foreach ($sessions as $s) {
    $byDay[date('Y-m-d', strtotime($s['starts_at']))][] = $s;
}
?>
<div class="topbar">
  <div>
    <h1>My Calendar</h1>
    <p>Your classes over the next 30 days — only the cohorts you're enrolled in or teaching.</p>
  </div>
</div>

<?php if (!$sessions): ?>
  <div class="empty-card">
    <b>Nothing scheduled</b>
    <p>You have no sessions in the next 30 days. Once you're enrolled in a cohort with a timetable, it appears here.</p>
  </div>
<?php else: ?>
  <?php foreach ($byDay as $day => $daySessions): ?>
  <h2 class="sec-title"><?= View::e(date('l, d M Y', strtotime($day))) ?><?= $day === date('Y-m-d') ? ' <span class="status-pill info" style="margin-left:8px">Today</span>' : '' ?></h2>
  <div class="card" style="margin-bottom:16px">
    <div class="tl">
      <?php foreach ($daySessions as $s): ?>
      <div class="tr">
        <div class="t"><?= View::e(date('H:i', strtotime($s['starts_at']))) ?></div>
        <div class="lane">
          <div class="evt <?= $s['mode'] === 'online' ? 'c' : '' ?>">
            <span class="tag"><?= View::e($s['mode'] === 'online' ? 'Online' : ($s['room_name'] ?? 'Physical')) ?></span>
            <h4><?= View::e($s['topic'] ?: $s['programme_title']) ?></h4>
            <p class="cap">
              <?= View::e($s['programme_title']) ?> · <?= View::e($s['group_name']) ?>
              · <?= View::e(date('H:i', strtotime($s['starts_at']))) ?>–<?= View::e(date('H:i', strtotime($s['ends_at']))) ?>
              <?php if ($s['instructor_name']): ?> · <?= View::e($s['instructor_name']) ?><?php endif; ?>
            </p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
