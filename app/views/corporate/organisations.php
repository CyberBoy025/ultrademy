<?php /** @var array $organisations @var string $status */ ?>
<div class="topbar">
  <div><h1>Organisations</h1><p><?= count($organisations) ?> corporate client(s).</p></div>
  <a class="btn sm" href="app.php?r=corporate">Pipeline</a>
</div>

<div class="card" style="margin-bottom:16px">
  <form method="get" action="app.php" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="r" value="corporate.organisations">
    <div class="field" style="margin:0;min-width:180px">
      <label>Status</label>
      <select name="status" onchange="this.form.submit()">
        <?php foreach (['' => 'All', 'prospect' => 'Prospect', 'active' => 'Active', 'dormant' => 'Dormant', 'blocked' => 'Blocked'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Organisation</th><th>Type</th><th>Open requests</th><th>Contracts</th><th>Value</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($organisations as $o): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($o['name']) ?></span><?php if ($o['industry']): ?><span class="cap" style="display:block"><?= View::e($o['industry']) ?></span><?php endif; ?></td>
          <td class="cap"><?= View::e(ucfirst($o['type'])) ?></td>
          <td class="cap"><?= (int) $o['open_requests'] ?></td>
          <td class="cap"><?= (int) $o['contract_count'] ?></td>
          <td><?= View::e(Money::formatShort((int) $o['contracted_value'])) ?></td>
          <td><span class="status-pill <?= $o['status'] === 'active' ? 'success' : ($o['status'] === 'blocked' ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($o['status'])) ?></span></td>
          <td><a class="btn sm" href="app.php?r=corporate.organisation&id=<?= (int) $o['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$organisations): ?><tr><td colspan="7" class="cap" style="padding:16px;text-align:center">No organisations yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>Add an organisation</h3></div>
  <form method="post" action="app.php?r=corporate.organisations.store">
    <?= Csrf::field() ?>
    <div class="row row-b">
      <div class="field"><label>Name</label><input type="text" name="name" required maxlength="200"></div>
      <div class="field">
        <label>Type</label>
        <select name="type">
          <?php foreach (['company'=>'Company','bank'=>'Bank','government'=>'Government agency','parastatal'=>'Parastatal','ngo'=>'NGO','institution'=>'Institution','other'=>'Other'] as $k=>$l): ?>
            <option value="<?= $k ?>"><?= View::e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Industry</label><input type="text" name="industry" maxlength="120"></div>
      <div class="field"><label>RC number</label><input type="text" name="registration_no" maxlength="60"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>City</label><input type="text" name="city" maxlength="80"></div>
      <div class="field"><label>State</label><input type="text" name="state" maxlength="80"></div>
    </div>
    <button type="submit" class="btn primary">Add organisation</button>
  </form>
</div>
