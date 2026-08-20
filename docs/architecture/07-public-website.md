# Phase 2 — Brand + Public Website / Landing Page UX

Status: **draft, awaiting approval.** Covers README §53–§80. This is a UX/visual build —
static markup and demo content, no database, no auth, no CMS backend. Those arrive in
later phases (see §5 below).

---

## 1. What was built

| Area | File(s) |
|---|---|
| Shared brand tokens & components | [`public/css/site.css`](../../public/css/site.css) — also consumed by the careers portal ([16-careers-portal.md §1.1](16-careers-portal.md)) |
| Shared nav / footer | [`app/views/partials/header.php`](../../app/views/partials/header.php), [`footer.php`](../../app/views/partials/footer.php) — careers rebuilds the same components in [`app/views/layout/careers.php`](../../app/views/layout/careers.php) rather than requiring these, because their hrefs are relative and careers sits one directory deeper |
| Homepage | [`public/index.php`](../../public/index.php) |
| Programme listing | [`public/programmes.php`](../../public/programmes.php) |
| Programme detail | [`public/programme-detail.php`](../../public/programme-detail.php) |
| Centres | [`public/centres.php`](../../public/centres.php) |
| About | [`public/about.php`](../../public/about.php) |
| Contact | [`public/contact.php`](../../public/contact.php) |
| Login / Register (placeholder) | [`public/login.php`](../../public/login.php), [`public/register.php`](../../public/register.php) |
| Demo programme data | [`app/views/partials/demo-programmes.php`](../../app/views/partials/demo-programmes.php) |

The Phase-3-early student dashboard preview that was previously sitting at
`public/index.php` has not been lost — it lives at `docs/preview/dashboard.html`, its
original location, and `public/index.php` is now the actual public homepage as §80
requires ("the homepage must be designed before or alongside the core application
architecture").

---

## 2. Brand applied

Tokens are pulled directly from `docs/DESIGN-SYSTEM.md` — cyan `#22C7E3` / magenta
`#FF00FF` identity, the Neulis Alt / Neue Helvetica type pairing (resolving to the
`Outfit` / `Inter` fallback stack per `docs/UI-REFERENCE.md` §7 until licensed webfonts
are added), the same radius/shadow/spacing scale, and the same light/dark token-swap
mechanism (`data-theme` on `<html>`, persisted to `localStorage` under
`ultrademy.theme`, applied pre-paint to avoid a flash). The public site and the
authenticated dashboard preview now share one visual language, as §75 requires.

`--gradient-brand-soft` is used sparingly — hero visual, CTA bands, mode icons — per the
design system's "used sparingly" rule.

---

## 3. Information architecture

```
/index.php              Home
/programmes.php          Programme listing (filter: All/Physical/Online/Hybrid/Corporate)
/programme-detail.php?slug=…   Programme detail
/centres.php              Our Centres (#gwagwalada, #kubwa)
/about.php                About
/contact.php               Contact (form UI only — no backend yet)
/login.php, /register.php   Placeholder — explains Phase 4 is where accounts land
```

Matches §73's intended clean-URL shape conceptually (`/programmes/web-development`,
`/centres/gwagwalada`); the `.php`-suffixed, query-driven form is a placeholder until
Phase 4 picks a routing approach (architecture README, Decision 1) and can rewrite these
to clean paths without changing the page templates.

Nav is intentionally narrower than the brief's example (§56): **Home · Programmes ·
Centres · About · Contact**, plus Login / Get Started. "Learning" folds into Programmes
(mode filter), and Affiliate is a homepage section + footer link rather than a full nav
item — five items reads cleaner on mobile and nothing forces a page that has no content
of its own yet.

---

## 4. Content decisions — what is real vs. placeholder

Per README §51 ("do not assume something exists if it has not been verified") and the
explicit bans on fabricated testimonials (§65) and invented company history (§68):

| Content | Status |
|---|---|
| Two centres — Gwagwalada, Kubwa | **Real** — confirmed in the brief |
| Training modes (physical/online/hybrid/corporate) | **Real** — confirmed in the brief |
| Programme names, outlines, durations (Web Development, Data Analysis, …) | **Placeholder** — plausible demo content in `demo-programmes.php`, clearly commented as such, to be replaced by real Programme Management data (Phase 7/8) |
| Testimonials / success stories | **Not fabricated.** Section ships as a "coming soon" empty state, matching the CMS approval workflow in §78 |
| About page mission/vision/history | **Draft copy.** Written to be plausible and non-committal (no invented stats, dates or achievements) — needs UltrAdemy sign-off before it's treated as final, per §68 |
| Centre street addresses, phone number | **Not fabricated.** Only the confirmed area (Gwagwalada/Kubwa, FCT) is shown; phone is "available on request" until a real number is provided |
| Blog posts | **Placeholder titles**, no fabricated authorship claims beyond "UltrAdemy Team" |
| Contact form | UI only. Submission shows a static "message received" state; no email/DB wiring — that depends on Phase 4 infrastructure decisions |

---

## 5. What Phase 2 deliberately does not do

- No CMS (§72) — homepage sections, programme data, blog and testimonials are hard-coded
  or `demo-programmes.php`-driven, not admin-editable yet. That's real backend work.
- No auth — `login.php` / `register.php` are honest placeholders, not stubs pretending to
  work.
- No SEO infra beyond per-page `<title>`/`<meta description>`/canonical tags — sitemap.xml,
  robots.txt and structured data are cheap to add later but need real, final URLs first.
- No real contact-form delivery, no real centre contact details.

---

## 6. Open questions for Phase 3+

1. Final nav wording/order — confirm before wiring into a shared layout component.
2. Real centre addresses, phone numbers and hours.
3. Approved About-page copy (mission/vision/history) from UltrAdemy.
4. First real testimonials/success stories, and who approves them (§78).
5. Confirm Login/Get Started should point at a unified `/register` flow (§4 of the
   brief) rather than separate applicant/student paths — affects the CTA wiring once
   Phase 4 ships accounts.
