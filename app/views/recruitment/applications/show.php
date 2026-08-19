<?php
/** @var array<string,mixed> $app @var array<string,mixed> $profile @var array<int,array<string,mixed>> $education
 *  @var array<int,array<string,mixed>> $experience @var array<int,array<string,mixed>> $skills
 *  @var array<int,array<string,mixed>> $references @var array<int,array<string,mixed>> $documents
 *  @var array<int,array<string,mixed>> $answers @var array<int,array<string,mixed>> $history
 *  @var array<int,array<string,mixed>> $notes @var array<int,array<string,mixed>> $interviews
 *  @var array<int,array<int,array<string,mixed>>> $feedback keyed by interview id — INTERNAL ONLY, never shown to the applicant
 *  @var array<int,array<string,mixed>> $panelistCandidates @var bool $canReview @var bool $canDecide @var bool $canManageInterviews */
$statusColor = [
    'submitted' => 'info', 'received' => 'info', 'under_review' => 'warning', 'shortlisted' => 'warning',
    'interview' => 'warning', 'assessment' => 'warning', 'final_review' => 'warning',
    'selected' => 'success', 'rejected' => 'error', 'withdrawn' => 'neutral', 'closed' => 'neutral',
];
$terminal = in_array($app['status'], ['selected', 'rejected', 'withdrawn', 'closed'], true);
$inReview = !$terminal;
$canDecideNow = $canDecide && !$terminal;
?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.applications" style="color:var(--text-3)">Applications</a> / <?= View::e($app['reference']) ?></span>
    <h1><?= View::e($app['job_title']) ?> <span class="status-pill <?= $statusColor[$app['status']] ?? 'neutral' ?>" style="margin-left:8px"><?= View::e(JobApplication::STATUS_LABELS[$app['status']] ?? $app['status']) ?></span></h1>
    <p><?= View::e($app['applicant_name'] ?: $app['email']) ?> · <?= View::e($app['email']) ?><?= $app['phone'] ? ' · ' . View::e($app['phone']) : '' ?> · applied <?= $app['submitted_at'] ? View::e(date('d M Y', strtotime($app['submitted_at']))) : '—' ?></p>
  </div>
</div>

<div class="row row-b">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Profile</h3></div>
      <p class="cap" style="margin-bottom:6px">Profile completion: <?= (int) $profile['completion_pct'] ?>%</p>
      <?php if ($profile['professional_summary']): ?><p style="font-size:13px;color:var(--text-2);margin-bottom:12px"><?= nl2br(View::e($profile['professional_summary'])) ?></p><?php endif; ?>

      <?php if ($education): ?>
        <p class="cap" style="margin-bottom:4px">Education</p>
        <?php foreach ($education as $e): ?><p style="font-size:13px;margin:0 0 4px"><?= View::e($e['qualification']) ?> — <?= View::e($e['institution']) ?></p><?php endforeach; ?>
      <?php endif; ?>
      <?php if ($experience): ?>
        <p class="cap" style="margin:10px 0 4px">Experience</p>
        <?php foreach ($experience as $e): ?><p style="font-size:13px;margin:0 0 4px"><?= View::e($e['job_title']) ?> — <?= View::e($e['organisation']) ?></p><?php endforeach; ?>
      <?php endif; ?>
      <?php if ($skills): ?>
        <p class="cap" style="margin:10px 0 4px">Skills</p>
        <p style="font-size:13px"><?= View::e(implode(', ', array_column($skills, 'skill_name'))) ?></p>
      <?php endif; ?>
      <?php if ($references): ?>
        <p class="cap" style="margin:10px 0 4px">References</p>
        <?php foreach ($references as $r): ?><p style="font-size:13px;margin:0 0 4px"><?= View::e($r['name']) ?><?= $r['relationship'] ? ' — ' . View::e($r['relationship']) : '' ?></p><?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Documents</h3></div>
      <?php if (!$documents): ?><p class="cap">No documents.</p><?php else: ?>
      <div class="table-wrap">
        <table class="dt">
          <thead><tr><th>Type</th><th>File</th><th>Size</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($documents as $d): ?>
            <tr>
              <td><?= View::e(JobApplicationDocument::TYPES[$d['type']] ?? $d['type']) ?></td>
              <td><?= View::e($d['original_name']) ?></td>
              <td class="cap"><?= View::e(Upload::humanSize((int) $d['size_bytes'])) ?></td>
              <td><a class="btn sm" href="app.php?r=recruitment.documents.download&id=<?= $d['id'] ?>">Download</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($answers): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Application Answers</h3></div>
      <?php foreach ($answers as $a): ?>
        <p class="cap" style="margin:10px 0 2px"><?= View::e($a['label']) ?></p>
        <p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($a['answer_text'] ?: '—')) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($app['cover_letter']): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Cover Letter</h3></div>
      <p style="font-size:13px;color:var(--text-2)"><?= nl2br(View::e($app['cover_letter'])) ?></p>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="chead"><h3>Status History</h3></div>
      <div class="table-wrap">
        <table class="dt">
          <thead><tr><th>Date</th><th>Change</th><th>Note</th></tr></thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td class="cap"><?= View::e(date('d M Y H:i', strtotime($h['created_at']))) ?></td>
              <td><?= View::e(JobApplication::STATUS_LABELS[$h['from_status']] ?? ($h['from_status'] ?? '—')) ?> &rarr; <?= View::e(JobApplication::STATUS_LABELS[$h['to_status']] ?? $h['to_status']) ?></td>
              <td class="cap"><?= View::e($h['note'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Actions</h3></div>
      <div class="stack">
        <?php if ($canReview && $inReview): ?>
          <form method="post" action="app.php?r=recruitment.applications.review">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
            <div class="field">
              <label>Move to</label>
              <select name="status">
                <?php foreach (JobApplication::REVIEW_STATUSES as $s): ?><option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= JobApplication::STATUS_LABELS[$s] ?></option><?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn" style="width:100%;justify-content:center">Update Status</button>
          </form>
        <?php endif; ?>

        <?php if ($canDecideNow): ?>
          <form method="post" action="app.php?r=recruitment.applications.decide">
            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $app['id'] ?>">
            <div class="field"><label>Decision note (shown to the applicant)</label><input type="text" name="decision_note"></div>
            <div style="display:flex;gap:8px">
              <button type="submit" name="decision" value="rejected" class="btn" style="flex:1;justify-content:center">Not Successful</button>
              <button type="submit" name="decision" value="selected" class="btn primary" style="flex:1;justify-content:center">Select</button>
            </div>
          </form>
        <?php elseif (!$canReview && !$canDecide): ?>
          <p class="cap">No actions available to you.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($canManageInterviews): ?>
    <div class="card" style="margin-bottom:16px">
      <div class="chead"><h3>Interviews</h3></div>
      <?php foreach ($interviews as $iv): ?>
        <div style="padding:8px 0;border-bottom:1px solid var(--line)">
          <span class="cell-main"><?= View::e(Interview::TYPES[$iv['type']] ?? $iv['type']) ?> — <?= View::e(Interview::STATUSES[$iv['status']] ?? $iv['status']) ?></span>
          <span class="cell-sub"><?= $iv['scheduled_at'] ? View::e(date('d M Y H:i', strtotime($iv['scheduled_at']))) : 'Not yet scheduled' ?></span>
          <?php foreach ($feedback[$iv['id']] ?? [] as $fb): ?>
            <div style="margin-top:8px;padding:8px 10px;background:var(--surface-2, rgba(127,127,127,.08));border-radius:6px">
              <p class="cell-main" style="margin-bottom:2px"><?= View::e($fb['panelist_name']) ?><?= $fb['score'] !== null ? ' — ' . (int) $fb['score'] . '/100' : '' ?><?= $fb['recommendation'] ? ' — ' . View::e(InterviewFeedback::RECOMMENDATIONS[$fb['recommendation']]) : '' ?></p>
              <?php if ($fb['evaluation']): ?><p class="cell-sub"><?= nl2br(View::e($fb['evaluation'])) ?></p><?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if ($iv['status'] === 'scheduled'): ?>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
              <form method="post" action="app.php?r=recruitment.interviews.reschedule" style="display:flex;gap:6px;align-items:center">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $iv['id'] ?>">
                <input type="datetime-local" name="scheduled_at" required style="padding:5px 8px;font-size:12.5px">
                <button type="submit" class="btn sm">Reschedule</button>
              </form>
              <form method="post" action="app.php?r=recruitment.interviews.status">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $iv['id'] ?>"><input type="hidden" name="status" value="completed">
                <button type="submit" class="btn sm">Mark Completed</button>
              </form>
              <form method="post" action="app.php?r=recruitment.interviews.status" onsubmit="return confirm('Cancel this interview? The applicant will be notified.')">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $iv['id'] ?>"><input type="hidden" name="status" value="cancelled">
                <button type="submit" class="btn sm">Cancel</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$interviews): ?><p class="cap" style="margin-bottom:10px">No interviews scheduled.</p><?php endif; ?>

      <form method="post" action="app.php?r=recruitment.interviews.store" style="margin-top:12px">
        <?= Csrf::field() ?><input type="hidden" name="job_application_id" value="<?= $app['id'] ?>">
        <div class="field"><label>Date &amp; time</label><input type="datetime-local" name="scheduled_at"></div>
        <div class="field">
          <label>Type</label>
          <select name="type"><?php foreach (Interview::TYPES as $c => $l): ?><option value="<?= $c ?>"><?= $l ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label>Location / meeting link</label><input type="text" name="location"><input type="text" name="meeting_link" placeholder="meeting link, if online" style="margin-top:6px"></div>
        <div class="field"><label>Instructions</label><input type="text" name="instructions"></div>
        <div class="field">
          <label>Panel</label>
          <div style="display:flex;flex-direction:column;gap:4px">
            <?php foreach ($panelistCandidates as $p): ?>
              <label style="display:flex;align-items:center;gap:6px;font-weight:400"><input type="checkbox" name="panelist_ids[]" value="<?= $p['id'] ?>" style="width:auto"><?= View::e($p['name']) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button type="submit" class="btn primary" style="width:100%;justify-content:center">Schedule Interview</button>
      </form>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="chead"><h3>Internal Notes</h3></div>
      <p class="cap" style="margin-bottom:10px">Never visible to the applicant.</p>
      <?php foreach ($notes as $n): ?>
        <div style="padding:8px 0;border-bottom:1px solid var(--line)">
          <p style="font-size:13px"><?= nl2br(View::e($n['note'])) ?></p>
          <span class="cell-sub"><?= View::e($n['author_name'] ?? 'System') ?> · <?= View::e(date('d M Y H:i', strtotime($n['created_at']))) ?></span>
        </div>
      <?php endforeach; ?>
      <form method="post" action="app.php?r=recruitment.notes.store" style="margin-top:10px">
        <?= Csrf::field() ?><input type="hidden" name="job_application_id" value="<?= $app['id'] ?>">
        <div class="field"><textarea name="note" rows="3" placeholder="Add a note…"></textarea></div>
        <button type="submit" class="btn" style="width:100%;justify-content:center">Add Note</button>
      </form>
    </div>
  </div>
</div>
