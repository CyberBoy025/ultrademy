# Phase 11 — Careers Portal & Recruitment

Status: **built, running against the live database.** A public, anonymous-facing careers
site plus an internal recruitment back office. This is the platform's **first surface a
stranger can reach without an account**, which drives most of the decisions below.

Migrations [`058`](../../database/migrations/058_create_departments.sql)–[`080`](../../database/migrations/080_create_rate_limit_hits.sql)
(23 files, 22 tables).

---

## 1. Two surfaces, one codebase

| | LMS / platform | Careers portal |
|---|---|---|
| Entry | `public/app.php` | `public/careers/app.php` (and `index.php`) |
| Layout | `View::shell()` — sidebar app | `View::careersShell()` — public site chrome |
| Session cookie | `ultrademy_session` | `ultrademy_careers_session` |
| Audience | students, staff | job seekers, anonymous visitors |

The **separate session cookie is the important one**: signing into the careers portal does
not open a session in the LMS, and vice versa. A job applicant is not a student, and a
leaked careers session must not become a foothold in the platform that holds student
records and payment data. `Session::start()` takes the cookie name for exactly this.

`CAREERS_URL` is a single `.env` value (path-based today,
`http://localhost/ultra/public/careers`). When careers gets its own subdomain, that one
value changes — nothing in code hard-codes either form, because links go through
`careers_url()`.

### 1.1 Visual identity: the public site's template, not a third one

Careers shipped with its own design system — Fraunces/Manrope, a deep-green accent, a
sage ground — on the reasoning that it is not the LMS. That reasoning was half right. It
is not the LMS, but it *is* the public marketing site: the same anonymous visitor, the
same first impression, reached by a link in the ultrademy.com footer. Three design
systems for two audiences was one too many, and the odd one out was the surface a
stranger sees first.

So careers now wears the marketing template. Every careers page loads
[`public/css/site.css`](../../public/css/site.css) — the same tokens, type scale, buttons,
cards and `[data-theme]` swap the marketing site uses — followed by
[`public/careers/css/careers.css`](../../public/careers/css/careers.css), which was
rewritten from a 244-line parallel design system into a ~190-line **add-on**: no `:root`
block, no fonts, no palette, only the dozen-odd components a brochure site never needed
(flash messages, the profile wizard rail, the application status track, list rows, data
tables, the auth split-screen, long-form job-description prose). The chrome is
`.site-header` / `.site-footer` carrying a careers nav, and `public/js/site.js` is loaded
rather than copied so the theme toggle writes the **same** `localStorage['ultrademy.theme']`
key — toggle dark on careers and ultrademy.com is already dark.

Rejected: copying `site.css` into `careers/css/`. It saves one request and duplicates the
token block with no build step to keep the copies honest — which is exactly how careers
drifted from the marketing site the first time.

One behaviour changed. The old `careers.css` honoured `prefers-color-scheme`; `site.css`
does not, being purely `[data-theme]`-driven with a light default. An OS-dark visitor who
has never touched the toggle now gets light careers pages — the same as ultrademy.com.

What did **not** change is the separation that matters: careers still has its own session
cookie, its own login, and never renders `View::shell()`. Looking like the marketing site
is a presentation decision; it grants no access.

## 2. Data model

Grouped by what they are for:

| Group | Tables |
|---|---|
| Structure | `departments`, `job_categories`, `job_postings`, `job_posting_centres`, `job_questions` |
| Candidate profile | `job_applicant_profiles`, `applicant_education`, `applicant_experience`, `applicant_skills`, `applicant_certifications`, `applicant_references` |
| Applications | `job_applications`, `application_answers`, `job_application_documents`, `job_application_status_history`, `saved_jobs` |
| Interviewing | `interviews`, `interview_panelists`, `interview_feedback` |
| Back office | `recruitment_notes`, `recruitment_email_templates`, `recruitment_email_logs` |
| Abuse control | `rate_limit_hits` |

A job posting moves `draft → published → unpublished → closed`. An application moves
through a longer pipeline — `draft → submitted → received → under_review → shortlisted →
interview → assessment → final_review → selected`, with `rejected`, `withdrawn` and
`closed` reachable from most of it — and every transition is recorded in
`job_application_status_history` rather than only mutating the row, so "why was this
candidate rejected and by whom" survives.

`job_posting_centres` reuses the existing centre model, so a vacancy can be tied to
Gwagwalada, Kubwa, both, or none (head office / remote) — the same nullable-centre
convention the rest of the platform uses.

## 3. Roles are deliberately NOT platform administrator

Four dedicated roles, none of which is `administrator`:

| Role | Holds |
|---|---|
| `recruitment_admin` | job management, application decisions, reports, email templates |
| `recruiter` | view/review applications, manage interviews and notes — **but not decide** |
| `interviewer` | submit interview feedback only |
| `reporting_user` | recruitment reports only |

This is the same separation-of-duties spine used for cashier vs accountant in Phase 9:
`recruitment.application.review` and `recruitment.application.decide` are **different
permissions held by different roles**, so the person shortlisting candidates is not the
person who makes the hire/reject call.

A platform `administrator` gets **none** of these. Running the LMS is not the same job as
hiring people, and HR data is some of the most sensitive in any organisation. Verified in
the route sweep: an administrator gets 403 on every `recruitment.*` route.

## 4. Anti-abuse, because this surface is public

The LMS has never had a form a stranger could POST to. Careers has several (register,
apply, contact), so two new pieces exist:

- **`RateLimit`** — counts rows in a trailing window rather than incrementing a counter,
  so concurrent requests cannot race each other into under-counting. It records the hit
  **even when the request is already over the limit**, so a script hammering the endpoint
  does not earn a free retry each time it is blocked.
- **`Captcha`** — configured through `settings`, with `captcha_provider` empty meaning
  "not offered". This mirrors the convention `PaymentService` already uses for gateway
  keys: credentials live in settings so an administrator can rotate them without a deploy,
  and an empty value means the integration simply is not offered rather than half-working.
  Turnstile, reCAPTCHA v2 and hCaptcha share one integration shape, so any of the three
  works once real keys are entered. **No provider has been chosen** — that remains open.

## 5. Notifications reuse the Phase 10 engine

Rather than build a second notification system, careers adds a `recruitment` **category**
to the existing one. Two consequences worth stating:

- The careers inbox is **category-scoped** (`Notify::inbox($user, $limit, 'recruitment')`).
  A job applicant who also happens to be a student never sees their LMS or finance
  notifications on the careers site — the tables are shared, the views are not.
- Careers preference forms only ever touch the `recruitment` category, so saving them
  cannot silently reset a user's unrelated LMS notification settings.

`recruitment_email_templates` and `recruitment_email_logs` exist so candidate-facing email
is authored and audited rather than hard-coded. **They are still subject to the Phase 10
reality: no mail transport is configured, so nothing actually leaves the building yet.**

## 6. Two gaps found and fixed on review

- **The careers base URL 403'd.** `CAREERS_URL` points at the `careers/` directory, but
  there was no `index.php` and directory listing is off (project `.htaccess`), so anyone
  typing the documented base URL got a 403. Added `public/careers/index.php`, which
  forwards to `app.php` — and becomes the natural front controller after the subdomain
  cutover, so no URL has to change then.
- **The public website never linked to careers at all.** README §77 lists "Careers where
  applicable" in the footer; the portal existed but was unreachable from the marketing
  site. Added a Careers link to the footer's Company column, via `careers_url()` so it
  follows the environment rather than being hard-coded.

## 7. Known gaps

1. **No captcha provider chosen** (§4). The public forms are rate-limited but not
   bot-verified until keys are entered.
2. **No email actually sends** — inherited from Phase 10. Candidate emails queue and are
   logged; a transport is still needed.
3. **Subdomain cutover not done.** Careers runs at a path today. The `.env` value and
   `careers_url()` exist so the move is configuration, but the vhost work is outstanding —
   and it shares the Phase 7 release blocker: DocumentRoot must point at `public/`.
4. **No CV parsing or search ranking.** Applications are read by a human; there is no
   keyword scoring, and deliberately so — an unexplained ranking on hiring decisions is a
   fairness problem, not a feature.
5. **Interview scheduling has no calendar integration.** Interviews are stored with
   panelists and feedback, but nothing writes to anyone's real calendar.

## 8. Demo state

A fresh `migrate` + `seed` gives 4 departments, 4 job categories, 6 job postings and an
email template, reachable at `/ultra/public/careers/` without logging in. The whole
install is 80 migrations / 76 tables / 60 permissions / 16 roles.
