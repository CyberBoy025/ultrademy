<?php /** @var array<int,array<string,mixed>> $notifications @var int $unread */ ?>
<section class="page-hero">
  <div class="wrap">
    <span class="eyebrow">Your Careers Account</span>
    <h1>Notifications</h1>
    <p><?= $unread ?> unread of <?= count($notifications) ?> shown.</p>
    <div class="hero-cta" style="margin-bottom:0">
      <a class="btn btn-secondary" href="app.php?r=notifications.preferences">Settings</a>
      <?php if ($unread > 0): ?>
        <form method="post" action="app.php?r=notifications.readall">
          <?= Csrf::field() ?>
          <button type="submit" class="btn btn-primary">Mark all read</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <?php if (!$notifications): ?>
      <div class="empty-card">
        <b>Nothing yet.</b>
        <p>Updates about your applications and interviews will appear here.</p>
      </div>
    <?php else: ?>
      <div class="card card-body">
        <?php foreach ($notifications as $n): $isUnread = $n['read_at'] === null; ?>
          <div class="list-row <?= $isUnread ? 'is-unread' : '' ?>">
            <div>
              <div class="title"><?= View::e($n['title']) ?><?php if ((int) $n['digest_count'] > 1): ?> <span class="badge">&times;<?= (int) $n['digest_count'] ?></span><?php endif; ?></div>
              <?php if ($n['body']): ?><div class="sub"><?= View::e($n['body']) ?></div><?php endif; ?>
              <div class="meta"><?= View::e(date('d M Y H:i', strtotime($n['created_at']))) ?></div>
            </div>
            <a class="btn btn-secondary btn-sm" href="app.php?r=notifications.open&id=<?= (int) $n['id'] ?>">Open</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
