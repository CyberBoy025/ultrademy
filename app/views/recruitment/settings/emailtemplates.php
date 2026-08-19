<?php /** @var array<int,array<string,mixed>> $templates */ ?>
<div class="topbar">
  <div>
    <h1>Recruitment — Email Templates</h1>
    <p>Sent alongside the in-app notification for each event. Editing here changes the wording only — see the <a href="app.php?r=recruitment.emaillogs">email log</a> for what's actually gone out.</p>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Event</th><th>Subject</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($templates as $t): ?>
        <tr onclick="location='app.php?r=recruitment.emailtemplates.edit&code=<?= urlencode($t['code']) ?>'" style="cursor:pointer">
          <td><span class="cell-main"><?= View::e($t['name']) ?></span><span class="cell-sub"><?= View::e($t['code']) ?></span></td>
          <td class="cap"><?= View::e($t['subject']) ?></td>
          <td><span class="status-pill <?= $t['updated_at'] ? 'success' : 'neutral' ?>"><?= $t['updated_at'] ? 'Customised' : 'Default' ?></span></td>
          <td><a class="btn sm" href="app.php?r=recruitment.emailtemplates.edit&code=<?= urlencode($t['code']) ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
