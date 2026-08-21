<?php /** @var array $contract @var array $participants @var ?array $invoice
 *  @var bool $canManage @var bool $canApprove @var string $inviteBase */
$used = count(array_filter($participants, static fn(array $p): bool => $p['status'] !== 'withdrawn')); ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=corporate.organisation&id=<?= (int) $contract['org_id'] ?>" style="color:var(--text-3)"><?= View::e($contract['org_name']) ?></a> / <?= View::e($contract['reference']) ?></span>
    <h1><?= View::e($contract['title']) ?></h1>
    <p>
      <?= View::e($contract['programme_title'] ?? 'Bespoke') ?> ·
      <?= View::e($contract['centre_name'] ?? 'Online') ?> ·
      <?= $used ?> of <?= (int) $contract['participants_cap'] ?> seats used
    </p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="status-pill <?= in_array($contract['status'], ['active','delivering'], true) ? 'success' : ($contract['status'] === 'cancelled' ? 'error' : 'neutral') ?>"><?= View::e(ucfirst($contract['status'])) ?></span>
    <a class="btn sm" href="app.php?r=corporate.report&id=<?= (int) $contract['id'] ?>">Report</a>
  </div>
</div>

<div class="row row-a" style="margin-bottom:16px">
  <div class="card">
    <div class="chead"><h3>Contract</h3></div>
    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:14px">
      <span class="pct"><?= View::e(Money::format((int) $contract['total_amount'], $contract['currency'])) ?></span>
      <span class="cap">contracted</span>
    </div>
    <p class="cap">
      Cohort: <strong><?= View::e($contract['cohort_name'] ?? '—') ?></strong><br>
      <?php if ($contract['starts_on']): ?>Runs <?= View::e(date('d M Y', strtotime((string) $contract['starts_on']))) ?><?= $contract['ends_on'] ? ' – ' . View::e(date('d M Y', strtotime((string) $contract['ends_on']))) : '' ?><br><?php endif; ?>
      <?php if ($invoice): ?>
        Invoice <a href="app.php?r=invoices.show&id=<?= (int) $invoice['id'] ?>" style="color:var(--brand-cyan-text);font-weight:600"><?= View::e($invoice['number']) ?></a>
        — <span class="status-pill <?= $invoice['status'] === 'paid' ? 'success' : 'warning' ?>"><?= View::e(ucfirst($invoice['status'])) ?></span>
      <?php else: ?>
        <span style="color:var(--warning)">No invoice found for this contract.</span>
      <?php endif; ?>
    </p>
    <p class="cap" style="margin-top:12px">
      Participants are enrolled into the cohort above, so their attendance, assessments and
      certificates work exactly as they do for any other student.
    </p>
  </div>

  <?php if ($canApprove): ?>
  <div class="card">
    <div class="chead"><h3>Status</h3></div>
    <form method="post" action="app.php?r=corporate.contracts.status">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $contract['id'] ?>">
      <div class="field">
        <label>Contract status</label>
        <select name="status">
          <?php foreach (['draft'=>'Draft','active'=>'Active','delivering'=>'Delivering','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$l): ?>
            <option value="<?= $k ?>" <?= $contract['status'] === $k ? 'selected' : '' ?>><?= View::e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn primary btn-sm">Update</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<h2 class="sec-title">Participants</h2>
<div class="card" style="margin-bottom:16px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Student no</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($participants as $p): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($p['name']) ?></span><?php if ($p['job_title']): ?><span class="cap" style="display:block"><?= View::e($p['job_title']) ?></span><?php endif; ?></td>
          <td class="cap"><?= View::e($p['email']) ?></td>
          <td>
            <span class="status-pill <?= $p['status'] === 'active' ? 'success' : ($p['status'] === 'withdrawn' ? 'error' : 'warning') ?>"><?= View::e(ucfirst($p['status'])) ?></span>
          </td>
          <td class="cap"><?= View::e((string) ($p['student_no'] ?? '—')) ?></td>
          <td style="white-space:nowrap">
            <?php if ($canManage && $p['status'] !== 'withdrawn' && $p['status'] !== 'active'): ?>
              <form method="post" action="app.php?r=corporate.participants.invite" style="display:inline">
                <?= Csrf::field() ?><input type="hidden" name="participant_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                <button type="submit" class="btn sm primary"><?= $p['invite_token'] ? 'Reissue link' : 'Invite' ?></button>
              </form>
            <?php endif; ?>
            <?php if ($canManage && $p['status'] !== 'withdrawn'): ?>
              <form method="post" action="app.php?r=corporate.participants.withdraw" style="display:inline"
                    onsubmit="return confirm('Withdraw this participant? Their seat becomes free.')">
                <?= Csrf::field() ?><input type="hidden" name="participant_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                <button type="submit" class="btn sm">Withdraw</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php if ($p['invite_token'] && $p['status'] === 'invited'): ?>
        <tr>
          <td colspan="5" style="background:var(--surface-muted)">
            <p class="cap" style="margin-bottom:4px">Invitation link for <?= View::e($p['name']) ?> — send this to them:</p>
            <input type="text" readonly value="<?= View::e($inviteBase . $p['invite_token']) ?>" style="width:100%;font-size:12px">
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$participants): ?><tr><td colspan="5" class="cap" style="padding:16px;text-align:center">No participants nominated yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canManage): ?>
<div class="card">
  <div class="chead">
    <h3>Nominate a participant</h3>
    <span class="cap"><?= max(0, (int) $contract['participants_cap'] - $used) ?> seat(s) left</span>
  </div>
  <p class="cap" style="margin-bottom:14px">
    Nominating does not create an account. The person gets a link, and their account exists
    once they click it — an employer supplying an address is not that person's consent.
  </p>
  <form method="post" action="app.php?r=corporate.participants.store">
    <?= Csrf::field() ?><input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
    <div class="row row-b">
      <div class="field"><label>Name</label><input type="text" name="name" required maxlength="150"></div>
      <div class="field"><label>Work email</label><input type="email" name="email" required maxlength="255"></div>
    </div>
    <div class="row row-b">
      <div class="field"><label>Job title</label><input type="text" name="job_title" maxlength="120"></div>
      <div class="field"><label>Phone</label><input type="tel" name="phone" maxlength="32"></div>
    </div>
    <button type="submit" class="btn primary btn-sm">Nominate</button>
  </form>
</div>
<?php endif; ?>
