<?php
/** @var array $cohort @var array $groups @var array $enrolments @var array $instructors
 *  @var bool $canSchedule @var array $rooms */
$statusColor = ['planned' => 'neutral', 'open' => 'info', 'running' => 'success', 'completed' => 'neutral', 'cancelled' => 'error'];
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=cohorts" style="color:var(--text-3)">Cohorts</a> / <?= View::e($cohort['name']) ?></span>
    <h1><?= View::e($cohort['name']) ?> <span class="status-pill <?= $statusColor[$cohort['status']] ?>" style="margin-left:8px"><?= View::e(ucfirst($cohort['status'])) ?></span></h1>
    <p><?= View::e($cohort['programme_title']) ?> · <?= View::e($cohort['centre_name'] ?? 'Online') ?></p>
  </div>
  <div class="actions">
    <form method="post" action="app.php?r=cohorts.status" style="display:inline">
      <?= Csrf::field() ?>
      <input type="hidden" name="id" value="<?= $cohort['id'] ?>">
      <select name="status" onchange="this.form.submit()" class="btn">
        <?php foreach (['planned', 'open', 'running', 'completed', 'cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $cohort['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<h2 class="sec-title">Class Groups</h2>
<?php foreach ($groups as $g): ?>
<div class="card" style="margin-bottom:16px">
  <div class="chead">
    <h3><?= View::e($g['name']) ?></h3>
    <span class="cap"><?= View::e($g['instructor_name'] ?: 'No instructor assigned') ?><?= $g['capacity'] ? ' · Capacity ' . (int) $g['capacity'] : '' ?></span>
  </div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Topic</th><th>Room</th><th>When</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($g['sessions'] as $s): ?>
        <tr>
          <td><?= View::e($s['topic'] ?: '—') ?></td>
          <td><?= View::e($s['room_name'] ?? 'Online') ?></td>
          <td><?= View::e(date('D, d M · H:i', strtotime($s['starts_at']))) ?></td>
          <td><span class="status-pill info"><?= View::e(ucfirst($s['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$g['sessions']): ?><tr><td colspan="4" class="cap" style="padding:12px;text-align:center">No sessions scheduled yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($canSchedule): ?>
  <form method="post" action="app.php?r=sessions.store" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
    <?= Csrf::field() ?>
    <input type="hidden" name="class_group_id" value="<?= $g['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Topic</label><input type="text" name="topic" placeholder="e.g. JavaScript Basics"></div>
      <div class="field"><label>Room</label>
        <select name="room_id">
          <option value="">Online</option>
          <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= View::e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field-row">
      <div class="field"><label>Starts</label><input type="datetime-local" name="starts_at" required></div>
      <div class="field"><label>Ends</label><input type="datetime-local" name="ends_at" required></div>
    </div>
    <button type="submit" class="btn sm primary">Schedule Session</button>
  </form>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($canSchedule): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Class Group</h3></div>
  <form method="post" action="app.php?r=classgroups.store">
    <?= Csrf::field() ?>
    <input type="hidden" name="cohort_id" value="<?= $cohort['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Name</label><input type="text" name="name" placeholder="e.g. Group 2" required></div>
      <div class="field"><label>Instructor</label>
        <select name="instructor_user_id">
          <option value="">— Unassigned —</option>
          <?php foreach ($instructors as $i): ?><option value="<?= $i['id'] ?>"><?= View::e($i['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field"><label>Capacity</label><input type="number" name="capacity" min="0"></div>
    <button type="submit" class="btn primary">Add Class Group</button>
  </form>
</div>
<?php endif; ?>

<h2 class="sec-title">Enrolled Students (<?= count($enrolments) ?>)</h2>
<div class="card">
  <?php if ($enrolments): ?>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Student No.</th><th>Name</th><th>Email</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($enrolments as $e): ?>
        <tr>
          <td><?= View::e($e['student_no']) ?></td>
          <td><?= View::e($e['student_name']) ?></td>
          <td><?= View::e($e['email']) ?></td>
          <td><span class="status-pill success"><?= View::e(ucfirst($e['status'])) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-card"><b>No enrolled students yet</b><p>Direct enrolment isn't built in this phase — the full applicant → admission → enrolment workflow is Phase 7 (Applications & Students).</p></div>
  <?php endif; ?>
</div>
