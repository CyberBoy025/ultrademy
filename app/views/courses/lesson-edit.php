<?php /** @var array $lesson @var array $materials */ ?>
<div class="topbar">
  <div>
    <span class="breadcrumb" style="display:block;margin-bottom:6px">
      <a href="app.php?r=courses" style="color:var(--text-3)">Courses</a> /
      <a href="app.php?r=courses.show&id=<?= $lesson['course_id'] ?>" style="color:var(--text-3)"><?= View::e($lesson['course_title']) ?></a> /
      <?= View::e($lesson['title']) ?>
    </span>
    <h1><?= View::e($lesson['title']) ?></h1>
    <p><?= View::e($lesson['module_title']) ?> · <?= View::e($lesson['content_type']) ?> · <?= (int) $lesson['duration_minutes'] ?> min</p>
  </div>
  <div class="actions">
    <a class="btn" href="app.php?r=learn.lesson&id=<?= $lesson['id'] ?>">Preview</a>
    <form method="post" action="app.php?r=lessons.delete" onsubmit="return confirm('Delete this lesson?')" style="display:inline">
      <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $lesson['id'] ?>">
      <button type="submit" class="btn">Delete</button>
    </form>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <form method="post" action="app.php?r=lessons.update">
    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $lesson['id'] ?>">
    <div class="field-row">
      <div class="field"><label>Title</label><input type="text" name="title" value="<?= View::e($lesson['title']) ?>" required></div>
      <div class="field"><label>Type</label>
        <select name="content_type">
          <?php foreach (['text', 'video', 'document', 'link'] as $t): ?>
          <option value="<?= $t ?>" <?= $lesson['content_type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Lesson content</label>
      <textarea name="body" rows="10" style="padding:11px 13px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--font-2);font-size:13px"><?= View::e($lesson['body']) ?></textarea>
      <span class="cap">Plain text. Line breaks are preserved when the learner reads it.</span>
    </div>
    <div class="field-row">
      <div class="field"><label>Duration (min)</label><input type="number" name="duration_minutes" min="0" value="<?= (int) $lesson['duration_minutes'] ?>"></div>
      <div class="field" style="justify-content:flex-end">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)">
          <input type="checkbox" name="is_preview" style="width:auto" <?= (int) $lesson['is_preview'] === 1 ? 'checked' : '' ?>> Free preview (visible without enrolment)
        </label>
      </div>
    </div>
    <button type="submit" class="btn primary">Save Lesson</button>
  </form>
</div>

<h2 class="sec-title">Materials</h2>
<div class="card" style="margin-bottom:14px">
  <div class="table-wrap">
    <table class="dt">
      <thead><tr><th>Title</th><th>Type</th><th>Size</th><th>Downloadable</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($materials as $m): ?>
        <tr>
          <td><span class="cell-main"><?= View::e($m['title']) ?></span><span class="cell-sub"><?= View::e($m['original_name'] ?? $m['url']) ?></span></td>
          <td><span class="status-pill neutral"><?= View::e($m['type']) ?></span></td>
          <td class="cap"><?= $m['size_bytes'] ? View::e(Upload::humanSize((int) $m['size_bytes'])) : '—' ?></td>
          <td><?= (int) $m['is_downloadable'] === 1 ? 'Yes' : 'View only' ?></td>
          <td>
            <form method="post" action="app.php?r=materials.delete" style="display:inline">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn sm">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$materials): ?><tr><td colspan="5" class="cap" style="padding:12px;text-align:center">No materials attached.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="chead"><h3>Add Material</h3></div>
  <form method="post" action="app.php?r=materials.store" enctype="multipart/form-data">
    <?= Csrf::field() ?><input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
    <div class="field"><label>Title</label><input type="text" name="title" placeholder="e.g. Week 1 slides" required></div>
    <div class="field-row">
      <div class="field">
        <label>Upload a file</label>
        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.mp4,.zip,.docx,.pptx,.xlsx">
        <span class="cap">PDF, image, MP4, ZIP or Office file · max 50 MB</span>
      </div>
      <div class="field">
        <label>…or link to one</label>
        <input type="url" name="url" placeholder="https://">
        <span class="cap">Provide a file or a link, not both.</span>
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2);margin-bottom:14px">
      <input type="checkbox" name="no_download" style="width:auto"> View only — learners may not download it
    </label>
    <button type="submit" class="btn primary">Add Material</button>
  </form>
</div>
