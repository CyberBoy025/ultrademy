<?php /** @var array $contracts @var string $status */ ?>
<div class="topbar">
  <div><h1>Contracts</h1><p><?= count($contracts) ?> contract(s).</p></div>
  <a class="btn sm" href="app.php?r=corporate">Pipeline</a>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="r" value="corporate.contracts">
    <div class="field" style="margin:0;min-width:180px">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['' => 'All', 'draft' => 'Draft', 'active' => 'Active', 'delivering' => 'Delivering', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Reference</th><th>Client</th><th>Programme</th><th>Centre</th><th>Seats</th><th>Value</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($contracts as $c): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($c['reference']) ?></span><span class="cap" style="display:block"><?= View::e($c['title']) ?></span></td>
          <td><?= View::e($c['org_name']) ?></td>
          <td class="cap"><?= View::e($c['programme_title'] ?? '—') ?></td>
          <td class="cap"><?= View::e($c['centre_name'] ?? 'Online') ?></td>
          <td class="cap"><?= (int) $c['active_participants'] ?> / <?= (int) $c['participants_cap'] ?></td>
          <td><?= View::e(Money::formatShort((int) $c['total_amount'], $c['currency'])) ?></td>
          <td><span class="status-pill <?= in_array($c['status'], ['active','delivering'], true) ? 'success' : ($c['status'] === 'cancelled' ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($c['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.contract&id=<?= (int) $c['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$contracts): ?><tr><td colspan="8" class="cap" style="padding:16px;text-align:center">No contracts.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
