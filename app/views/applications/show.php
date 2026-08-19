<?php
/** @var array $app @var array $documents @var bool $isOwner @var bool $canReview
 *  @var bool $canApprove @var bool $canReject @var bool $canEnrol @var array $cohorts @var array|null $enrolment */
$statusColor = [
    'draft' => 'neutral', 'submitted' => 'info', 'under_review' => 'warning',
    'approved' => 'success', 'rejected' => 'error', 'withdrawn' => 'neutral',
];
$docColor = ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'error'];

// The same five steps the Phase 3 applicant mockup showed.
$steps = ['Submitted', 'Under Review', 'Decision', 'Payment', 'Enrolled'];
$reached = match ($app['status']) {
    'draft' => 0,
    'submitted' => 1,
    'under_review' => 2,
    'approved' => $enrolment ? (($enrolment['status'] ?? '') === 'active' ? 5 : 4) : 3,
    'rejected', 'withdrawn' => 3,
    default => 1,
};
$isClosed = in_array($app['status'], ['approved', 'rejected', 'withdrawn'], true);
$backRoute = $isOwner && !$canReview ? 'myapplications' : 'applications';
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="app.php?r=<?= $backRoute ?>" style="color:var(--text-3)"><?= $backRoute === 'myapplications' ? 'My Applications' : 'Applications' ?></a> / <?= View::e($app['reference']) ?>
    </span>
    <h1><?= View::e($app['programme_title']) ?>
      <span class="status-pill <?= $statusColor[$app['status']] ?>" style="margin-left:8px"><?= View::e(ucwords(str_replace('_', ' ', $app['status']))) ?></span>
    </h1>
    <p>
      <?= View::e($app['reference']) ?>
      · <?= View::e($app['centre_name'] ?? 'Online') ?>
      <?php if (!$isOwner): ?> · <?= View::e($app['applicant_name'] ?: $app['email']) ?><?php endif; ?>
      <?php if ($app['submitted_at']): ?> · submitted <?= View::e(date('d M Y', strtotime($app['submitted_at']))) ?><?php endif; ?>
    </p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="stepper">
    <?php foreach ($steps as $i => $label):
      $n = $i + 1;
      $cls = $n < $reached ? 'done' : ($n === $reached ? 'current' : '');
      if ($app['status'] === 'rejected' && $n === 3) { $cls = 'current'; }
    ?>
    <div class="step-item <?= $cls ?>">
      <span class="step-dot"><?= $n < $reached ? '&#10003;' : $n ?></span>
      <span class="step-lab"><?= $label ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($app['status'] === 'rejected'): ?>
    <p class="cap" style="margin-top:18px;text-align:center;color:var(--error)">This application was not successful.</p>
  <?php elseif ($app['status'] === 'withdrawn'): ?>
    <p class="cap" style="margin-top:18px;text-align:center">This application was withdrawn.</p>
  <?php elseif ($app['status'] === 'approved' && !$enrolment): ?>
    <p class="cap" style="margin-top:18px;text-align:center">Approved — awaiting admission into <?= View::e($app['cohort_name'] ?? 'a cohort') ?>.</p>
  <?php elseif ($enrolment): ?>
    <p class="cap" style="margin-top:18px;text-align:center">
      Admitted as <strong><?= View::e($enrolment['student_no']) ?></strong>
      — <?= View::e(ucwords(str_replace('_', ' ', $enrolment['status']))) ?>.
    </p>
  <?php else: ?>
    <p class="cap" style="margin-top:18px;text-align:center">Applications are typically reviewed within 5–7 business days.</p>
  <?php endif; ?>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Application</h3></div>
    <div class="prog-meta" style="flex-direction:column;gap:10px;align-items:flex-start;font-size:12.5px">
      <span><strong>Programme:</strong>&nbsp;<?= View::e($app['programme_title']) ?> (<?= View::e(ucfirst($app['delivery_mode'])) ?>)</span>
      <span><strong>Preferred centre:</strong>&nbsp;<?= View::e($app['centre_name'] ?? 'Online') ?></span>
      <span><strong>Cohort:</strong>&nbsp;<?= View::e($app['cohort_name'] ?? 'Not yet assigned') ?></span>
      <?php if (!$isOwner): ?>
        <span><strong>Applicant:</strong>&nbsp;<?= View::e($app['applicant_name'] ?: '—') ?></span>
        <span><strong>Email:</strong>&nbsp;<?= View::e($app['email']) ?></span>
        <?php if ($app['phone']): ?><span><strong>Phone:</strong>&nbsp;<?= View::e($app['phone']) ?></span><?php endif; ?>
      <?php endif; ?>
    </div>
    <?php if ($app['motivation']): ?>
      <p class="cap" style="margin-top:14px;margin-bottom:4px">Motivation</p>
      <p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($app['motivation'])) ?></p>
    <?php endif; ?>
    <?php if ($app['decision_note']): ?>
      <p class="cap" style="margin-top:14px;margin-bottom:4px">Decision note<?= $app['reviewer_name'] ? ' — ' . View::e($app['reviewer_name']) : '' ?></p>
      <p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($app['decision_note'])) ?></p>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="chead"><h3>Actions</h3></div>
    <div class="stack">
      <?php if ($canReview && $app['status'] === 'submitted'): ?>
        <form method="post" action="app.php?r=applications.review">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
          <button type="submit" class="btn" style="width:100%;justify-content:center">Mark Under Review</button>
        </form>
      <?php endif; ?>

      <?php if (($canApprove || $canReject) && in_array($app['status'], ['submitted', 'under_review'], true)): ?>
        <form method="post" action="app.php?r=applications.decide">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
          <?php if ($canApprove): ?>
          <div class="field">
            <label>Assign cohort (required to approve)</label>
            <select name="cohort_id">
              <option value="">— Choose a cohort —</option>
              <?php foreach ($cohorts as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (int) ($app['cohort_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                <?= View::e($c['name']) ?> — <?= View::e($c['centre_name'] ?? 'Online') ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="field">
            <label>Decision note</label>
            <input type="text" name="decision_note" placeholder="optional, shown to the applicant">
          </div>
          <div style="display:flex;gap:8px">
            <?php if ($canReject): ?>
              <button type="submit" name="decision" value="rejected" class="btn" style="flex:1;justify-content:center">Reject</button>
            <?php endif; ?>
            <?php if ($canApprove): ?>
              <button type="submit" name="decision" value="approved" class="btn primary" style="flex:1;justify-content:center">Approve</button>
            <?php endif; ?>
          </div>
        </form>
      <?php elseif ($canReview && !$canApprove && in_array($app['status'], ['submitted', 'under_review'], true)): ?>
        <p class="cap">You can review and recommend this application, but the admission decision sits with management or an administrator.</p>
      <?php endif; ?>

      <?php if ($canEnrol && $app['status'] === 'approved' && !$enrolment): ?>
        <form method="post" action="app.php?r=applications.enrol">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
          <button type="submit" class="btn primary" style="width:100%;justify-content:center">Admit &amp; Create Enrolment</button>
        </form>
      <?php endif; ?>

      <?php if ($enrolment && Auth::can('admissions.enrolment.create')): ?>
        <a class="btn" href="app.php?r=students.show&id=<?= $enrolment['id'] ?>" style="width:100%;justify-content:center">View Student Record</a>
      <?php endif; ?>

      <?php if ($isOwner && in_array($app['status'], ['draft', 'submitted', 'under_review'], true)): ?>
        <form method="post" action="app.php?r=applications.withdraw" onsubmit="return confirm('Withdraw this application?')">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
          <button type="submit" class="btn" style="width:100%;justify-content:center">Withdraw Application</button>
        </form>
      <?php endif; ?>

      <?php if (!$canReview && !$isOwner): ?><p class="cap">No actions available to you.</p><?php endif; ?>
    </div>
  </div>
</div>

<h2 class="sec-title">Documents</h2>
<div class="card" style="margin-bottom:16px">
  <?php if (!$documents): ?>
    <p class="cap" style="padding:6px 0">No documents uploaded yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Type</th><th>File</th><th>Size</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($documents as $d): ?>
        <tr>
          <td><span class="cell-main"><?= View::e(ucwords(str_replace('_', ' ', $d['type']))) ?></span></td>
          <td><?= View::e($d['original_name']) ?></td>
          <td class="cap"><?= View::e(ApplicationDocument::humanSize((int) $d['size_bytes'])) ?></td>
          <td><span class="status-pill <?= $docColor[$d['status']] ?>"><?= View::e(ucfirst($d['status'])) ?></span>
            <?php if ($d['reviewer_note']): ?><span class="cell-sub"><?= View::e($d['reviewer_note']) ?></span><?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;align-items:center">
              <a class="btn sm" href="app.php?r=documents.download&id=<?= $d['id'] ?>">Download</a>
              <?php if ($canReview): ?>
              <form method="post" action="app.php?r=documents.status" style="display:flex;gap:6px">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $d['id'] ?>">
                <select name="status" onchange="this.form.submit()" class="btn sm">
                  <?php foreach (['pending', 'accepted', 'rejected'] as $s): ?>
                  <option value="<?= $s ?>" <?= $d['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if (!$isClosed): ?>
<div class="card">
  <div class="chead"><h3>Upload a Document</h3></div>
  <form method="post" action="app.php?r=documents.upload" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
    <div class="field-row">
      <div class="field">
        <label>Type</label>
        <select name="type">
          <option value="id_card">Valid ID</option>
          <option value="passport_photo">Passport Photograph</option>
          <option value="certificate">Educational Certificate</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="field">
        <label>File</label>
        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
        <span class="cap">PDF, JPG or PNG · max 5 MB</span>
      </div>
    </div>
    <button type="submit" class="btn primary">Upload</button>
  </form>
</div>
<?php endif; ?>
