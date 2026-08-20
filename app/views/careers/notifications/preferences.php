<?php /** @var array<string,bool> $channels */ ?>
<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="app.php?r=notifications">Notifications</a> / Settings</div>
    <h1>Notification Settings</h1>
    <p>Choose what reaches you by email. Everything still appears in your in-app inbox here, so this page stays a complete record either way.</p>
  </div>
</section>

<section class="section">
  <div class="wrap narrow">
    <form method="post" action="app.php?r=notifications.preferences.save">
      <?= Csrf::field() ?>
      <div class="card card-body form-card">
        <h2>Recruitment Updates</h2>
        <p class="form-hint">Application status changes, interview scheduling, and outcomes.</p>
        <label class="check">
          <input type="checkbox" name="pref[in_app]" value="1" <?= $channels['in_app'] ? 'checked' : '' ?>>
          <span>Show in my notifications inbox</span>
        </label>
        <label class="check" style="margin-bottom:0">
          <input type="checkbox" name="pref[email]" value="1" <?= $channels['email'] ? 'checked' : '' ?>>
          <span>Send me an email</span>
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>
</section>
