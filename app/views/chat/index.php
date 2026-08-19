<?php
/** @var array $conversations @var bool $canDirect @var int|null $groupLimit @var int $groupCount
 *  @var bool $canGroups @var bool $canModerate @var array $contacts */
$atLimit = $canGroups && $groupLimit !== null && $groupCount >= $groupLimit;
?>
<div class="topbar">
  <div>
    <h1>Messages</h1>
    <p>Direct conversations and groups. You can message people you share a class, cohort or centre with.</p>
  </div>
</div>

<?php if (!$canDirect && !$canGroups): ?>
<div class="card" style="margin-bottom:20px;border-color:var(--warning)">
  <p class="cap">Messaging isn't included in your current package.
    <a href="app.php?r=subscription" style="color:var(--brand-cyan-text);font-weight:600">View packages</a>.</p>
</div>
<?php endif; ?>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Conversations</h3></div>
    <?php if (!$conversations): ?>
      <p class="cap" style="padding:8px 0">Nothing yet. Start one on the right.</p>
    <?php else: ?>
    <div class="queue">
      <?php foreach ($conversations as $c): ?>
      <div class="queue-item">
        <div class="queue-ico" style="background:var(--grad);color:#fff">
          <?= $c['type'] === 'direct' ? 'DM' : 'GR' ?>
        </div>
        <div class="queue-t">
          <h4><?= View::e(Conversation::titleFor($c, (int) Auth::id())) ?></h4>
          <p>
            <?= View::e(mb_substr((string) ($c['last_body'] ?? 'No messages yet'), 0, 60)) ?>
            <?php if ($c['last_at']): ?> · <?= View::e(date('d M H:i', strtotime($c['last_at']))) ?><?php endif; ?>
          </p>
        </div>
        <?php if ((int) $c['unread'] > 0): ?><span class="badge" style="position:static"><?= (int) $c['unread'] ?></span><?php endif; ?>
        <a class="btn sm" href="app.php?r=chat.show&id=<?= $c['id'] ?>">Open</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if ($canDirect): ?>
    <div class="chead"><h3>New Message</h3></div>
    <form method="post" action="app.php?r=chat.direct">
      <?= Csrf::field() ?>
      <div class="field">
        <label>To</label>
        <select name="user_id" required>
          <option value="">— Choose someone —</option>
          <?php foreach ($contacts as $p): ?>
          <option value="<?= $p['id'] ?>"><?= View::e($p['name']) ?> (<?= View::e($p['relation']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <?php if (!$contacts): ?><span class="cap">Nobody yet — you'll see classmates and instructors here once you're in a cohort.</span><?php endif; ?>
      </div>
      <button type="submit" class="btn primary">Start Conversation</button>
    </form>
    <?php endif; ?>

    <?php if ($canGroups): ?>
      <?php if ($canDirect): ?><div class="rule"></div><?php endif; ?>
      <div class="chead"><h3>New Group</h3></div>
      <p class="cap" style="margin-bottom:10px">
        You're in <strong><?= $groupCount ?></strong> group<?= $groupCount === 1 ? '' : 's' ?><?php
        if ($groupLimit !== null): ?> of <strong><?= $groupLimit ?></strong> allowed on your package<?php
        else: ?> — your package allows <strong>unlimited</strong> groups<?php endif; ?>.
      </p>
      <?php if ($atLimit): ?>
        <p class="cap" style="color:var(--warning)">You've reached your group limit. Leave a group, or upgrade.</p>
      <?php else: ?>
      <form method="post" action="app.php?r=chat.group">
        <?= Csrf::field() ?>
        <div class="field"><label>Group name</label><input type="text" name="title" required></div>
        <div class="field">
          <label>Members</label>
          <select name="member_ids[]" multiple style="height:120px">
            <?php foreach ($contacts as $p): ?>
            <option value="<?= $p['id'] ?>"><?= View::e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn primary">Create Group</button>
      </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
