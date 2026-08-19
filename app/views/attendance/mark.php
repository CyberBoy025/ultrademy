<?php
/** @var array $session @var array $enrolments @var array $existing */
$options = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'];
// Decision 11: an instructor marks the register by name and student number — they do not
// need, and do not get, the student's contact details.
$showContact = Auth::maySeeContactDetails();
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=attendance" style="color:var(--text-3)">Attendance</a> / Mark</span>
    <h1><?= View::e($session['programme_title']) ?> — <?= View::e($session['group_name']) ?></h1>
    <p><?= View::e(date('D, d M Y · H:i', strtotime($session['starts_at']))) ?> · <?= View::e($session['room_name'] ?? 'Online') ?><?= $session['topic'] ? ' · ' . View::e($session['topic']) : '' ?></p>
  </div>
</div>

<div class="card">
  <?php if (!$enrolments): ?>
  <div class="empty-card"><b>No enrolled students</b><p>This cohort has no active enrolments to mark attendance for.</p></div>
  <?php else: ?>
  <form method="post" action="app.php?r=attendance.save">
    <?= Csrf::field() ?>
    <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Student</th><th>Student No.</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($enrolments as $e): $current = $existing[$e['id']] ?? 'present'; ?>
          <tr>
            <td>
              <span class="cell-main"><?= View::e($e['student_name']) ?></span>
              <?php if ($showContact): ?><span class="cell-sub"><?= View::e($e['email']) ?></span><?php endif; ?>
            </td>
            <td><?= View::e($e['student_no']) ?></td>
            <td>
              <select name="status[<?= $e['id'] ?>]">
                <?php foreach ($options as $val => $label): ?>
                <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn primary" style="margin-top:16px">Save Attendance</button>
  </form>
  <?php endif; ?>
</div>
