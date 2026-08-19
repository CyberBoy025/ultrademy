<?php require __DIR__ . '/../config/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UltrAdemy — Student Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script>
  (function () {
    try {
      // Light is the default. Dark only applies if the user has chosen it here.
      var t = localStorage.getItem('ultrademy.theme');
      document.documentElement.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
    } catch (e) {}
  })();
</script>
<style>
/* ================================================================
   ULTRADEMY — reference layout, UltrAdemy brand colour only.
   Structure, spacing, proportions and content follow the reference
   screenshot. The only substitution is the purple identity for
   cyan #22C7E3 + magenta #FF00FF.
   ================================================================ */
:root{
  --cyan-50:#EDFBFD;--cyan-100:#D2F4F9;--cyan-200:#A9E9F3;--cyan-300:#6FDAEB;
  --cyan-500:#22C7E3;--cyan-600:#0FA6C0;--cyan-700:#0C8499;--cyan-800:#0A6675;
  --magenta-50:#FFEBFF;--magenta-100:#FFD1FF;--magenta-200:#FFA6FF;--magenta-300:#FF6BFF;
  --magenta-500:#FF00FF;--magenta-600:#D400D4;--magenta-700:#A600A6;--magenta-800:#7A007A;

  --bg:#F7F8FA;
  --surface:#FFFFFF;
  --surface-muted:#F1F3F5;
  --border:#E9EBEF;
  --text:#0B0B0F;
  --text-2:#4B5563;
  --text-3:#8A92A0;

  --brand-cyan-text:var(--cyan-700);
  --brand-magenta-text:var(--magenta-700);
  --track:#EDEFF3;
  --nav-active-bg:#111318;
  --nav-active-fg:#FFFFFF;

  --font-1:"Neulis Alt","Outfit","Poppins",system-ui,sans-serif;
  --font-2:"Neue Helvetica","Helvetica Neue","Inter",Helvetica,Arial,system-ui,sans-serif;

  --r-sm:8px;--r-md:14px;--r-lg:20px;--r-xl:26px;--r-full:999px;
  --sh-sm:0 1px 2px rgba(16,24,40,.04);
  --sh-md:0 2px 8px rgba(16,24,40,.06);
  --grad:linear-gradient(135deg,#22C7E3 0%,#A855C7 55%,#FF00FF 100%);
}
[data-theme="dark"]{
  --bg:#0B0F14;--surface:#141A21;--surface-muted:#1C242D;--border:#2A343F;
  --text:#FFFFFF;--text-2:#B4BECA;--text-3:#8794A3;
  --brand-cyan-text:var(--cyan-500);--brand-magenta-text:var(--magenta-500);
  --track:#232D38;--nav-active-bg:#FFFFFF;--nav-active-fg:#0B0F14;
  --sh-sm:0 1px 2px rgba(0,0,0,.4);--sh-md:0 2px 8px rgba(0,0,0,.45);
}

*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:var(--font-2);
  font-size:13px;line-height:1.55;-webkit-font-smoothing:antialiased}
h1,h2,h3,h4,button,.f1{font-family:var(--font-1)}
h1,h2,h3,h4,p{margin:0}
button{cursor:pointer;font-family:var(--font-1)}
:focus-visible{outline:2px solid var(--cyan-500);outline-offset:2px;border-radius:4px}
.sr-only{position:absolute;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0)}

/* ---------- shell ---------- */
.shell{display:grid;grid-template-columns:264px minmax(0,1fr) 406px;gap:20px;
  padding:24px;max-width:1440px;margin:0 auto;align-items:start}
.card{background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r-md);box-shadow:var(--sh-sm);padding:18px}
.sec-title{font-size:18px;font-weight:600;margin:24px 0 14px}
.cap{font-size:11px;color:var(--text-3);line-height:1.45}
.drop{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);
  background:var(--surface);color:var(--text-2);border-radius:var(--r-sm);
  padding:5px 10px;font-size:11px;font-weight:500}
.drop:hover{border-color:var(--cyan-300)}
.drop svg{width:11px;height:11px}

/* ---------- sidebar ---------- */
.side{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);
  box-shadow:var(--sh-sm);padding:14px;position:sticky;top:24px;
  display:flex;flex-direction:column;min-height:calc(100vh - 48px)}
.prof{display:flex;align-items:center;gap:9px;width:100%;background:none;
  border:1px solid transparent;border-radius:var(--r-md);padding:7px;text-align:left;color:inherit}
.prof:hover{background:var(--surface-muted)}
.pfp{width:32px;height:32px;border-radius:var(--r-full);flex:none;background:var(--grad);
  display:grid;place-items:center;color:#fff;font-family:var(--font-1);font-weight:600;font-size:12px}
.prof-t{flex:1;min-width:0}
.prof-n{font-family:var(--font-1);font-weight:600;font-size:13px;line-height:1.25;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chev{width:14px;height:14px;color:var(--text-3);flex:none}

.nav{display:flex;flex-direction:column;gap:2px;margin-top:12px}
.nav button{display:flex;align-items:center;gap:11px;padding:9px 11px;border-radius:var(--r-md);
  border:none;background:none;color:var(--text-2);font-size:13px;font-weight:500;
  width:100%;text-align:left}
.nav button svg{width:17px;height:17px;flex:none;stroke-width:1.7}
.nav button:hover{background:var(--surface-muted);color:var(--text)}
.nav button[aria-current="page"]{background:var(--nav-active-bg);color:var(--nav-active-fg);font-weight:600}
.badge{margin-left:auto;min-width:18px;height:18px;padding:0 5px;border-radius:var(--r-full);
  background:var(--magenta-500);color:#fff;font-family:var(--font-1);font-size:10px;
  font-weight:600;display:grid;place-items:center}

.rule{height:1px;background:var(--border);margin:14px 2px}

.meta{display:flex;align-items:center;gap:11px;padding:8px 11px;font-size:13px;color:var(--text-2)}
.meta svg{width:17px;height:17px;flex:none;stroke-width:1.7}
.meta .v{margin-left:auto;font-family:var(--font-1);font-weight:600;color:var(--brand-magenta-text)}

.avg{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;padding:6px 11px 0}
.avg span{aspect-ratio:1;border-radius:var(--r-full);display:grid;place-items:center;
  font-size:13px;background:var(--surface-muted);border:1px solid var(--border)}

.promo{margin-top:auto;border-radius:var(--r-lg);padding:16px;background:var(--grad);
  color:#fff;position:relative;overflow:hidden;min-height:140px}
.promo .top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.promo .top span{font-size:11px;opacity:.92}
.promo .cr{width:26px;height:26px;border-radius:var(--r-full);background:rgba(255,255,255,.25);
  border:1px solid rgba(255,255,255,.45);display:grid;place-items:center;
  font-family:var(--font-1);font-weight:700;font-size:11px;flex:none}
.promo h4{font-size:19px;font-weight:700;line-height:1.15;margin-top:26px;max-width:110px}
.promo .lnk{display:inline-block;margin-top:6px;font-size:10px;font-weight:500;
  text-decoration:underline;opacity:.95}
.promo .rk{position:absolute;right:-4px;bottom:-6px;font-size:46px;opacity:.9}

/* floating appearance switch — not part of the reference composition */
.appearance{position:fixed;top:16px;right:16px;z-index:50;width:34px;height:34px;
  border-radius:var(--r-full);border:1px solid var(--border);background:var(--surface);
  color:var(--text-2);display:grid;place-items:center;box-shadow:var(--sh-md)}
.appearance:hover{border-color:var(--cyan-300);color:var(--brand-cyan-text)}
.appearance svg{width:16px;height:16px;stroke-width:1.8}
.i-sun{display:none}
[data-theme="dark"] .i-sun{display:block}
[data-theme="dark"] .i-moon{display:none}

/* ---------- hero ---------- */
.hero{border-radius:var(--r-xl);background:var(--grad);color:#fff;overflow:hidden;
  display:grid;grid-template-columns:1fr 44%;min-height:250px;position:relative}
.hero-txt{padding:34px 30px;display:flex;flex-direction:column;justify-content:center}
.hero h1{font-size:38px;line-height:1.14;font-weight:700;letter-spacing:-.02em}
.hero .em{margin-top:8px;font-size:13px;opacity:.9}
.hero-cta{margin-top:22px;align-self:flex-start;background:rgba(255,255,255,.22);
  border:1px solid rgba(255,255,255,.42);color:#fff;border-radius:var(--r-full);
  padding:11px 22px;font-size:13px;font-weight:600;backdrop-filter:blur(4px)}
.hero-cta:hover{background:rgba(255,255,255,.32)}
.hero-img{position:relative;background:rgba(255,255,255,.1);display:grid;place-items:center;
  text-align:center;padding:20px;font-size:11px;line-height:1.5;color:rgba(255,255,255,.85)}
.hero-img::after{content:"";position:absolute;inset:0;
  background:linear-gradient(90deg,rgba(34,199,227,.55) 0%,rgba(255,0,255,0) 55%)}
.hero-img span{position:relative;z-index:1}

/* ---------- new courses ---------- */
.courses{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.course{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-md);
  box-shadow:var(--sh-sm);padding:14px;display:flex;align-items:center;gap:12px}
.course:hover{border-color:var(--cyan-300);box-shadow:var(--sh-md)}
.tile{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;
  font-size:18px;flex:none}
.tile.c{background:var(--cyan-50)}
.tile.m{background:var(--magenta-50)}
[data-theme="dark"] .tile.c{background:rgba(34,199,227,.16)}
[data-theme="dark"] .tile.m{background:rgba(255,0,255,.16)}
.course h4{font-size:14px;font-weight:600;margin-top:1px}

/* ---------- stats ---------- */
.row{display:grid;gap:14px;margin-bottom:14px}
.row-a{grid-template-columns:minmax(0,58fr) minmax(0,42fr)}
.row-b{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
.chead{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
.chead h3{font-size:14px;font-weight:600}
.pct{font-size:30px;font-weight:700;line-height:1;letter-spacing:-.02em;
  font-family:var(--font-1);color:var(--brand-cyan-text)}
.bar{height:7px;border-radius:var(--r-full);background:var(--track);overflow:hidden;margin-top:12px}
.bar span{display:block;height:100%;border-radius:var(--r-full);
  background:linear-gradient(90deg,#22C7E3,#FF00FF)}
.legend{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px;justify-content:center}
.legend.left{justify-content:flex-start}
.legend div{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text-2)}
.dot{width:7px;height:7px;border-radius:var(--r-full);flex:none}
.donut{display:grid;place-items:center;position:relative}
.donut .mid{position:absolute;text-align:center;pointer-events:none}
.donut .mid b{display:block;font-family:var(--font-1);font-size:22px;font-weight:700;line-height:1}
.ax text{font-size:9px;fill:var(--text-3);font-family:var(--font-2)}
.gl{stroke:var(--border);stroke-width:1}
.lgt{font-size:11px;color:var(--text-2);display:flex;align-items:center;gap:5px;margin-bottom:4px}

/* ---------- right rail ---------- */
.rail{display:flex;flex-direction:column;gap:20px;position:sticky;top:24px}
.mrow{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.mrow .m{font-family:var(--font-1);font-weight:500;font-size:12px}
.ib{width:26px;height:26px;border-radius:var(--r-sm);border:1px solid var(--border);
  background:var(--surface);color:var(--text-3);display:grid;place-items:center}
.ib:hover{border-color:var(--cyan-300);color:var(--brand-cyan-text)}
.ib svg{width:13px;height:13px;stroke-width:2}
.cal{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center}
.cal .wd{font-size:10px;color:var(--text-3);padding:4px 0;font-weight:500}
.cal .d{aspect-ratio:1;display:grid;place-items:center;border-radius:var(--r-full);
  font-size:11px;color:var(--text-2);border:1px solid transparent}
.cal .d:hover{background:var(--surface-muted)}
.cal .d.sel{background:var(--cyan-500);color:#00252B;font-weight:700}

.srow{display:flex;align-items:center;justify-content:space-between;margin-bottom:2px}
.srow h4{font-size:13px;font-weight:500}
.tl{margin-top:12px}
.tr{display:grid;grid-template-columns:36px 1fr;gap:8px;min-height:40px}
.tr .t{font-size:10px;color:var(--text-3);padding-top:1px}
.tr .lane{border-top:1px solid var(--border);position:relative;padding-bottom:6px}
.evt{background:var(--magenta-50);border:1px solid var(--magenta-100);
  border-left:3px solid var(--magenta-500);border-radius:var(--r-md);padding:9px 11px;margin-top:4px}
[data-theme="dark"] .evt{background:rgba(255,0,255,.12);border-color:rgba(255,0,255,.28)}
.evt .tag{display:inline-block;background:var(--magenta-500);color:#fff;font-family:var(--font-1);
  font-size:9px;font-weight:600;padding:2px 7px;border-radius:var(--r-full);margin-bottom:5px}
.evt h4{font-size:12px;font-weight:600;margin-bottom:1px}

.gauges{display:grid;grid-template-columns:1fr 1fr;gap:14px;text-align:center}
.gauges .lab{font-family:var(--font-1);font-size:12px;font-weight:600;margin-bottom:1px}
.gauges .gwrap{display:grid;place-items:center;position:relative;margin-top:8px}
.gauges .gmid{position:absolute;font-family:var(--font-1);font-size:17px;font-weight:700}

/* ---------- responsive ---------- */
@media (max-width:1279px){
  .shell{grid-template-columns:224px minmax(0,1fr) 330px;gap:16px;padding:16px}
  .hero{min-height:210px}.hero h1{font-size:30px}.hero-txt{padding:26px 22px}
}
@media (max-width:1023px){
  .shell{grid-template-columns:72px minmax(0,1fr)}
  .rail{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;position:static}
  .rail .gcard{grid-column:1/-1}
  .side{padding:10px 8px;min-height:0}
  .prof-t,.chev,.nav span,.badge,.meta span:not(.v),.meta .v,.avg,.promo,.rule{display:none}
  .nav button{justify-content:center;padding:11px}
  .hero{grid-template-columns:1fr}.hero-img{display:none}
}
@media (max-width:767px){
  .shell{grid-template-columns:minmax(0,1fr)}
  .side{flex-direction:row;position:static;align-items:center;min-height:0}
  .nav{flex-direction:row;flex:1;justify-content:space-around;margin:0}
  .courses,.row-a,.row-b,.rail{grid-template-columns:1fr}
  .hero h1{font-size:26px}
}
</style>
</head>
<body>

<button class="appearance" id="themeToggle" aria-pressed="false" aria-label="Switch appearance">
  <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
    <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
  <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
    <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
</button>

<div class="shell">

  <!-- ================= SIDEBAR ================= -->
  <aside class="side">
    <button class="prof">
      <span class="pfp" aria-hidden="true">DR</span>
      <span class="prof-t">
        <span class="prof-n">Dan Robertson</span>
        <span class="cap" style="display:block">Paid Member</span>
      </span>
      <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M6 9l6 6 6-6" stroke-width="2" stroke-linecap="round"/></svg>
    </button>

    <nav class="nav" aria-label="Main">
      <button aria-current="page">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/>
          <rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>
        </svg><span>Dashboard</span>
      </button>
      <button>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M4 5.5A1.5 1.5 0 015.5 4H11v16H5.5A1.5 1.5 0 014 18.5z"/>
          <path d="M20 5.5A1.5 1.5 0 0018.5 4H13v16h5.5a1.5 1.5 0 001.5-1.5z"/>
        </svg><span>My Courses</span><span class="badge">2</span>
      </button>
      <button>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M3 9l9-5 9 5-9 5-9-5z"/><path d="M7 11.5V16c0 1.7 2.2 3 5 3s5-1.3 5-3v-4.5"/>
        </svg><span>Classroom</span>
      </button>
      <button>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <circle cx="12" cy="12" r="3.2"/>
          <path d="M12 3.5v2.6M12 17.9v2.6M3.5 12h2.6M17.9 12h2.6M6.1 6.1l1.9 1.9M16 16l1.9 1.9M17.9 6.1L16 8M8 16l-1.9 1.9"/>
        </svg><span>Interactive Modules</span>
      </button>
      <button>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1A1.7 1.7 0 007 19.4a1.7 1.7 0 00-1.9.4l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 003 14.1H3a2 2 0 110-4h.1A1.7 1.7 0 004.6 8.9a1.7 1.7 0 00-.4-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 002.9 1.2l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>
        </svg><span>Settings</span>
      </button>
    </nav>

    <div class="rule"></div>

    <div class="meta">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <circle cx="12" cy="9" r="5"/><path d="M8.5 13.5L7 22l5-2.5L17 22l-1.5-8.5"/></svg>
      <span>Your Rank</span><span class="v">Gold 🥇</span>
    </div>
    <div class="meta">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M12 15a6 6 0 100-12 6 6 0 000 12z"/><path d="M9 14l-1 7 4-2 4 2-1-7"/></svg>
      <span>Your Achievement</span>
    </div>
    <div class="avg" aria-hidden="true">
      <span>👩🏽</span><span>🧑🏻</span><span>👨🏾</span><span>👩🏻</span>
      <span>🧑🏽</span><span>👨🏻</span><span>👩🏾</span><span>🧑🏾</span>
    </div>

    <div class="promo">
      <div class="top"><span>Your Credit Left</span><span class="cr">90</span></div>
      <h4>Upgrade to Pro</h4>
      <span class="lnk">Get 1 Month Free!</span>
      <span class="rk" aria-hidden="true">🚀</span>
    </div>
  </aside>

  <!-- ================= MAIN ================= -->
  <main>
    <section class="hero">
      <div class="hero-txt">
        <h1>Good Morning!<br>Dan Robert.</h1>
        <p class="em">albertdanrobert@gmail.com</p>
        <button class="hero-cta">Continue Learning</button>
      </div>
      <div class="hero-img"><span>Learner<br>photograph</span></div>
    </section>

    <h2 class="sec-title">New Courses</h2>
    <div class="courses">
      <article class="course">
        <div class="tile c" aria-hidden="true">🍲</div>
        <div><p class="cap">2/8 Watched</p><h4>Food Recipe</h4></div>
      </article>
      <article class="course">
        <div class="tile m" aria-hidden="true">💼</div>
        <div><p class="cap">4/8 Watched</p><h4>Branding</h4></div>
      </article>
      <article class="course">
        <div class="tile c" aria-hidden="true">🖼️</div>
        <div><p class="cap">10/25 Watched</p><h4>UI UX Design</h4></div>
      </article>
    </div>

    <h2 class="sec-title">Course Statistics</h2>

    <div class="row row-a">
      <div class="card">
        <div class="chead">
          <h3>Course Overview</h3>
          <button class="drop">Spoken English
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                 aria-hidden="true"><path d="M6 9l6 6 6-6" stroke-linecap="round"/></svg>
          </button>
        </div>
        <h4 style="font-size:17px;font-weight:600;letter-spacing:-.01em">Spoken English For Beginners</h4>
        <p class="cap" style="margin-top:4px">Continue your course and keep rocking!</p>
        <div style="display:flex;align-items:baseline;gap:6px;margin-top:22px">
          <span class="pct">81%</span><span class="cap">(completed)</span>
        </div>
        <div class="bar" role="progressbar" aria-valuenow="81" aria-valuemin="0" aria-valuemax="100"
             aria-label="Course completion"><span style="width:81%"></span></div>
      </div>

      <div class="card">
        <div class="chead"><h3>Word Usage</h3></div>
        <div class="donut">
          <svg viewBox="0 0 150 150" width="132" height="132" role="img"
               aria-label="Word usage, 527 words. Grammar 45 percent, idiom 30 percent, vocabulary 25 percent.">
            <circle cx="75" cy="75" r="58" fill="none" stroke="var(--track)" stroke-width="19"/>
            <circle cx="75" cy="75" r="58" fill="none" stroke="#22C7E3" stroke-width="19"
                    stroke-dasharray="164 400" transform="rotate(-90 75 75)"/>
            <circle cx="75" cy="75" r="58" fill="none" stroke="#FF00FF" stroke-width="19"
                    stroke-dasharray="109 455" transform="rotate(72 75 75)"/>
          </svg>
          <div class="mid"><b>527</b><span class="cap">words</span></div>
        </div>
        <div class="legend">
          <div><span class="dot" style="background:#22C7E3"></span>Grammar</div>
          <div><span class="dot" style="background:#FF00FF"></span>Idiom</div>
          <div><span class="dot" style="background:var(--track)"></span>Vocabulary</div>
        </div>
      </div>
    </div>

    <div class="row row-b">
      <div class="card">
        <div class="chead">
          <h3>Hours Spend Each Week</h3>
          <button class="drop">This Week
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                 aria-hidden="true"><path d="M6 9l6 6 6-6" stroke-linecap="round"/></svg>
          </button>
        </div>
        <svg viewBox="0 0 330 170" width="100%" height="170" class="ax" role="img"
             aria-label="Hours spent each day: Monday 4, Tuesday 5.5, Wednesday 3, Thursday 6.5, Friday 5, Saturday 7.5, Sunday 4.5 hours. Saturday is the current day.">
          <line class="gl" x1="26" y1="18"  x2="325" y2="18"/>
          <line class="gl" x1="26" y1="53"  x2="325" y2="53"/>
          <line class="gl" x1="26" y1="88"  x2="325" y2="88"/>
          <line class="gl" x1="26" y1="123" x2="325" y2="123"/>
          <line class="gl" x1="26" y1="145" x2="325" y2="145"/>
          <text x="4" y="21">8h</text><text x="4" y="56">6h</text>
          <text x="4" y="91">4h</text><text x="4" y="126">2h</text>
          <rect x="42"  y="75"  width="15" height="70"  rx="7" fill="#22C7E3"/>
          <rect x="83"  y="49"  width="15" height="96"  rx="7" fill="#22C7E3"/>
          <rect x="124" y="92"  width="15" height="53"  rx="7" fill="#22C7E3"/>
          <rect x="165" y="31"  width="15" height="114" rx="7" fill="#22C7E3"/>
          <rect x="206" y="58"  width="15" height="87"  rx="7" fill="#22C7E3"/>
          <rect x="247" y="14"  width="15" height="131" rx="7" fill="#FF00FF"/>
          <rect x="288" y="67"  width="15" height="78"  rx="7" fill="#22C7E3"/>
          <text x="46"  y="160">M</text><text x="87"  y="160">T</text><text x="128" y="160">W</text>
          <text x="169" y="160">T</text><text x="210" y="160">F</text><text x="251" y="160">S</text>
          <text x="292" y="160">S</text>
        </svg>
      </div>

      <div class="card">
        <div style="margin-bottom:10px">
          <div class="lgt"><span class="dot" style="background:#22C7E3"></span>Language Fluency Score</div>
          <div class="lgt"><span class="dot" style="background:#FF00FF"></span>Word Usage</div>
        </div>
        <svg viewBox="0 0 330 150" width="100%" height="150" class="ax" role="img"
             aria-label="Two trend lines over eight weeks. Language fluency score rises from 30 to 140, peaking at 524. Word usage, shown dashed, rises steadily from 20 to 90.">
          <polyline fill="none" stroke="#22C7E3" stroke-width="2.4" stroke-linecap="round"
                    stroke-linejoin="round"
                    points="14,118 58,96 102,106 146,64 190,74 234,32 278,46 322,16"/>
          <polyline fill="none" stroke="#FF00FF" stroke-width="2.4" stroke-dasharray="5 5"
                    stroke-linecap="round" stroke-linejoin="round"
                    points="14,134 58,128 102,114 146,120 190,98 234,90 278,74 322,66"/>
          <circle cx="234" cy="32" r="4" fill="#22C7E3" stroke="var(--surface)" stroke-width="2"/>
          <rect x="210" y="2" width="48" height="22" rx="7" fill="#0B0B0F"/>
          <text x="234" y="17" text-anchor="middle" fill="#fff" font-size="11" font-weight="600">524</text>
        </svg>
      </div>
    </div>
  </main>

  <!-- ================= RIGHT RAIL ================= -->
  <aside class="rail">
    <div class="card">
      <div class="chead"><h3>Calendar</h3></div>
      <div class="mrow">
        <button class="ib" aria-label="Previous month">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M15 6l-6 6 6 6" stroke-linecap="round"/></svg>
        </button>
        <span class="m">November 2023</span>
        <button class="ib" aria-label="Next month">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M9 6l6 6-6 6" stroke-linecap="round"/></svg>
        </button>
      </div>
      <div class="cal">
        <div class="wd">S</div><div class="wd">M</div><div class="wd">T</div><div class="wd">W</div>
        <div class="wd">T</div><div class="wd">F</div><div class="wd">S</div>
        <div class="d">01</div><div class="d sel" aria-current="date">02</div><div class="d">03</div>
        <div class="d">04</div><div class="d">05</div><div class="d">06</div><div class="d">07</div>
        <div class="d">08</div><div class="d">09</div><div class="d">10</div><div class="d">11</div>
        <div class="d">12</div><div class="d">13</div><div class="d">14</div>
        <div class="d">15</div><div class="d">16</div><div class="d">17</div><div class="d">18</div>
        <div class="d">19</div><div class="d">20</div><div class="d">21</div>
        <div class="d">22</div><div class="d">23</div><div class="d">24</div><div class="d">25</div>
        <div class="d">26</div><div class="d">27</div><div class="d">28</div>
        <div class="d">29</div><div class="d">30</div>
      </div>
    </div>

    <div class="card">
      <div class="chead" style="margin-bottom:10px"><h3>My Schedule</h3></div>
      <div class="srow">
        <h4>02 November 2023</h4>
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M9 6l6 6-6 6" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <p class="cap">All Days</p>
      <div class="tl">
        <div class="tr"><div class="t">11 am</div><div class="lane">
          <div class="evt">
            <span class="tag">Spoken English</span>
            <h4>Basic Grammar With Vocabulary</h4>
            <p class="cap">Continue your course and keep rocking!</p>
          </div>
        </div></div>
        <div class="tr"><div class="t">12 pm</div><div class="lane"></div></div>
        <div class="tr"><div class="t">1 pm</div><div class="lane"></div></div>
        <div class="tr"><div class="t">3 pm</div><div class="lane"></div></div>
      </div>
    </div>

    <div class="card gcard">
      <div class="chead" style="margin-bottom:12px">
        <h3 style="text-align:center;width:100%;line-height:1.35">Achievements and Areas<br>For Improvement.</h3>
      </div>
      <div class="gauges">
        <div>
          <p class="lab">Your Goals</p>
          <p class="cap">Achieved your goals</p>
          <div class="gwrap">
            <svg viewBox="0 0 100 100" width="86" height="86" role="img"
                 aria-label="Goals achieved: 75 percent">
              <circle cx="50" cy="50" r="40" fill="none" stroke="var(--track)" stroke-width="10"/>
              <circle cx="50" cy="50" r="40" fill="none" stroke="#22C7E3" stroke-width="10"
                      stroke-linecap="round" stroke-dasharray="188 251" transform="rotate(-90 50 50)"/>
            </svg>
            <span class="gmid">75%</span>
          </div>
        </div>
        <div>
          <p class="lab">Improvement</p>
          <p class="cap">Areas of improvement</p>
          <div class="gwrap">
            <svg viewBox="0 0 100 100" width="86" height="86" role="img"
                 aria-label="Areas of improvement: 25 percent">
              <circle cx="50" cy="50" r="40" fill="none" stroke="var(--track)" stroke-width="10"/>
              <circle cx="50" cy="50" r="40" fill="none" stroke="#FF00FF" stroke-width="10"
                      stroke-linecap="round" stroke-dasharray="63 251" transform="rotate(-90 50 50)"/>
            </svg>
            <span class="gmid">25%</span>
          </div>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
(function () {
  var btn = document.getElementById('themeToggle'), root = document.documentElement;
  function sync(){ btn.setAttribute('aria-pressed', String(root.getAttribute('data-theme') === 'dark')); }
  sync();
  btn.addEventListener('click', function () {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('ultrademy.theme', next); } catch (e) {}
    sync();
  });
  document.querySelectorAll('.cal .d').forEach(function (d) {
    d.addEventListener('click', function () {
      document.querySelectorAll('.cal .d.sel').forEach(function (s) {
        s.classList.remove('sel'); s.removeAttribute('aria-current');
      });
      d.classList.add('sel'); d.setAttribute('aria-current','date');
    });
  });
})();
</script>
</body>
</html>
