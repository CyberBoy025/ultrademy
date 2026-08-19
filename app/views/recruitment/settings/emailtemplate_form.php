<?php /** @var array<string,mixed> $template @var bool $isCustomised */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px"><a href="app.php?r=recruitment.emailtemplates" style="color:var(--text-3)">Email Templates</a> / <?= View::e($template['name']) ?></span>
    <h1><?= View::e($template['name']) ?> <span class="status-pill <?= $isCustomised ? 'success' : 'neutral' ?>" style="margin-left:8px"><?= $isCustomised ? 'Customised' : 'Default' ?></span></h1>
  </div>
</div>

<div class="row row-b">
  <div class="card">
    <div class="chead"><h3>Edit</h3></div>
    <form method="post" action="app.php?r=recruitment.emailtemplates.save" id="templateForm">
      <?= Csrf::field() ?>
      <input type="hidden" name="code" value="<?= View::e($template['code']) ?>">
      <div class="field"><label>Subject</label><input type="text" name="subject" id="subjectInput" value="<?= View::e($template['subject']) ?>" required></div>
      <div class="field"><label>Body</label><textarea name="body" id="bodyInput" rows="10" required><?= View::e($template['body']) ?></textarea></div>
      <p class="cap" style="margin-bottom:12px">Available tokens: <?php foreach (EmailTemplate::TOKENS as $t): ?><code style="margin-right:6px">{{<?= $t ?>}}</code><?php endforeach; ?></p>
      <button type="submit" class="btn primary">Save Template</button>
    </form>
  </div>

  <div class="card">
    <div class="chead"><h3>Preview</h3></div>
    <p class="cap" style="margin-bottom:12px">Substituted with sample data — nothing here is sent.</p>
    <div style="border:1px solid var(--line);border-radius:8px;padding:14px">
      <p style="font-weight:600;margin-bottom:10px" id="previewSubject"></p>
      <p style="white-space:pre-wrap;font-size:13px;color:var(--text-2)" id="previewBody"></p>
    </div>
  </div>
</div>

<script>
(function () {
  var sample = {
    applicant_name: 'Chika Nnamdi', job_title: 'Web Development Instructor',
    application_number: 'UTD-JA-2026-000042', application_status: 'Shortlisted',
    interview_date: 'Wednesday, August 26, 2026', interview_time: '10:00am',
    decision_note: 'Great teaching demo — welcome to the team!', company_name: 'UltrAdemy',
  };
  function render(text) {
    return text.replace(/\{\{(\w+)\}\}/g, function (m, key) { return sample[key] !== undefined ? sample[key] : m; });
  }
  var subjectInput = document.getElementById('subjectInput');
  var bodyInput = document.getElementById('bodyInput');
  var previewSubject = document.getElementById('previewSubject');
  var previewBody = document.getElementById('previewBody');
  function update() {
    previewSubject.textContent = render(subjectInput.value);
    previewBody.textContent = render(bodyInput.value);
  }
  subjectInput.addEventListener('input', update);
  bodyInput.addEventListener('input', update);
  update();
})();
</script>
