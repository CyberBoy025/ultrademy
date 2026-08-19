<?php /** @var array<int,array<string,mixed>> $departments */ ?>
<div class="topbar"><div><h1>Recruitment — Departments</h1><p><?= count($departments) ?> department<?= count($departments) === 1 ? '' : 's' ?></p></div></div>

<div class="row row-b">
  <div class="card">
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Name</th></tr></thead>
        <tbody>
          <?php foreach ($departments as $d): ?><tr><td><?= View::e($d['name']) ?></td></tr><?php endforeach; ?>
          <?php if (!$departments): ?><tr><td class="cap" style="padding:16px;text-align:center">No departments yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="chead"><h3>Add Department</h3></div>
    <form method="post" action="app.php?r=recruitment.departments.store">
      <?= Csrf::field() ?>
      <div class="field"><label>Name</label><input type="text" name="name" required></div>
      <button type="submit" class="btn primary">Add</button>
    </form>
  </div>
</div>
