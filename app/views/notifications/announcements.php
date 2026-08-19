<?php /** @var array $announcements @var array $centres @var array $cohorts */ ?>
<div class="topbar">
  <div>
    <h1>Announcements</h1>
    <p>One message, fanned out to an audience. Every recipient gets it in-app; email follows their preferences.</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>New Announcement</h3></div>
  <form method="post" action="app.php?r=announcements.publish">
    <?= Csrf::field() ?>
    <div class="field"><label>Title</label><input type="text" name="title" required></div>
    <div class="field">
      <label>Message</label>
      <textarea name="body" rows="4" required style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"></textarea>
    </div>
    <div class="field-row">
      <div class="field">
        <label>Audience</label>
        <select name="audience">
          <option value="all">Everyone with an active account</option>
          <option value="students">Students (active enrolment)</option>
          <option value="applicants">Applicants (open application)</option>
          <option value="staff">Staff</option>
          <option value="centre">One centre's students</option>
          <option value="cohort">One cohort</option>
        </select>
      </div>
      <div class="field">
        <label>Centre (if audience = centre)</label>
        <select name="centre_id">
          <option value="">—</option>
          <?php foreach ($centres as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Cohort (if audience = cohort)</label>
      <select name="cohort_id">
        <option value="">—</option>
        <?php foreach ($cohorts as $co): ?><option value="<?= $co['id'] ?>"><?= View::e($co['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn primary">Publish</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Audience</th><th>Recipients</th><th>Published by</th><th>When</th></tr></thead>
      <tbody>
        <?php foreach ($announcements as $a): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($a['title']) ?></span><span class="cell-sub"><?= View::e(mb_substr($a['body'], 0, 70)) ?></span></td>
          <td>
            <?= View::e(ucfirst($a['audience'])) ?>
            <?php if ($a['centre_name']): ?><span class="cell-sub"><?= View::e($a['centre_name']) ?></span><?php endif; ?>
            <?php if ($a['cohort_name']): ?><span class="cell-sub"><?= View::e($a['cohort_name']) ?></span><?php endif; ?>
          </td>
          <td><?= (int) $a['recipient_count'] ?></td>
          <td class="cap"><?= View::e($a['publisher_name'] ?: '—') ?></td>
          <td class="cap"><?= View::e(date('d M Y H:i', strtotime($a['published_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$announcements): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No announcements yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
