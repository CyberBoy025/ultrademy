<?php
/** @var array<string,mixed> $user @var array<string,mixed> $person @var array<string,mixed> $profile */
$stepActive = 'personal';
?>
<div class="wizard">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1>Personal &amp; Professional Information</h1>
  <p class="muted">This becomes part of your reusable applicant profile — you won't need to re-enter it for every job.</p>

  <form method="post" action="app.php?r=profile.personal.save">
    <?= Csrf::field() ?>
    <div class="card card-body form-card">
      <h2>Personal Information</h2>
      <p class="form-hint">Shared with your UltrAdemy account.</p>
      <div class="form-grid">
        <label class="field"><span>First name</span><input type="text" name="first_name" value="<?= View::e($person['first_name'] ?? '') ?>" required></label>
        <label class="field"><span>Last name</span><input type="text" name="last_name" value="<?= View::e($person['last_name'] ?? '') ?>" required></label>
        <label class="field"><span>Phone</span><input type="tel" name="phone" value="<?= View::e($user['phone'] ?? '') ?>"></label>
        <label class="field"><span>Country</span><input type="text" name="country" value="<?= View::e($person['country'] ?? 'Nigeria') ?>"></label>
        <label class="field field-full"><span>Address</span><input type="text" name="address_line" value="<?= View::e($person['address_line'] ?? '') ?>"></label>
        <label class="field"><span>City</span><input type="text" name="city" value="<?= View::e($person['city'] ?? '') ?>"></label>
        <label class="field"><span>State</span><input type="text" name="state" value="<?= View::e($person['state'] ?? '') ?>"></label>
      </div>
    </div>

    <div class="card card-body form-card">
      <h2>Professional Summary</h2>
      <p class="form-hint">A short overview recruiters see first.</p>
      <label class="field field-full">
        <span>Summary</span>
        <textarea name="professional_summary"><?= View::e($profile['professional_summary'] ?? '') ?></textarea>
      </label>
      <div class="form-grid" style="margin-top:14px">
        <label class="field"><span>Current occupation</span><input type="text" name="current_occupation" value="<?= View::e($profile['current_occupation'] ?? '') ?>"></label>
        <label class="field"><span>Years of experience</span><input type="number" min="0" max="60" name="years_experience" value="<?= View::e((string) ($profile['years_experience'] ?? '')) ?>"></label>
        <label class="field field-full"><span>Career interests</span><input type="text" name="career_interests" placeholder="e.g. software development, instruction" value="<?= View::e($profile['career_interests'] ?? '') ?>"></label>
      </div>
    </div>

    <div class="wizard-actions">
      <span></span>
      <button type="submit" class="btn btn-primary">Save &amp; Continue &rarr;</button>
    </div>
  </form>
</div>
