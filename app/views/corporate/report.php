<?php /** @var array $contract @var array $rows */
$active = array_filter($rows, static fn(array $r): bool => $r['status'] === 'active');
$rated = array_filter($rows, static fn(array $r): bool => $r['attendance_rate'] !== null);
$avgAttendance = $rated ? round(array_sum(array_map(static fn(array $r): float => (float) $r['attendance_rate'], $rated)) / count($rated), 1) : null;
$scored = array_filter($rows, static fn(array $r): bool => $r['avg_assessment'] !== null);
$avgScore = $scored ? round(array_sum(array_map(static fn(array $r): float => (float) $r['avg_assessment'], $scored)) / count($scored), 1) : null;
$certs = array_sum(array_map(static fn(array $r): int => (int) $r['certificates'], $rows)); ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=corporate.contract&id=<?= (int) $contract['id'] ?>" style="color:var(--text-3)"><?= View::e($contract['reference']) ?></a> / Report</span>
    <h1><?= View::e($contract['org_name']) ?></h1>
    <p><?= View::e($contract['title']) ?> · <?= View::e($contract['programme_title'] ?? 'Bespoke') ?></p>
  </div>
  <a class="btn sm" href="app.php?r=corporate.report&id=<?= (int) $contract['id'] ?>&format=csv">Export CSV</a>
</div>

<div class="row row-b" style="margin-bottom:16px">
  <div class="card"><div class="chead"><h3>Participants</h3></div><span class="pct"><?= count($active) ?></span><span class="cap">of <?= (int) $contract['participants_cap'] ?> seats, active</span></div>
  <div class="card"><div class="chead"><h3>Certificates</h3></div><span class="pct"><?= $certs ?></span><span class="cap">issued</span></div>
</div>
<div class="row row-b" style="margin-bottom:20px">
  <div class="card">
    <div class="chead"><h3>Average attendance</h3></div>
    <span class="pct"><?= $avgAttendance === null ? '—' : $avgAttendance . '%' ?></span>
    <span class="cap"><?= $avgAttendance === null ? 'no sessions marked yet' : 'across ' . count($rated) . ' participant(s)' ?></span>
  </div>
  <div class="card">
    <div class="chead"><h3>Average assessment</h3></div>
    <span class="pct"><?= $avgScore === null ? '—' : $avgScore . '%' ?></span>
    <span class="cap"><?= $avgScore === null ? 'no graded attempts yet' : 'across ' . count($scored) . ' participant(s)' ?></span>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>By participant</h3></div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Name</th><th>Status</th><th>Sessions</th><th>Attendance</th><th>Assessment</th><th>Certificates</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['name']) ?></span><?php if ($r['job_title']): ?><span class="cap" style="display:block"><?= View::e($r['job_title']) ?></span><?php endif; ?></td>
          <td><span class="status-pill <?= $r['status'] === 'active' ? 'success' : ($r['status'] === 'withdrawn' ? 'error' : 'warning') ?>"><?= View::e(ucfirst($r['status'])) ?></span></td>
          <td class="cap"><?= (int) $r['sessions_marked'] ?></td>
          <td>
            <?php if ($r['attendance_rate'] === null): ?><span class="cap">n/a</span>
            <?php else: ?><span class="status-pill <?= (float) $r['attendance_rate'] >= 80 ? 'success' : ((float) $r['attendance_rate'] >= 60 ? 'warning' : 'error') ?>"><?= View::e((string) $r['attendance_rate']) ?>%</span><?php endif; ?>
          </td>
          <td class="cap"><?= $r['avg_assessment'] === null ? 'n/a' : View::e((string) $r['avg_assessment']) . '%' ?></td>
          <td class="cap"><?= (int) $r['certificates'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="cap" style="padding:16px;text-align:center">No participants yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="cap" style="margin-top:12px">
    <strong>“n/a” is not zero.</strong> A participant with no marked sessions has no attendance
    rate; showing 0% would report them as absent from classes that were never registered.
  </p>
</div>
