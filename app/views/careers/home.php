<?php /** @var array<int,array<string,mixed>> $featured */ ?>

<section class="cw-hero">
  <div class="cw-narrow">
    <span class="cw-hero-eyebrow">UltrAdemy Careers</span>
    <h1>Build your career with UltrAdemy.</h1>
    <p class="lede">Explore opportunities to join our growing team across technology, education, administration, training and centre operations — at Gwagwalada Hub, Kubwa Hub, or remote.</p>
    <div class="cw-hero-actions">
      <a class="cw-btn cw-btn-primary" href="app.php?r=jobs">View Open Positions</a>
      <a class="cw-btn cw-btn-outline" href="register.php">Create Applicant Account</a>
    </div>
  </div>
</section>

<section class="cw-section cw-section-alt">
  <div class="cw-wrap">
    <div class="cw-section-head">
      <span class="cw-eyebrow">Why UltrAdemy</span>
      <h2>A place to do work that matters</h2>
    </div>
    <div class="cw-why-grid">
      <div class="cw-why-card">
        <h3>Real training, real outcomes</h3>
        <p>You'll work alongside people who care about whether a student actually finds a job — not just whether a course was delivered.</p>
      </div>
      <div class="cw-why-card">
        <h3>Two hubs, room to grow</h3>
        <p>Gwagwalada and Kubwa are both active, growing centres — plenty of room to take on more as they do.</p>
      </div>
      <div class="cw-why-card">
        <h3>On-site, hybrid or remote</h3>
        <p>Roles span the classroom, the centre floor, and fully remote instruction — pick the mode that fits how you work best.</p>
      </div>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="cw-section">
  <div class="cw-wrap">
    <div class="cw-section-head">
      <span class="cw-eyebrow">Current Openings</span>
      <h2>A few roles we're hiring for now</h2>
    </div>
    <div class="cw-jobs-grid">
      <?php foreach ($featured as $job): ?>
        <article class="cw-job-card">
          <div class="cw-job-card-dept"><?= View::e($job['department_name'] ?? 'UltrAdemy') ?></div>
          <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
          <div class="cw-tag-row">
            <span class="cw-tag cw-tag-accent"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
            <span class="cw-tag"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
          </div>
          <div class="cw-job-card-foot"><span><?= View::e($job['_location']) ?></span></div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:26px"><a class="cw-btn cw-btn-outline" href="app.php?r=jobs">See all open positions &rarr;</a></p>
  </div>
</section>
<?php endif; ?>

<section class="cw-section cw-section-alt">
  <div class="cw-wrap">
    <div class="cw-section-head">
      <span class="cw-eyebrow">How It Works</span>
      <h2>The application process</h2>
    </div>
    <div class="cw-process">
      <div class="cw-process-step"><h4>Create an account</h4><p>One profile, reusable across every application you make.</p></div>
      <div class="cw-process-step"><h4>Complete your profile</h4><p>Education, experience, skills — filled in once, step by step.</p></div>
      <div class="cw-process-step"><h4>Apply</h4><p>Attach documents, answer role-specific questions, review, and submit.</p></div>
      <div class="cw-process-step"><h4>Track your status</h4><p>Follow your application from received through to a decision.</p></div>
    </div>
  </div>
</section>

<section class="cw-section">
  <div class="cw-wrap">
    <div class="cw-section-head">
      <span class="cw-eyebrow">FAQ</span>
      <h2>Frequently asked questions</h2>
    </div>
    <div class="cw-faq">
      <details>
        <summary>Do I need an account to browse jobs?</summary>
        <p>No — anyone can browse open positions without signing in. You'll need an account only when you're ready to apply.</p>
      </details>
      <details>
        <summary>Can I apply to more than one role at once?</summary>
        <p>Yes. Your profile is reusable, so applying to a second role doesn't mean starting over.</p>
      </details>
      <details>
        <summary>I already have an UltrAdemy student account — do I need a new one?</summary>
        <p>You can sign in to Careers with the same email and password. Your applicant activity stays separate from your learning account.</p>
      </details>
      <details>
        <summary>How do I contact the recruitment team?</summary>
        <p>Reach us at <a href="mailto:careers@ultrademy.com">careers@ultrademy.com</a> with any questions about a role or your application.</p>
      </details>
    </div>
  </div>
</section>

<section class="cw-section">
  <div class="cw-wrap">
    <div class="cw-cta-band">
      <h2>Ready to take the next step?</h2>
      <p>Browse everything we're hiring for right now.</p>
      <a class="cw-btn cw-btn-primary" href="app.php?r=jobs">View Open Positions</a>
    </div>
  </div>
</section>
