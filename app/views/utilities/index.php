<?php /** @var bool $appDebug @var bool $forceHttps @var bool $isProduction */ ?>
<div class="topbar">
  <div><h1>Utilities</h1><p>Server and database maintenance actions.</p></div>
</div>

<div class="kpi-grid">
  <div class="card kpi-card">
    <div class="top"><span class="lab">Clear Cache</span></div>
    <p class="cap">Reset PHP's OPcache.</p>
    <form method="post" action="app.php?r=utilities.clearcache">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm">Clear Cache</button>
    </form>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab">Clear Log</span></div>
    <p class="cap">Truncate the configured PHP error log.</p>
    <form method="post" action="app.php?r=utilities.clearlog">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm">Clear Log</button>
    </form>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab">App Debug</span></div>
    <p class="cap">Currently <strong><?= $appDebug ? 'enabled' : 'disabled' ?></strong>. Verbose errors should stay off outside development.</p>
    <form method="post" action="app.php?r=utilities.toggledebug">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm"><?= $appDebug ? 'Disable' : 'Enable' ?> App Debug</button>
    </form>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab">Force HTTPS</span></div>
    <p class="cap">Currently <strong><?= $forceHttps ? 'enabled' : 'disabled' ?></strong>. Redirects every request to https:// when on.</p>
    <form method="post" action="app.php?r=utilities.togglehttps">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm"><?= $forceHttps ? 'Disable' : 'Enable' ?> Force HTTPS</button>
    </form>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab">Run Migration</span></div>
    <p class="cap">Apply any pending <code>database/migrations/*.sql</code> files.</p>
    <form method="post" action="app.php?r=utilities.migrate">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm">Run Migration</button>
    </form>
  </div>

  <?php if (!$isProduction): ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Import Demo Database</span></div>
    <p class="cap">Seed demo roles, users, programmes and centres. Safe to re-run.</p>
    <form method="post" action="app.php?r=utilities.importdemo" onsubmit="return confirm('Import demo data into this database?');">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm">Import Demo Database</button>
    </form>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab" style="color:var(--error)">Reset Database</span></div>
    <p class="cap">Drops every table and rebuilds an empty schema. <strong>Irreversible</strong> — including your own account.</p>
    <form method="post" action="app.php?r=utilities.resetdb" onsubmit="return confirm('This permanently deletes ALL data, including every user account. Continue?');">
      <?= Csrf::field() ?>
      <button type="submit" class="btn sm" style="color:var(--error);border-color:var(--error)">Reset Database</button>
    </form>
  </div>
  <?php else: ?>
  <div class="card kpi-card">
    <div class="top"><span class="lab">Import Demo Database</span></div>
    <p class="cap">Disabled in production.</p>
  </div>

  <div class="card kpi-card">
    <div class="top"><span class="lab">Reset Database</span></div>
    <p class="cap">Disabled in production.</p>
  </div>
  <?php endif; ?>
</div>
