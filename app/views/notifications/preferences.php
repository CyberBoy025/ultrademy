<?php /** @var array $preferences @var array $locked */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=notifications" style="color:var(--text-3)">Notifications</a> / Settings</span>
    <h1>Notification Settings</h1>
    <p>Choose what reaches you by email. Everything still appears in your in-app inbox, so the platform stays a complete record.</p>
  </div>
</div>

<div class="card">
  <form method="post" action="app.php?r=notifications.preferences.save">
    <?= Csrf::field() ?>
    <div class="table-wrap">
      <table class="dt">
        <thead><tr><th>Category</th><th style="width:110px">In-app</th><th style="width:110px">Email</th></tr></thead>
        <tbody>
          <?php foreach ($preferences as $category => $channels):
            $isLocked = in_array($category, $locked, true); ?>
          <tr>
            <td>
              <span class="cell-main"><?= View::e(ucfirst($category)) ?></span>
              <?php if ($isLocked): ?><span class="cell-sub">Always on — these matter too much to miss</span><?php endif; ?>
            </td>
            <?php foreach (['in_app', 'email'] as $channel): ?>
            <td>
              <?php if ($isLocked): ?>
                <span class="status-pill success">Always</span>
              <?php else: ?>
                <input type="checkbox" name="pref[<?= View::e($category) ?>][<?= $channel ?>]" value="1" <?= $channels[$channel] ? 'checked' : '' ?>>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn primary" style="margin-top:16px">Save Settings</button>
  </form>
</div>
