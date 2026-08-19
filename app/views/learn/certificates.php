<?php /** @var array $certificates */
$verifyBase = rtrim((string) config('app.url'), '/') . '/verify.php?serial=';
?>
<div class="topbar">
  <div>
    <h1>My Certificates</h1>
    <p>Each certificate has a serial anyone can verify without needing an account.</p>
  </div>
</div>

<?php if (!$certificates): ?>
  <div class="empty-card">
    <b>No certificates yet</b>
    <p>Complete every lesson in a course and your certificate is issued automatically.</p>
  </div>
<?php else: ?>
<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
  <?php foreach ($certificates as $c): ?>
  <div class="card" style="<?= $c['revoked_at'] ? 'border-color:var(--error)' : '' ?>">
    <div class="chead">
      <h3><?= View::e($c['course_title'] ?? $c['programme_title'] ?? 'Certificate') ?></h3>
      <?php if ($c['revoked_at']): ?><span class="status-pill error">Revoked</span>
      <?php else: ?><span class="status-pill success">Valid</span><?php endif; ?>
    </div>
    <p class="cap" style="margin-bottom:10px"><?= View::e($c['title']) ?></p>
    <p class="cap" style="margin-bottom:4px">Issued <?= View::e(date('d M Y', strtotime($c['issued_at']))) ?></p>
    <p style="font-family:var(--font-1);font-size:13px;font-weight:600;margin-bottom:12px"><?= View::e($c['serial']) ?></p>
    <a class="btn sm" href="<?= View::e($verifyBase . urlencode($c['serial'])) ?>" target="_blank" rel="noopener noreferrer">Verify Publicly</a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
