<?php /** @var string $from @var string $to @var array $rows */
require __DIR__ . '/_charts.php';
$totRevenue = 0; $totExpenses = 0; $totStudents = 0;
foreach ($rows as $r) { $totRevenue += (int) $r['revenue']; $totExpenses += (int) $r['expenses']; $totStudents += (int) $r['students']; } ?>
<div class="mgmt">
<?php require __DIR__ . '/_style.php'; ?>

<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=management" style="color:var(--text-3)">Management</a> / Centre comparison</span>
    <h1>Centre Comparison</h1>
    <p><?= View::e(date('d M Y', strtotime($from))) ?> – <?= View::e(date('d M Y', strtotime($to))) ?></p>
  </div>
  <a class="btn sm" href="app.php?r=management.export&what=centres&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Export CSV</a>
</div>

<div class="kpis">
  <div class="kpi"><span class="k">Active students</span><span class="v"><?= $totStudents ?></span><span class="s">across all centres</span></div>
  <div class="kpi"><span class="k">Revenue</span><span class="v"><?= View::e(Money::formatShort($totRevenue)) ?></span><span class="s">this period</span></div>
  <div class="kpi"><span class="k">Expenses</span><span class="v"><?= View::e(Money::formatShort($totExpenses)) ?></span><span class="s">approved only</span></div>
  <div class="kpi">
    <span class="k">Net</span>
    <span class="v" style="color:<?= $totRevenue - $totExpenses >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= View::e(Money::formatShort($totRevenue - $totExpenses)) ?></span>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chead"><h3>Revenue and expenses</h3></div>
  <?php
    echo chart_grouped_bars(
        array_map(static fn(array $c): array => ['label' => $c['name'], 'values' => [(int) $c['revenue'], (int) $c['expenses']]], $rows),
        ['Revenue', 'Expenses'],
        static fn($v): string => Money::format((int) $v),
        'Revenue and expenses by centre',
        // Ticks are in naira; the underlying values are kobo.
        static fn(float $v): string => chart_tick($v / 100)
    );
    echo chart_legend(['Revenue', 'Expenses']);
  ?>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chead"><h3>Active students</h3></div>
  <?php
    // One series, so no legend — the card title names it, and each bar is labelled.
    echo chart_grouped_bars(
        array_map(static fn(array $c): array => ['label' => $c['name'], 'values' => [(int) $c['students']]], $rows),
        ['Active students'],
        static fn($v): string => (string) (int) $v,
        'Active students by centre'
    );
  ?>
</div>

<div class="card">
  <div class="chead"><h3>All figures</h3></div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Centre</th><th>Active students</th><th>New enrolments</th><th>Rooms</th><th>Attendance</th><th>Revenue</th><th>Expenses</th><th>Net</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $net = (int) $r['net']; ?>
        <tr>
          <td><span class="cell-main"><?= View::e($r['name']) ?></span></td>
          <td><?= (int) $r['students'] ?></td>
          <td><?= (int) $r['enrolments'] ?></td>
          <td class="cap"><?= $r['rooms'] === null ? '—' : (int) $r['rooms'] ?></td>
          <td>
            <?php if ($r['attendance'] === null): ?>
              <span class="cap">n/a</span>
            <?php else: ?>
              <span class="status-pill <?= (float) $r['attendance'] >= 80 ? 'success' : ((float) $r['attendance'] >= 60 ? 'warning' : 'error') ?>"><?= View::e((string) $r['attendance']) ?>%</span>
            <?php endif; ?>
          </td>
          <td><?= View::e(Money::format((int) $r['revenue'])) ?></td>
          <td class="cap"><?= View::e(Money::format((int) $r['expenses'])) ?></td>
          <td><span class="cell-main" style="color:<?= $net >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= View::e(Money::format($net)) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="cap" style="margin-top:12px">
    <strong>“n/a” is not zero.</strong> An online cohort has no register to take, so it has no
    attendance rate — showing 0% would read as nobody turning up. Online and head-office
    activity is reported on its own line and never folded into a physical centre.
  </p>
</div>
</div>
