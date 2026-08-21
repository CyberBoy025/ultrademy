<?php /** @var string $from @var string $to @var array $programmes @var array $assessments
 *  @var array $instructors @var array $atRisk */
require __DIR__ . '/_charts.php'; ?>
<div class="mgmt">
<?php require __DIR__ . '/_style.php'; ?>

<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=management" style="color:var(--text-3)">Management</a> / Academic</span>
    <h1>Academic Performance</h1>
    <p><?= View::e(date('d M Y', strtotime($from))) ?> – <?= View::e(date('d M Y', strtotime($to))) ?></p>
  </div>
  <a class="btn sm" href="app.php?r=management.export&what=programmes&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Export CSV</a>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chead"><h3>Enrolments by programme</h3></div>
  <?php
    $top = array_slice($programmes, 0, 8);
    echo chart_grouped_bars(
        array_map(static fn(array $p): array => [
            'label' => $p['title'],
            'values' => [(int) $p['active'], (int) $p['completed']],
        ], $top),
        ['Active', 'Completed'],
        static fn($v): string => (string) (int) $v,
        'Active and completed enrolments by programme'
    );
    echo chart_legend(['Active', 'Completed']);
  ?>
  <?php if (count($programmes) > 8): ?>
    <p class="cap" style="margin-top:10px">Showing the 8 largest of <?= count($programmes) ?> programmes. The table below has all of them.</p>
  <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chead"><h3>Programmes</h3></div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Programme</th><th>Mode</th><th>Enrolments</th><th>Active</th><th>Completed</th><th>Withdrawn</th><th>Completion</th></tr></thead>
      <tbody>
        <?php foreach ($programmes as $p): $total = (int) $p['enrolments']; $rate = $total > 0 ? round(((int) $p['completed']) * 100 / $total) : null; ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['title']) ?></span></td>
          <td class="cap"><?= View::e(ucfirst((string) $p['delivery_mode'])) ?></td>
          <td><?= $total ?></td>
          <td class="cap"><?= (int) $p['active'] ?></td>
          <td class="cap"><?= (int) $p['completed'] ?></td>
          <td class="cap"><?= (int) $p['withdrawn'] ?></td>
          <td><?= $rate === null ? '<span class="cap">—</span>' : '<span class="cell-main">' . $rate . '%</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$programmes): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No programmes with enrolments.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chead"><h3>Assessment outcomes</h3></div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Course</th><th>Graded attempts</th><th>Average</th><th>Pass rate</th></tr></thead>
      <tbody>
        <?php foreach ($assessments as $a): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($a['title']) ?></span></td>
          <td class="cap"><?= (int) $a['attempts'] ?></td>
          <td><?= View::e((string) $a['avg_percent']) ?>%</td>
          <td><span class="status-pill <?= (float) $a['pass_rate'] >= 70 ? 'success' : ((float) $a['pass_rate'] >= 50 ? 'warning' : 'error') ?>"><?= View::e((string) $a['pass_rate']) ?>%</span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$assessments): ?><tr><td colspan="4" class="cap" style="padding:16px;text-align:center">No graded assessment attempts yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead">
    <h3>Instructor load</h3>
    <a class="btn sm" href="app.php?r=management.export&what=instructors&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Instructor</th><th>Class groups</th><th>Sessions in period</th><th>Students</th></tr></thead>
      <tbody>
        <?php foreach ($instructors as $i): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($i['name'] ?: '—') ?></span></td>
          <td class="cap"><?= (int) $i['class_groups'] ?></td>
          <td class="cap"><?= (int) $i['sessions'] ?></td>
          <td><?= (int) $i['students'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$instructors): ?><tr><td colspan="4" class="cap" style="padding:16px;text-align:center">No instructors assigned to class groups.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
