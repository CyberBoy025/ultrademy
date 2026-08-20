<?php
/** @var string $stepActive @var array<string,mixed> $app — required, shares caller scope. */
$applySteps = ['documents' => 'Documents', 'questions' => 'Questions', 'review' => 'Review & Submit'];
?>
<span class="eyebrow">Applying for</span>
<h1><?= View::e($app['job_title']) ?></h1>
<nav class="filters" aria-label="Application steps" style="margin-top:18px">
  <?php foreach ($applySteps as $key => $label): ?>
    <span class="chip <?= $stepActive === $key ? 'active' : '' ?>"><?= View::e($label) ?></span>
  <?php endforeach; ?>
</nav>
