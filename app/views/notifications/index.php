<?php /** @var array $notifications @var int $unread */
$catColor = ['security'=>'error','payment'=>'success','admission'=>'info','learning'=>'info','operations'=>'warning','general'=>'neutral'];
?>
<div class="topbar">
  <div>
    <h1>Notifications</h1>
    <p><?= $unread ?> unread of <?= count($notifications) ?> shown.</p>
  </div>
  <div class="actions">
    <a class="btn" href="app.php?r=notifications.preferences">Settings</a>
    <?php if ($unread > 0): ?>
    <form method="post" action="app.php?r=notifications.readall" style="display:inline">
      <?= Csrf::field() ?><button type="submit" class="btn primary">Mark all read</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!$notifications): ?>
  <div class="empty-card"><b>Nothing yet</b><p>Updates about your applications, payments, learning and messages appear here.</p></div>
<?php else: ?>
<div class="card">
  <div class="queue">
    <?php foreach ($notifications as $n): $isUnread = $n['read_at'] === null; ?>
    <div class="queue-item" style="<?= $isUnread ? 'background:var(--surface-muted)' : '' ?>">
      <div class="queue-ico" style="<?= $isUnread ? 'background:var(--grad);color:#fff' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
      </div>
      <div class="queue-t">
        <h4><?= View::e($n['title']) ?><?php if ((int) $n['digest_count'] > 1): ?> <span class="badge" style="position:static">&times;<?= (int) $n['digest_count'] ?></span><?php endif; ?></h4>
        <p><?= View::e($n['body'] ?: '') ?> · <?= View::e(date('d M Y H:i', strtotime($n['created_at']))) ?></p>
      </div>
      <span class="status-pill <?= $catColor[$n['category']] ?? 'neutral' ?>"><?= View::e($n['category']) ?></span>
      <a class="btn sm <?= $isUnread ? 'primary' : '' ?>" href="app.php?r=notifications.open&id=<?= $n['id'] ?>">Open</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
