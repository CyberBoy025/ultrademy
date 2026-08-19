<?php
/**
 * Shared public-site header/nav.
 * Expects (optional): $active — nav key to mark active: home|programmes|centres|about|contact
 */
$active = $active ?? '';
$navItems = [
    'home'       => ['label' => 'Home',       'href' => 'index.php'],
    'programmes' => ['label' => 'Programmes', 'href' => 'programmes.php'],
    'centres'    => ['label' => 'Centres',     'href' => 'centres.php'],
    'about'      => ['label' => 'About',       'href' => 'about.php'],
    'contact'    => ['label' => 'Contact',     'href' => 'contact.php'],
];
?>
<header class="site-header">
  <div class="wrap">
    <a class="brand" href="index.php">
      <span class="brand-mark"><span>U</span></span>
      UltrAdemy
    </a>

    <nav class="nav-main" id="primaryNav" aria-label="Primary">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <button type="button" class="theme-toggle" id="themeToggle" aria-pressed="false">
        <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
        <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <span class="sr-only">Toggle dark mode</span>
      </button>
      <a href="login.php" class="btn btn-secondary btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
      <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="primaryNav">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        <span class="sr-only">Menu</span>
      </button>
    </div>
  </div>
</header>
