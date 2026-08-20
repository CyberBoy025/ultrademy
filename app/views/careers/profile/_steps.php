<?php
/** @var string $stepActive @var array<string,mixed> $profile
 * Shared step nav + progress bar, required (not View::render'd) so it shares the caller's scope. */
$steps = [
    'personal' => ['label' => 'Personal', 'route' => 'profile.personal'],
    'education' => ['label' => 'Education', 'route' => 'profile.education'],
    'experience' => ['label' => 'Experience', 'route' => 'profile.experience'],
    'skills' => ['label' => 'Skills', 'route' => 'profile.skills'],
    'certifications' => ['label' => 'Certifications', 'route' => 'profile.certifications'],
    'resume' => ['label' => 'Documents', 'route' => 'profile.resume'],
    'references' => ['label' => 'References', 'route' => 'profile.references'],
    'review' => ['label' => 'Review', 'route' => 'profile.review'],
];
$pct = (int) ($profile['completion_pct'] ?? 0);
?>
<div class="progress-row">
  <span>Profile completion</span>
  <span><?= $pct ?>%</span>
</div>
<div class="progress"><i style="width:<?= $pct ?>%"></i></div>
<nav class="filters" aria-label="Profile sections">
  <?php foreach ($steps as $key => $s): ?>
    <a href="app.php?r=<?= $s['route'] ?>" class="chip <?= $stepActive === $key ? 'active' : '' ?>"><?= View::e($s['label']) ?></a>
  <?php endforeach; ?>
</nav>
