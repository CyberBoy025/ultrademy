<?php
/** @var array $conversation @var string $title @var array $messages @var array $participants
 *  @var bool $isParticipant @var bool $canModerate @var bool $isMuted @var int $myId */
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=chat" style="color:var(--text-3)">Messages</a> / <?= View::e($title) ?></span>
    <h1><?= View::e($title) ?></h1>
    <p>
      <?= View::e(ucwords(str_replace('_', ' ', $conversation['type']))) ?>
      · <?= count($participants) ?> participant(s)
      <?php if ((int) $conversation['is_moderated'] === 1): ?> · moderated<?php endif; ?>
    </p>
  </div>
  <div class="actions">
    <?php if ($isParticipant): ?>
    <form method="post" action="app.php?r=chat.mute" style="display:inline">
      <?= Csrf::field() ?>
      <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">
      <input type="hidden" name="muted" value="<?= $isMuted ? '0' : '1' ?>">
      <button type="submit" class="btn"><?= $isMuted ? 'Unmute' : 'Mute' ?></button>
    </form>
    <?php if ($conversation['type'] !== 'direct'): ?>
    <form method="post" action="app.php?r=chat.leave" style="display:inline" onsubmit="return confirm('Leave this group?')">
      <?= Csrf::field() ?>
      <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">
      <button type="submit" class="btn">Leave</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (!$isParticipant && $canModerate): ?>
<div class="card" style="margin-bottom:16px;border-color:var(--warning)">
  <p class="cap">You are viewing this thread as a <strong>moderator</strong>. You are not a participant and cannot post.</p>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <?php if (!$messages): ?>
    <p class="cap" style="padding:8px 0">No messages yet.</p>
  <?php else: ?>
  <div class="stack" style="gap:12px">
    <?php foreach ($messages as $m):
      $mine = (int) $m['sender_id'] === $myId;
      $removed = $m['deleted_at'] !== null; ?>
    <div style="display:flex;gap:10px;<?= $mine ? 'flex-direction:row-reverse;text-align:right' : '' ?>">
      <div class="queue-ico" style="flex:none;<?= $mine ? 'background:var(--grad);color:#fff' : '' ?>">
        <?= View::e(strtoupper(mb_substr((string) ($m['sender_name'] ?: $m['sender_email'] ?: 'S'), 0, 2))) ?>
      </div>
      <div style="flex:1;min-width:0">
        <p class="cap" style="margin-bottom:3px">
          <?= View::e($m['sender_name'] ?: ($m['sender_email'] ?: 'System')) ?>
          · <?= View::e(date('d M H:i', strtotime($m['created_at']))) ?>
        </p>
        <?php if ($removed): ?>
          <div class="card" style="background:var(--surface-muted);padding:10px 12px;display:inline-block">
            <p class="cap" style="font-style:italic">
              Message removed<?= $m['deleted_by_name'] ? ' by ' . View::e($m['deleted_by_name']) : '' ?>.
              <?php if ($canModerate): ?><br>Reason: <?= View::e($m['deleted_reason'] ?: '—') ?><?php endif; ?>
            </p>
            <?php if ($canModerate && trim((string) $m['body']) !== ''): ?>
              <p class="cap" style="margin-top:6px;opacity:.8">Original: <?= View::e($m['body']) ?></p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="card" style="padding:10px 12px;display:inline-block;max-width:100%;<?= $mine ? 'border-color:var(--cyan-300)' : '' ?>">
            <?php if (trim((string) $m['body']) !== ''): ?>
              <div style="font-size:13.5px;line-height:1.6;white-space:pre-wrap"><?= View::e($m['body']) ?></div>
            <?php endif; ?>
            <?php if ($m['attachment_stored_name']): ?>
              <a class="btn sm" style="margin-top:8px" href="app.php?r=chat.attachment&id=<?= $m['id'] ?>">
                <?= View::e($m['attachment_original_name']) ?> (<?= View::e(Upload::humanSize((int) $m['attachment_size'])) ?>)
              </a>
            <?php endif; ?>
          </div>
          <?php if ($canModerate): ?>
          <form method="post" action="app.php?r=chat.moderate" style="margin-top:6px;display:flex;gap:6px;<?= $mine ? 'justify-content:flex-end' : '' ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <input type="text" name="reason" placeholder="reason" style="padding:4px 8px;font-size:11px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text)">
            <button type="submit" class="btn sm">Remove</button>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php if ($isParticipant): ?>
<div class="card">
  <form method="post" action="app.php?r=chat.post" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?>">
    <div class="field">
      <label>Message</label>
      <textarea name="body" rows="3" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <input type="file" name="attachment">
      <button type="submit" class="btn primary">Send</button>
    </div>
  </form>
</div>
<?php endif; ?>

<h2 class="sec-title">Participants</h2>
<div class="card">
  <div class="queue">
    <?php foreach ($participants as $p): ?>
    <div class="queue-item">
      <div class="queue-ico" style="background:var(--grad);color:#fff"><?= View::e(strtoupper(mb_substr((string) ($p['name'] ?: $p['email']), 0, 2))) ?></div>
      <div class="queue-t"><h4><?= View::e($p['name'] ?: $p['email']) ?></h4><p><?= View::e(ucfirst($p['role'])) ?></p></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
