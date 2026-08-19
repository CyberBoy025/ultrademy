<?php
/** @var string $stepActive @var array<string,mixed> $app — required, shares caller scope. */
$applySteps = ['documents' => 'Documents', 'questions' => 'Questions', 'review' => 'Review & Submit'];
?>
<p style="font-size:0.8rem;color:var(--cw-ink-faint);margin-bottom:6px">Applying for</p>
<h1 style="font-size:1.5rem;margin-bottom:20px"><?= View::e($app['job_title']) ?></h1>
<nav class="cw-wizard-steps" aria-label="Application steps">
  <?php foreach ($applySteps as $key => $label): ?>
    <span class="<?= $stepActive === $key ? 'is-active' : '' ?>" style="font-size:0.8rem;font-weight:600;padding:7px 13px;border-radius:20px;background:<?= $stepActive === $key ? 'var(--cw-accent)' : 'var(--cw-surface-2)' ?>;color:<?= $stepActive === $key ? '#fff' : 'var(--cw-ink-faint)' ?>"><?= View::e($label) ?></span>
  <?php endforeach; ?>
</nav>
