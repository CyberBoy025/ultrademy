<?php
/** @var array $programmes @var bool $canManage @var array $categories @var array $centres */
$statusColor = ['draft' => 'neutral', 'pending_approval' => 'warning', 'approved' => 'info', 'published' => 'success', 'archived' => 'neutral'];
?>
<div class="topbar">
  <div>
    <h1>Programmes</h1>
    <p><?= $canManage ? 'Full catalogue, including drafts.' : 'Published programmes.' ?></p>
  </div>
</div>

<?php if ($canManage): ?>
<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Programme</h3></div>
  <form method="post" action="app.php?r=programmes.store">
    <?= Csrf::field() ?>
    <div class="field-row">
      <div class="field"><label>Title</label><input type="text" name="title" required></div>
      <div class="field"><label>Category</label>
        <select name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description"></div>
    <div class="field-row">
      <div class="field"><label>Duration (weeks)</label><input type="number" name="duration_weeks" min="1"></div>
      <div class="field"><label>Fee (₦)</label><input type="number" name="fee_naira" min="0" step="500"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>Delivery mode</label>
        <select name="delivery_mode">
          <option value="physical">Physical</option><option value="online">Online</option><option value="hybrid">Hybrid</option>
        </select>
      </div>
      <div class="field"><label>Available at</label>
        <select name="centre_ids[]" multiple style="height:76px">
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <button type="submit" class="btn primary">Create Draft</button>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Category</th><th>Mode</th><th>Duration</th><th>Fee</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($programmes as $p): ?>
        <tr onclick="location='app.php?r=programmes.show&id=<?= $p['id'] ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($p['title']) ?></span><span class="cell-sub"><?= View::e($p['code']) ?></span></td>
          <td><?= View::e($p['category_name'] ?? '—') ?></td>
          <td><?= View::e(ucfirst($p['delivery_mode'])) ?></td>
          <td><?= $p['duration_weeks'] ? $p['duration_weeks'] . ' wks' : '—' ?></td>
          <td>₦<?= number_format(((int) $p['fee_amount']) / 100) ?></td>
          <td><span class="status-pill <?= $statusColor[$p['status']] ?>"><?= View::e(ucwords(str_replace('_', ' ', $p['status']))) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$programmes): ?><tr><td colspan="6" class="cap" style="padding:20px;text-align:center">No programmes yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
