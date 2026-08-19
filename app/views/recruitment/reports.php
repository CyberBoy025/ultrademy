<?php
/** @var string $from @var string $to @var bool $isGlobal @var array<string,int> $vacancySummary
 *  @var array<string,mixed> $applicationSummary @var array<int,array<string,mixed>> $byJob
 *  @var array<int,array<string,mixed>> $byCentre @var array<string,int> $interviewStats
 *  @var array<int,array<string,mixed>> $activity */
?>
<div class="topbar">
  <div>
    <h1>Recruitment Reports</h1>
    <p><?= View::e(date('d M Y', strtotime($from))) ?> – <?= View::e(date('d M Y', strtotime($to))) ?><?= $isGlobal ? ' · all centres' : ' · your centre(s)' ?></p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <form method="get" action="app.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="r" value="recruitment.reports">
    <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= View::e($from) ?>"></div>
    <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= View::e($to) ?>"></div>
    <button type="submit" class="btn primary">Apply</button>
  </form>
</div>

<h2 class="sec-title">Vacancies</h2>
<div class="kpi-grid" style="margin-bottom:24px">
  <div class="card kpi-card"><span class="lab">Total Postings</span><span class="val"><?= (int) $vacancySummary['total'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Active</span><span class="val" style="color:var(--success)"><?= (int) $vacancySummary['published'] ?></span><span class="cap">Published, accepting applications</span></div>
  <div class="card kpi-card"><span class="lab">Draft</span><span class="val"><?= (int) $vacancySummary['draft'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Closed</span><span class="val"><?= (int) $vacancySummary['closed'] + (int) $vacancySummary['unpublished'] ?></span></div>
</div>

<h2 class="sec-title">Applications — this period</h2>
<div class="kpi-grid" style="margin-bottom:24px">
  <div class="card kpi-card"><span class="lab">Received</span><span class="val"><?= (int) $applicationSummary['total'] ?></span></div>
  <div class="card kpi-card"><span class="lab">In Review</span><span class="val" style="color:var(--warning)"><?= (int) $applicationSummary['in_review'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Selected</span><span class="val" style="color:var(--success)"><?= (int) $applicationSummary['selected'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Not Successful</span><span class="val" style="color:var(--error)"><?= (int) $applicationSummary['rejected'] ?></span></div>
  <div class="card kpi-card"><span class="lab">Conversion</span><span class="val"><?= View::e((string) $applicationSummary['conversion_pct']) ?>%</span><span class="cap">Received → Selected</span></div>
</div>

<div class="row row-b" style="margin-bottom:24px">
  <div class="card">
    <div class="chead"><h3>Status Breakdown</h3></div>
    <?php $maxCount = max([1, ...array_values($applicationSummary['by_status'])]); ?>
    <?php foreach (JobApplication::STATUS_LABELS as $code => $label): ?>
      <?php if ($code === 'draft') { continue; } $c = (int) ($applicationSummary['by_status'][$code] ?? 0); if ($c === 0) { continue; } ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <span style="width:110px;font-size:12.5px;color:var(--text-2)"><?= View::e($label) ?></span>
        <div style="flex:1;background:var(--bg-2,rgba(127,127,127,.12));border-radius:4px;height:8px;overflow:hidden">
          <div style="width:<?= round($c / $maxCount * 100) ?>%;background:var(--brand,#4f7cff);height:100%"></div>
        </div>
        <span style="width:24px;text-align:right;font-size:12.5px;font-weight:600"><?= $c ?></span>
      </div>
    <?php endforeach; ?>
    <?php if ($applicationSummary['total'] === 0): ?><p class="cap" style="padding:6px 0">No applications in this period.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Interviews Scheduled</h3></div>
    <?php foreach (Interview::STATUSES as $code => $label): ?>
      <div class="queue-item">
        <div class="queue-t"><h4><?= $label ?></h4></div>
        <span class="status-pill <?= $code === 'completed' ? 'success' : ($code === 'cancelled' ? 'error' : 'info') ?>"><?= (int) ($interviewStats[$code] ?? 0) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<h2 class="sec-title">Applications by Job</h2>
<div class="card" style="margin-bottom:24px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Job Posting</th><th>Applications</th></tr></thead>
      <tbody>
        <?php foreach ($byJob as $j): ?>
        <tr onclick="location='app.php?r=recruitment.applications&job=<?= $j['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($j['title']) ?></span></td>
          <td><?= (int) $j['c'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$byJob): ?><tr><td colspan="2" class="cap" style="padding:16px;text-align:center">No applications in this period.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($isGlobal && $byCentre): ?>
<h2 class="sec-title">Applications by Centre</h2>
<div class="card" style="margin-bottom:24px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Centre</th><th>Applications</th></tr></thead>
      <tbody>
        <?php foreach ($byCentre as $c): ?>
        <tr><td><?= View::e($c['name']) ?></td><td><?= (int) $c['c'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="cap" style="margin-top:10px">Remote and multi-centre postings aren't tied to a single centre, so they don't appear here — see "By Job" above for the full picture.</p>
</div>
<?php endif; ?>

<h2 class="sec-title">Recent Activity</h2>
<div class="card">
  <?php foreach ($activity as $a): ?>
    <div class="queue-item">
      <div class="queue-t">
        <h4><?= View::e($a['reference']) ?> — <?= View::e($a['job_title']) ?></h4>
        <p><?= View::e(JobApplication::STATUS_LABELS[$a['from_status']] ?? ($a['from_status'] ?? 'New')) ?> &rarr; <?= View::e(JobApplication::STATUS_LABELS[$a['to_status']] ?? $a['to_status']) ?> · <?= View::e(date('d M Y H:i', strtotime($a['created_at']))) ?></p>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$activity): ?><p class="cap" style="padding:16px;text-align:center">No recent activity.</p><?php endif; ?>
</div>
