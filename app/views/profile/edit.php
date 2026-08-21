<?php /** @var array $user */ ?>
<div class="topbar">
  <div>
    <h1>My Profile</h1>
    <p>Your name, phone number and photo — visible only to you and to staff who already
      see your account.</p>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="chead"><h3>Photo</h3></div>
  <div style="display:flex;align-items:center;gap:16px">
    <?php if (!empty($user['photo_path'])): ?>
      <img src="app.php?r=profile.photo" alt="" width="72" height="72" style="border-radius:var(--r-full);object-fit:cover;flex:none">
    <?php else: ?>
      <span class="pfp" style="width:72px;height:72px;font-size:24px" aria-hidden="true"><?= View::e(Auth::initials()) ?></span>
    <?php endif; ?>
    <div class="cap">JPG, PNG or WEBP, up to 3 MB. Choose a file below and save to update it.</div>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>Account details</h3></div>
  <form method="post" action="app.php?r=profile.update" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="field">
      <label for="first_name">First name</label>
      <input type="text" id="first_name" name="first_name" value="<?= View::e($user['first_name'] ?? '') ?>" required>
    </div>
    <div class="field">
      <label for="last_name">Last name</label>
      <input type="text" id="last_name" name="last_name" value="<?= View::e($user['last_name'] ?? '') ?>" required>
    </div>
    <div class="field">
      <label for="phone">Phone number</label>
      <input type="tel" id="phone" name="phone" value="<?= View::e($user['phone'] ?? '') ?>" placeholder="e.g. 08012345678">
    </div>
    <div class="field">
      <label>Email address</label>
      <input type="email" value="<?= View::e($user['email'] ?? '') ?>" disabled>
      <span class="cap">Contact an administrator to change the email address on your account.</span>
    </div>
    <div class="field">
      <label for="photo">Photo</label>
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
    </div>
    <button type="submit" class="btn primary">Save changes</button>
  </form>
</div>

<div class="card" style="margin-top:16px">
  <div class="chead"><h3>Notifications</h3></div>
  <p class="cap" style="margin-bottom:12px">Choose which categories of notification reach you, and by which channel.</p>
  <a class="btn sm" href="app.php?r=notifications.preferences">Notification Settings</a>
</div>
