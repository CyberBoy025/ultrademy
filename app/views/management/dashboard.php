<?php /** @var string $from @var string $to @var bool $scoped @var array $headline
 *  @var array $growth @var array $funnel @var array $centres @var ?array $services @var array $atRisk */
require __DIR__ . '/_charts.php'; ?>
<div class="mgmt">
<?php require __DIR__ . '/_style.php'; ?>

<div class="topbar">
  <div>
    <h1>Management Overview</h1>
    <p><?= View::e(date('d M Y', strtotime($from))) ?> – <?= View::e(date('d M Y', strtotime($to))) ?><?= $scoped ? ' · your centre(s) only' : ' · all centres' ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <?php if (!$scoped): ?><a class="btn sm" href="app.php?r=management.centres&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Centre comparison</a><?php endif; ?>
    <a class="btn sm" href="app.php?r=management.academic&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Academic</a>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <form method="get" action="app.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="r" value="management">
    <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= View::e($from) ?>"></div>
    <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= View::e($to) ?>"></div>
    <button type="submit" class="btn primary btn-sm">Apply</button>
    <?php foreach (['30 days' => 30, '90 days' => 90, '12 months' => 365] as $label => $days): ?>
      <a class="btn sm" href="app.php?r=management&from=<?= date('Y-m-d', strtotime("-$days days")) ?>&to=<?= date('Y-m-d') ?>">Last <?= $label ?></a>
    <?php endforeach; ?>
  </form>
</div>

<div class="kpis">
  <div class="kpi"><span class="k">Active students</span><span class="v"><?= (int) $headline['active_students'] ?></span><span class="s"><?= (int) $headline['completed_enrolments'] ?> completed to date</span></div>
  <div class="kpi"><span class="k">New enrolments</span><span class="v"><?= (int) $headline['new_enrolments'] ?></span><span class="s">in this period</span></div>
  <div class="kpi"><span class="k">Applications</span><span class="v"><?= (int) $headline['applications'] ?></span><span class="s"><?= (int) $headline['pending_applications'] ?> awaiting review</span></div>
  <div class="kpi"><span class="k">Revenue</span><span class="v"><?= View::e(Money::formatShort((int) $headline['revenue'])) ?></span><span class="s"><?= View::e(Money::formatShort((int) $headline['expenses'])) ?> expenses</span></div>
  <div class="kpi">
    <span class="k">Net</span>
    <span class="v" style="color:<?= $headline['net'] >= 0 ? 'var(--success)' : 'var(--error)' ?>"><?= View::e(Money::formatShort((int) $headline['net'])) ?></span>
    <span class="s"><?= View::e(Money::formatShort((int) $headline['outstanding'])) ?> outstanding</span>
  </div>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>Registrations and enrolments</h3><span class="cap">last 12 months</span></div>
    <?php
      $growthRows = array_map(static fn(array $g): array => [
          'label' => $g['label'], 'values' => [(int) $g['users'], (int) $g['enrolments']],
      ], $growth);
      echo chart_lines($growthRows, ['Registrations', 'Enrolments'], 'Monthly registrations and enrolments over the last 12 months');
      echo chart_legend(['Registrations', 'Enrolments']);
    ?>
    <details class="tableview">
      <summary>View as table</summary>
      <div class="table-wrap" style="margin-top:10px">
        <table class="dt">
          <thead><tr><th>Month</th><th>Registrations</th><th>Enrolments</th></tr></thead>
          <tbody>
            <?php foreach ($growth as $g): ?>
              <tr><td class="cap"><?= View::e($g['month']) ?></td><td><?= (int) $g['users'] ?></td><td><?= (int) $g['enrolments'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>
  </div>

  <div class="card">
    <div class="chead"><h3>Admissions funnel</h3></div>
    <?= chart_funnel([
        ['label' => 'Submitted', 'value' => (int) $funnel['submitted']],
        ['label' => 'Reviewed',  'value' => (int) $funnel['reviewed']],
        ['label' => 'Approved',  'value' => (int) $funnel['approved']],
        ['label' => 'Enrolled',  'value' => (int) $funnel['enrolled']],
    ]) ?>
    <p class="cap" style="margin-top:14px">
      <?php if ($funnel['conversion'] === null): ?>
        No applications submitted in this period.
      <?php else: ?>
        <strong><?= $funnel['conversion'] ?>%</strong> of submitted applications reached enrolment.
        <?= (int) $funnel['rejected'] ?> rejected.
      <?php endif; ?>
    </p>
  </div>
</div>

<?php if (!$scoped && $centres): ?>
<div class="card" style="margin-bottom:16px">
  <div class="chead">
    <h3>Revenue and expenses by centre</h3>
    <a class="btn sm" href="app.php?r=management.export&what=centres&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Export CSV</a>
  </div>
  <?php
    $barRows = array_map(static fn(array $c): array => [
        'label' => $c['name'], 'values' => [(int) $c['revenue'], (int) $c['expenses']],
    ], $centres);
    echo chart_grouped_bars($barRows, ['Revenue', 'Expenses'],
        static fn($v): string => Money::format((int) $v),
        'Revenue and expenses by centre for the selected period',
        static fn(float $v): string => chart_tick($v / 100));
    echo chart_legend(['Revenue', 'Expenses']);
  ?>
  <p class="cap" style="margin-top:12px">
    Online and head-office activity is its own column and is never folded into a physical centre.
  </p>
</div>
<?php endif; ?>

<?php if ($services !== null): ?>
<div class="kpis">
  <div class="kpi"><span class="k">Approved affiliates</span><span class="v"><?= (int) $services['affiliates'] ?></span><span class="s"><?= (int) $services['referrals'] ?> referrals this period</span></div>
  <div class="kpi"><span class="k">Commission earned</span><span class="v"><?= View::e(Money::formatShort((int) $services['commissions'])) ?></span><span class="s">lifetime, excluding voided</span></div>
  <div class="kpi"><span class="k">Donations</span><span class="v"><?= View::e(Money::formatShort((int) $services['donation_total'])) ?></span><span class="s"><?= (int) $services['donations'] ?> gifts this period</span></div>
  <div class="kpi"><span class="k">New accounts</span><span class="v"><?= (int) $headline['new_users'] ?></span><span class="s">registrations this period</span></div>
</div>
<?php endif; ?>

<?php if ($atRisk): ?>
<div class="card">
  <div class="chead">
    <h3>Students below 70% attendance</h3>
    <a class="btn sm" href="app.php?r=management.export&what=at-risk&from=<?= View::e($from) ?>&to=<?= View::e($to) ?>">Export CSV</a>
  </div>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Student</th><th>Programme</th><th>Sessions marked</th><th>Attendance</th></tr></thead>
      <tbody>
        <?php foreach (array_slice($atRisk, 0, 15) as $s): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($s['name'] ?: '—') ?></span><span class="cap" style="display:block"><?= View::e((string) $s['student_no']) ?></span></td>
          <td class="cap"><?= View::e($s['programme']) ?></td>
          <td class="cap"><?= (int) $s['marked'] ?></td>
          <td><span class="status-pill <?= (float) $s['rate'] < 50 ? 'error' : 'warning' ?>"><?= View::e((string) $s['rate']) ?>%</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($atRisk) > 15): ?><p class="cap" style="margin-top:10px">Showing 15 of <?= count($atRisk) ?> — export for the full list.</p><?php endif; ?>
  <p class="cap" style="margin-top:10px">
    Only students with at least three marked sessions appear, so a single missed class does not flag someone.
  </p>
</div>
<?php endif; ?>
</div>
