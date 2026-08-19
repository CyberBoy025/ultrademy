<?php /** @var array<string,bool> $channels */ ?>
<div class="cw-narrow" style="padding:40px 24px 60px">
  <p style="font-size:0.8rem;color:var(--cw-ink-faint);margin-bottom:6px"><a href="app.php?r=notifications">Notifications</a> / Settings</p>
  <h1 style="font-size:1.6rem;margin-bottom:8px">Notification Settings</h1>
  <p style="color:var(--cw-ink-soft);margin-bottom:24px">Choose what reaches you by email. Everything still appears in your in-app inbox here, so this page stays a complete record either way.</p>

  <form method="post" action="app.php?r=notifications.preferences.save">
    <?= Csrf::field() ?>
    <div class="cw-form-card">
      <h2>Recruitment Updates</h2>
      <p class="cw-form-hint">Application status changes, interview scheduling, and outcomes.</p>
      <label class="cw-check" style="margin-bottom:10px">
        <input type="checkbox" name="pref[in_app]" value="1" <?= $channels['in_app'] ? 'checked' : '' ?>>
        <span>Show in my notifications inbox</span>
      </label>
      <label class="cw-check">
        <input type="checkbox" name="pref[email]" value="1" <?= $channels['email'] ? 'checked' : '' ?>>
        <span>Send me an email</span>
      </label>
    </div>
    <button type="submit" class="cw-btn cw-btn-primary">Save Settings</button>
  </form>
</div>
