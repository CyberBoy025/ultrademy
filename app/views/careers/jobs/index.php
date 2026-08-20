<?php
/** @var array<int,array<string,mixed>> $jobs @var array<string,mixed> $filters
 *  @var array<int,array<string,mixed>> $departments @var array<int,array<string,mixed>> $categories
 *  @var array<int,array<string,mixed>> $centres @var array<int,int> $savedIds */
$hasFilters = $filters['department_id'] || $filters['category_id'] || $filters['employment_type']
    || $filters['work_mode'] || $filters['centre_id'] || $filters['keyword'];
?>
<section class="page-hero">
  <div class="wrap">
    <div class="breadcrumb"><a href="app.php">Careers</a> / Open Positions</div>
    <span class="eyebrow">Current Openings</span>
    <h1>Open Positions</h1>
    <p>Browse current vacancies across every UltrAdemy department and location.</p>
    <form class="search-form" method="get" action="app.php">
      <input type="hidden" name="r" value="jobs">
      <label class="field">
        <span class="sr-only">Search open positions</span>
        <input type="text" name="q" placeholder="Search by title or keyword…" value="<?= View::e($filters['keyword']) ?>">
      </label>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </div>
</section>

<section class="section">
  <div class="wrap listing-layout">
    <aside class="card card-body sticky-side">
      <form method="get" action="app.php" id="filterForm">
        <input type="hidden" name="r" value="jobs">
        <input type="hidden" name="q" value="<?= View::e($filters['keyword']) ?>">

        <div class="field">
          <label for="f-dept">Department</label>
          <select id="f-dept" name="department_id" onchange="document.getElementById('filterForm').submit()">
            <option value="">All departments</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int) $d['id'] ?>" <?= (string) $filters['department_id'] === (string) $d['id'] ? 'selected' : '' ?>><?= View::e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="f-cat">Category</label>
          <select id="f-cat" name="category_id" onchange="document.getElementById('filterForm').submit()">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="f-emp">Employment Type</label>
          <select id="f-emp" name="employment_type" onchange="document.getElementById('filterForm').submit()">
            <option value="">All types</option>
            <?php foreach (JobPosting::EMPLOYMENT_TYPES as $code => $label): ?>
              <option value="<?= View::e($code) ?>" <?= $filters['employment_type'] === $code ? 'selected' : '' ?>><?= View::e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="f-mode">Work Mode</label>
          <select id="f-mode" name="work_mode" onchange="document.getElementById('filterForm').submit()">
            <option value="">Any mode</option>
            <?php foreach (JobPosting::WORK_MODES as $code => $label): ?>
              <option value="<?= View::e($code) ?>" <?= $filters['work_mode'] === $code ? 'selected' : '' ?>><?= View::e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="f-centre">Location</label>
          <select id="f-centre" name="centre_id" onchange="document.getElementById('filterForm').submit()">
            <option value="">Any location</option>
            <?php foreach ($centres as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= (string) $filters['centre_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($hasFilters): ?>
          <p class="empty-hint"><a href="app.php?r=jobs">Clear all filters</a></p>
        <?php endif; ?>
      </form>
    </aside>

    <div>
      <p class="results-count"><?= count($jobs) ?> open position<?= count($jobs) === 1 ? '' : 's' ?></p>

      <?php if (!$jobs): ?>
        <div class="empty-card">
          <b>No open positions match those filters right now.</b>
          <p><a href="app.php?r=jobs">Clear filters and see everything open</a></p>
        </div>
      <?php else: ?>
        <div class="grid grid-2">
          <?php foreach ($jobs as $job): ?>
            <article class="card job-card">
              <div class="card-body">
                <div class="dept"><?= View::e($job['department_name'] ?? 'UltrAdemy') ?></div>
                <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
                <div class="tag-row">
                  <span class="badge"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
                  <span class="pill"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
                  <?php if (in_array((int) $job['id'], $savedIds, true)): ?><span class="badge badge-magenta">Saved</span><?php endif; ?>
                </div>
                <div class="prog-meta">
                  <span><?= View::e($job['_location']) ?></span>
                  <?php if ($job['application_deadline']): ?><span>Closes <?= date('M j', strtotime($job['application_deadline'])) ?></span><?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
