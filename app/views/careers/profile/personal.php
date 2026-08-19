<?php
/** @var array<string,mixed> $user @var array<string,mixed> $person @var array<string,mixed> $profile */
$stepActive = 'personal';
?>
<div class="cw-wizard-wrap">
  <?php require __DIR__ . '/_steps.php'; ?>
  <h1 style="font-size:1.5rem;margin-bottom:6px">Personal &amp; Professional Information</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:22px">This becomes part of your reusable applicant profile — you won't need to re-enter it for every job.</p>

  <form method="post" action="app.php?r=profile.personal.save">
    <?= Csrf::field() ?>
    <div class="cw-form-card">
      <h2>Personal Information</h2>
      <p class="cw-form-hint">Shared with your UltrAdemy account.</p>
      <div class="cw-form-grid">
        <label class="cw-field"><span>First name</span><input type="text" name="first_name" value="<?= View::e($person['first_name'] ?? '') ?>" required></label>
        <label class="cw-field"><span>Last name</span><input type="text" name="last_name" value="<?= View::e($person['last_name'] ?? '') ?>" required></label>
        <label class="cw-field"><span>Phone</span><input type="tel" name="phone" value="<?= View::e($user['phone'] ?? '') ?>"></label>
        <label class="cw-field"><span>Country</span><input type="text" name="country" value="<?= View::e($person['country'] ?? 'Nigeria') ?>"></label>
        <label class="cw-field cw-field-full"><span>Address</span><input type="text" name="address_line" value="<?= View::e($person['address_line'] ?? '') ?>"></label>
        <label class="cw-field"><span>City</span><input type="text" name="city" value="<?= View::e($person['city'] ?? '') ?>"></label>
        <label class="cw-field"><span>State</span><input type="text" name="state" value="<?= View::e($person['state'] ?? '') ?>"></label>
      </div>
    </div>

    <div class="cw-form-card">
      <h2>Professional Summary</h2>
      <p class="cw-form-hint">A short overview recruiters see first.</p>
      <label class="cw-field cw-field-full">
        <span>Summary</span>
        <textarea class="cw-textarea" name="professional_summary"><?= View::e($profile['professional_summary'] ?? '') ?></textarea>
      </label>
      <div class="cw-form-grid" style="margin-top:14px">
        <label class="cw-field"><span>Current occupation</span><input type="text" name="current_occupation" value="<?= View::e($profile['current_occupation'] ?? '') ?>"></label>
        <label class="cw-field"><span>Years of experience</span><input type="number" min="0" max="60" name="years_experience" value="<?= View::e((string) ($profile['years_experience'] ?? '')) ?>"></label>
        <label class="cw-field cw-field-full"><span>Career interests</span><input type="text" name="career_interests" placeholder="e.g. software development, instruction" value="<?= View::e($profile['career_interests'] ?? '') ?>"></label>
      </div>
    </div>

    <div class="cw-wizard-actions">
      <span></span>
      <button type="submit" class="cw-btn cw-btn-primary">Save &amp; Continue &rarr;</button>
    </div>
  </form>
</div>
