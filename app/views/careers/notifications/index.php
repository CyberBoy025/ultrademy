<?php /** @var array<int,array<string,mixed>> $notifications @var int $unread */ ?>
<div class="cw-narrow" style="padding:40px 24px 60px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:8px">
    <div>
      <h1 style="font-size:1.6rem;margin-bottom:6px">Notifications</h1>
      <p style="color:var(--cw-ink-soft)"><?= $unread ?> unread of <?= count($notifications) ?> shown.</p>
    </div>
    <div style="display:flex;gap:10px">
      <a class="cw-btn cw-btn-outline" href="app.php?r=notifications.preferences">Settings</a>
      <?php if ($unread > 0): ?>
        <form method="post" action="app.php?r=notifications.readall">
          <?= Csrf::field() ?>
          <button type="submit" class="cw-btn cw-btn-primary">Mark all read</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$notifications): ?>
    <div class="cw-empty" style="margin-top:20px">
      <p>Nothing yet.</p>
      <p style="margin-top:6px;color:var(--cw-ink-faint);font-size:0.9rem">Updates about your applications and interviews will appear here.</p>
    </div>
  <?php else: ?>
    <div class="cw-form-card" style="margin-top:20px">
      <?php foreach ($notifications as $n): $isUnread = $n['read_at'] === null; ?>
        <div class="cw-notif-item <?= $isUnread ? 'is-unread' : '' ?>">
          <div>
            <div class="cw-notif-title"><?= View::e($n['title']) ?><?php if ((int) $n['digest_count'] > 1): ?> <span class="cw-tag cw-tag-accent">&times;<?= (int) $n['digest_count'] ?></span><?php endif; ?></div>
            <?php if ($n['body']): ?><div class="cw-notif-body"><?= View::e($n['body']) ?></div><?php endif; ?>
            <div class="cw-notif-meta"><?= View::e(date('d M Y H:i', strtotime($n['created_at']))) ?></div>
          </div>
          <a class="cw-btn cw-btn-outline" style="white-space:nowrap" href="app.php?r=notifications.open&id=<?= (int) $n['id'] ?>">Open</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
