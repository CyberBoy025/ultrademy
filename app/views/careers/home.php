<?php /** @var array<int,array<string,mixed>> $featured */ ?>

<section class="hero hero-solo">
  <div class="wrap">
    <div class="hero-copy">
      <span class="eyebrow">UltrAdemy Careers</span>
      <h1>Build your career with <em>UltrAdemy.</em></h1>
      <p>Explore opportunities to join our growing team across technology, education, administration, training and centre operations — at Gwagwalada Hub, Kubwa Hub, or remote.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="app.php?r=jobs">View Open Positions</a>
        <a class="btn btn-secondary" href="register.php">Create Applicant Account</a>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Why UltrAdemy</span>
      <h2>A place to do work that matters</h2>
    </div>
    <div class="grid grid-3">
      <div class="card card-body mode-card">
        <h3>Real training, real outcomes</h3>
        <p>You'll work alongside people who care about whether a student actually finds a job — not just whether a course was delivered.</p>
      </div>
      <div class="card card-body mode-card">
        <h3>Two hubs, room to grow</h3>
        <p>Gwagwalada and Kubwa are both active, growing centres — plenty of room to take on more as they do.</p>
      </div>
      <div class="card card-body mode-card">
        <h3>On-site, hybrid or remote</h3>
        <p>Roles span the classroom, the centre floor, and fully remote instruction — pick the mode that fits how you work best.</p>
      </div>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Current Openings</span>
      <h2>A few roles we're hiring for now</h2>
    </div>
    <div class="grid grid-4">
      <?php foreach ($featured as $job): ?>
        <article class="card job-card">
          <div class="card-body">
            <div class="dept"><?= View::e($job['department_name'] ?? 'UltrAdemy') ?></div>
            <h3><a href="app.php?r=jobs.show&slug=<?= urlencode($job['slug']) ?>"><?= View::e($job['title']) ?></a></h3>
            <div class="tag-row">
              <span class="badge"><?= View::e(JobPosting::EMPLOYMENT_TYPES[$job['employment_type']] ?? $job['employment_type']) ?></span>
              <span class="pill"><?= View::e(JobPosting::WORK_MODES[$job['work_mode']] ?? $job['work_mode']) ?></span>
            </div>
            <div class="prog-meta"><span><?= View::e($job['_location']) ?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:28px"><a class="btn btn-secondary" href="app.php?r=jobs">See all open positions &rarr;</a></p>
  </div>
</section>
<?php endif; ?>

<section class="section" style="background:var(--color-surface-muted)">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">How It Works</span>
      <h2>The application process</h2>
    </div>
    <?php /* .grid.grid-4, not .steps — site.css:245 hard-codes .steps to three columns
             and would leave the fourth step orphaned on its own row. */ ?>
    <div class="grid grid-4">
      <div class="step"><span class="num">01</span><div><h4>Create an account</h4><p>One profile, reusable across every application you make.</p></div></div>
      <div class="step"><span class="num">02</span><div><h4>Complete your profile</h4><p>Education, experience, skills — filled in once, step by step.</p></div></div>
      <div class="step"><span class="num">03</span><div><h4>Apply</h4><p>Attach documents, answer role-specific questions, review, and submit.</p></div></div>
      <div class="step"><span class="num">04</span><div><h4>Track your status</h4><p>Follow your application from received through to a decision.</p></div></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">FAQ</span>
      <h2>Frequently asked questions</h2>
    </div>
    <?php /* .faq-item/.faq-q buttons rather than <details>, so public/js/site.js drives
             the accordion exactly as it does on ultrademy.com. */ ?>
    <div class="faq-list">
      <div class="faq-item">
        <button type="button" class="faq-q">Do I need an account to browse jobs? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>No — anyone can browse open positions without signing in. You'll need an account only when you're ready to apply.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">Can I apply to more than one role at once? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>Yes. Your profile is reusable, so applying to a second role doesn't mean starting over.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">I already have an UltrAdemy student account — do I need a new one? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>You can sign in to Careers with the same email and password. Your applicant activity stays separate from your learning account.</p></div>
      </div>
      <div class="faq-item">
        <button type="button" class="faq-q">How do I contact the recruitment team? <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></button>
        <div class="faq-a"><p>Reach us at <a href="mailto:careers@ultrademy.com">careers@ultrademy.com</a> with any questions about a role or your application.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band">
      <h2>Ready to take the next step?</h2>
      <p>Browse everything we're hiring for right now.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="app.php?r=jobs">View Open Positions</a>
      </div>
    </div>
  </div>
</section>
