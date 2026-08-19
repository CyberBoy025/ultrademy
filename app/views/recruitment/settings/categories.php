<?php /** @var array<int,array<string,mixed>> $categories */ ?>
<div class="topbar"><div><h1>Recruitment — Job Categories</h1><p><?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?></p></div></div>

<div class="row row-b">
  <div class="card">
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Name</th></tr></thead>
        <tbody>
          <?php foreach ($categories as $c): ?><tr><td><?= View::e($c['name']) ?></td></tr><?php endforeach; ?>
          <?php if (!$categories): ?><tr><td class="cap" style="padding:16px;text-align:center">No categories yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="chead"><h3>Add Category</h3></div>
    <form method="post" action="app.php?r=recruitment.categories.store">
      <?= Csrf::field() ?>
      <div class="field"><label>Name</label><input type="text" name="name" required></div>
      <button type="submit" class="btn primary">Add</button>
    </form>
  </div>
</div>
