# UltrAdemy — Status by Phase

Updated **21 August 2026**. Phase numbering follows the revised roadmap in README §80.

**Scale:** 104 migrations · 92 tables · 236 application files · 28 controllers ·
59 models · 22 core classes · 119 views · 22 architecture documents · 81 tests.

> **Built vs installed.** Phases 8, 9b and 11 were built after the last push to
> `origin/main` (which sits at `11771ae`). They exist as commits and as a delivered zip.
> They are only live on a machine once that zip is unpacked and `database/migrate.php`
> has run. Everything else below is confirmed against the live database.

**Stack:** Apache 2.4.58 · PHP 8.2.12 · MariaDB 10.4.32 · vanilla PHP, no framework,
no Composer.

| Phase | Scope | Status |
|---|---|---|
| 0 | Discovery | ⛔ **Blocked** — no existing LMS supplied |
| 1 | Business & System Architecture | ✅ Done |
| 2 | Brand + Public Website UX | ✅ Done |
| 3 | UI/UX Design System | ✅ Done |
| 4 | Core Platform Foundation | ✅ Done |
| 5 | Centres & Operations | ✅ Done |
| 6 | Subscriptions & Entitlements | ✅ Done |
| 7 | Applications & Students | ✅ Done |
| 8 | LMS | ✅ **Done** — assessments built |
| 9 | Finance & Payments | ⚠️ Built, **never exercised** |
| 9b | Donations & Supporter Giving | ✅ **Built** — ships switched off, see below |
| 10 | Communication | ✅ Done |
| 11 | Affiliate | ✅ **Built** — ships switched off |
| 12 | Management & Reporting | ✅ **Built** |
| 13 | Corporate Training | ✅ **Built** — ships switched off |
| 14 | Testing & Security | ⚠️ Started — 81 tests, no DB or scope tests |
| 15 | Data Migration | ⛔ Blocked on Phase 0 |
| 16 | Deployment | ❌ Not started |
| — | Careers / Recruitment ATS | ✅ Done — **not in the brief** |

Fourteen phases complete, one partial, one untouched, two blocked.

---

## Phase 0 — Discovery ⛔

Report items 2–8 of §51 audit an existing LMS. **None has been supplied**, so those
seven sections were never written rather than invented (§51: *"Do not assume something
exists if it has not been verified"*).

To close: provide the old source, a SQL dump, or screenshots — or confirm no prior
system exists, which closes Phase 15 at the same time.

## Phase 1 — Architecture ✅

17 documents in `docs/architecture/`: the original six plus one per implementation
phase (07 public website through 16 careers portal). Implementation follows them —
centre-scoped `user_roles`, polymorphic invoices, the gateway interface and
`webhook_events` idempotency table are all present as designed.

25 decisions remain on defaults. Decision 1 (Laravel vs vanilla PHP) was answered by
implementation: vanilla PHP.

## Phase 2 — Public website ✅

`index.php`, `programmes.php`, `programme-detail.php`, `centres.php`, `about.php`,
`contact.php`, `css/site.css` (18.9 KB), `js/site.js`.

## Phase 3 — Design system ✅

`site.css` public · `shell.css` (21.8 KB) app · `careers.css` recruitment portal.
Cyan/magenta tokens, light and dark. Student dashboard reference in `docs/preview/`.

## Phase 4 — Core foundation ✅

Auth, sessions, CSRF, rate limiting, captcha, uploads, audit. Registration with email
verification, login, logout.
**Live data:** 18 users · 18 profiles · 16 roles · 60 permissions · 171 role-permission
grants · 14 role assignments · 13 settings · 62 audit entries.

## Phase 5 — Centres & operations ✅

**Live data:** 3 centres (Gwagwalada, Kubwa, Abuja Central *planned*) · 110 rooms ·
5 staff postings · 3 programme categories · 5 published programmes · 5 programme-centre
links · 2 cohorts · 22 class groups · 2 sessions · 2 attendance records ·
0 equipment records.

## Phase 6 — Subscriptions & entitlements ✅

`Entitlements.php`, a `402.php` view for upgrade prompts, admin override screen.
**Live data:** 13 features · 4 packages · 35 package-feature grants · 2 subscriptions.
The feature registry matches the architecture doc exactly.

## Phase 7 — Applications & students ✅

Application submission, document upload, review, admission, enrolment.
**Live data:** 2 applications · 1 document · 4 enrolments.

## Phase 8 — LMS ✅

**Built:** courses, modules, lessons, materials, progress tracking, assignments,
submissions, grading queue, certificates, lesson editor.

**Assessments added 20 Aug 2026** (migrations 081–086) — quizzes and examinations with
five question types, server-side timing, attempt limits, automatic marking, a manual
marking queue for essays, pass marks and result-release rules. Design in
`docs/architecture/17-assessments.md`.

The `assessments` feature, sold in Premium and Advanced since Phase 6, now delivers
something.

**Not built, deliberately:** question banks with random selection, partial credit,
per-question timers, proctoring — listed with reasons in 17-assessments.md §10.

## Phase 9 — Finance & payments ⚠️ Built, never exercised

**Built:** `PaymentService` with `PaystackGateway`, `FlutterwaveGateway` and
`ManualGateway` behind one interface. `Money.php`, `DocumentNumber.php`,
`Reconciliation.php`, `webhook.php`, `payment-return.php`, nine finance views including
the manual-transfer verify queue.
**Live data:** 3 invoices · 3 lines · 2 payments · 2 receipts · 1 refund ·
2 number sequences.

**Empty tables that matter:**

| Table | Meaning |
|---|---|
| `webhook_events` | **No gateway webhook has ever been received.** |
| `payment_proofs` | Manual bank transfer never exercised. |
| `reconciliation_runs` | Reconciliation never run. |
| `expenses` | No expense recorded — accounting side of §28 untested. |

Per the architecture, the webhook *is* the source of truth for payment status. A
payment system whose webhook path has never fired has not been proven.

## Phase 9b — Donations & Supporter Giving ✅ Built, switched off

Public giving page, campaigns with targets and a donor wall, guest donations without
registration, admin ledger with CSV export. Design in
`docs/architecture/18-donations.md`.

**Reuses the existing money spine** — a donation raises an invoice with
`payable_type = 'donation'` and settles through the same gateways, webhook, receipts and
reconciliation. Migration 089 is the whole integration.

**`donations_enabled` ships as 0**, and a campaign cannot be published while it is off.
Do not switch it on until the payment webhook has been proven end to end — a donor whose
payment silently fails has no invoice to chase and no service to miss, so nobody ever
finds out. See 18-donations.md §8.

**Flagged:** the request said "investors can donate". A donation is a gift; an investment
is a security under SEC Nigeria rules. What is built is the donation, and the copy says
so. 18-donations.md §1.

## Phase 10 — Communication ✅

Direct chat, group conversations, announcements, notifications, preferences screen,
cron scripts.
**Live data:** 4 conversations · 10 participants · 1 message · 24 notifications ·
1 announcement.

## Phase 11 — Affiliate ✅ Built, switched off

Referral links, attribution at registration, automatic commission on a referred user's
first qualifying payment, approval workflow, payout sweep with locking. Design in
`docs/architecture/19-affiliate.md`.

Closes the last feature sold with nothing behind it — `affiliate_programme` had been
granted by every package since Phase 6.

Three database constraints carry the integrity: one referral per person ever, one
commission per payment ever, rates snapshotted at earning. Commission rounds **down**.
Affiliates see referrals by date and status only, never names or emails.

**`affiliate_enabled` ships as 0.** Agree the rate, the first-payment-only rule and the
payout minimum before switching it on.

**Known gap:** no clawback when a referred user is refunded. Needs a decision before real
volume — 19-affiliate.md §10.

## Phase 12 — Management & Reporting ✅

Management overview, §16 centre comparison, and academic performance. CSV export on
four reports, all audited. Design in `docs/architecture/20-management-reporting.md`.

Every query is centre-scoped through the same resolver as the rest of the system —
reporting does not get its own access model. The centre comparison is refused to a
centre-scoped viewer, because comparing centres means reading another centre's data.

Server-rendered SVG charts, no library, each with a table view. Palette validated for
colour-vision deficiency in both themes.

**Not built:** PDF export (CSV only), scheduled email reports, retention curves,
aggregate tables for scale. Listed in 20-management-reporting.md §6.

## Phase 13 — Corporate Training ✅ Built, switched off

The full §46 chain — organisations, training requests, proposals, contracts,
participants, client report — plus a public enquiry form and an invitation flow for
nominated employees. Design in `docs/architecture/21-corporate-training.md`.

**No parallel system.** A contract is delivered through a real cohort, so participants
get ordinary enrolments and the existing attendance, assessment and certificate
machinery. It is invoiced through the ordinary invoice spine — migration 103 is one
`ALTER`.

**Nominating is not consent.** An employer supplying an email does not create an account;
that happens when the person clicks their own link. An existing account's password is
never changed by an invitation, which would otherwise be an account-takeover path.

**`corporate_enabled` ships as 0.** The public form is closed; the internal pipeline works
so phone and email enquiries can be recorded from day one.

**Sharpest gap:** invitations are generated, not sent. Someone copies each link and sends
it. Wiring it to `Notify` is small but wants the outbound email path proven first.

## Phase 14 — Testing & Security ⚠️ Started

`tests/` now exists — a runner (`php tests/run.php`) and 29 passing assertions covering
assessment marking, the expiry clock and result visibility.

**Still missing, and these matter most:** nothing verifies that a Gwagwalada manager
cannot read Kubwa data, or that a cashier cannot verify a bank transfer — the two
controls §42 is most emphatic about. Nor is anything testing the database layer, which
needs a MySQL instance in the test environment.

## Phase 15 — Data Migration ⛔ Blocked

§48. Nothing to migrate from until Phase 0 closes.

## Phase 16 — Deployment ❌ Not started

Local XAMPP only. No staging, no production, no backup schedule.

---

## Outside the roadmap — Careers / Recruitment ATS ✅

Not requested anywhere in the brief. A complete applicant-tracking system:
22 migrations (058–079), its own portal under `public/careers/` with separate login,
registration, dashboard and a 7-step application wizard, plus a recruitment backend.

**Live data:** 4 departments · 4 job categories · 7 postings across 7 centre links ·
2 screening questions · 4 applicant profiles with education, experience, skills,
certifications and references · 3 job applications · 8 status-history entries ·
2 interviews with panelists and feedback · 1 email template · 4 send logs · 1 saved job.

Roughly a quarter of the codebase.

---

## Also missing

| Gap | Brief |
|---|---|
| Public-site CMS — no `posts`, `testimonials`, `faqs`, `pages`, `seo_meta` tables | §65, §67, §72 |
| Blog | §67 |
| Testimonials with approval workflow | §65 |
| Sitemap, SEO metadata management | §73 |

Public content is hard-coded in PHP, so §72's requirement that content changes not need
a developer is unmet.

---

## Suggested order

1. ~~Assessments.~~ **Done — 20 Aug 2026.**
2. **Exercise the payment path** end to end — Paystack and Flutterwave test keys, a
   real webhook, one manual transfer through verification. **Prerequisite for 9b.**
3. ~~Phase 9b — donations.~~ **Built** — switch on only after step 2.
4. **Permission and centre-scope tests.** The two §42 controls, unverified.
5. ~~Affiliate module.~~ **Built.**
6. **CMS**, so content stops needing a developer.
7. ~~Management reporting.~~ **Built.**
