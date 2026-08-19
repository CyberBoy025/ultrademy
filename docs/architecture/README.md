# Phase 1 — Business & System Architecture

Status: **draft, awaiting approval.** Per §51 and §2 of the brief, no production code
is written until this is signed off.

---

## Documents

| # | Document | Covers (brief §) |
|---|---|---|
| 01 | [System Architecture](01-system-architecture.md) | 41, 42, 43, 47, 50 |
| 02 | [Domain Model & ERD](02-data-model.md) | 9, 12–14, 40 |
| 03 | [Roles, Permissions & Centre Scoping](03-rbac.md) | 15, 29, 32, 33, 42 |
| 04 | [Subscriptions & Entitlements](04-subscriptions-entitlements.md) | 6, 7 |
| 05 | [Finance, Payments & Audit](05-finance-payments.md) | 26–31, 39 |
| 06 | [API, Notifications & Reporting](06-api-notifications.md) | 37, 38, 41 |
| 07 | [Phase 2 — Brand + Public Website](07-public-website.md) | 53–80 |
| 08 | [Phase 3 — UI/UX Design System](08-ui-design-system.md) | 44, 49, 4 |
| 09 | [Phase 4 — Core Platform Foundation](09-core-foundation.md) | 4, 5, 49 |
| 10 | [Phase 5 — Centres & Operations](10-centres-operations.md) | 9, 12–17, 21–22 |
| 11 | [Phase 6 — Subscriptions & Entitlements](11-subscriptions-entitlements.md) | 6, 7 |
| 12 | [Phase 7 — Applications & Students](12-applications-students.md) | 10, 11, 14, 34 |
| 13 | [Phase 8 — LMS](13-lms.md) | 18, 19, 20, 23 |
| 14 | [Phase 9 — Finance & Payments](14-finance-payments.md) | 26–31, 39 |
| 15 | [Phase 10 — Communication](15-communication.md) | 24, 37 |
| 16 | [Phase 11 — Careers Portal & Recruitment](16-careers-portal.md) | 40, 43, 51 |

Design system and UI decomposition live one level up in `docs/`.

---

## Phase 0 — what is still missing

The brief's report specification (§51) has 43 items. Items **2–8** describe an existing
LMS: its audit, technology stack, architecture, modules, database, user roles and
workflows. **No existing system has been provided to this project.**

Those seven sections are therefore **not written**, and are not guessed. Per §51:
*"Do not assume something exists if it has not been verified."*

To close them, provide any of:

- the existing LMS source code
- a SQL dump or schema export
- screenshots of the current admin and student areas
- a list of what the current system does that must not be lost

If there is no existing LMS and UltrAdemy is starting fresh, say so and those items get
marked *not applicable* rather than left open.

The same applies to §48's migration strategy — it cannot be designed without knowing
what is being migrated from.

---

## Decisions required

Grouped by when they become blocking. Items marked **schema** change table columns and
are cheapest to answer now.

### Before Phase 4 (Core Foundation) — resolved

| # | Question | Answer |
|---|---|---|
| 1 | Laravel 11, or vanilla PHP? | **Vanilla PHP** — chosen explicitly, overriding this doc's stated default; see [09-core-foundation.md](09-core-foundation.md) §7 |
| 2 | NGN only, or multi-currency? *(schema)* | NGN — default taken, `currency` column retained |
| 4 | Expected concurrent users in year one? | 500 — default taken |
| 10 | Is the accountant role global, or scoped per centre? | Global — default taken, seeded accordingly |

### Before Phase 6 (Subscriptions) — resolved

All five went unanswered, so the stated defaults were built. See
[11-subscriptions-entitlements.md](11-subscriptions-entitlements.md) §6.

| # | Question | Built as |
|---|---|---|
| 12 | Grace period after subscription expiry? | None — hard stop at `ends_at` |
| 13 | Proration on mid-cycle upgrade? | None — upgrade supersedes and starts a full period |
| 14 | May a user hold two active subscriptions? | No — enforced by a DB unique index |
| 15 | Is the affiliate programme available on Basic? | Yes |
| 16 | Do staff implicitly receive student features? | Operational only (`operations` + `comms`) |

### Before Phase 7 (Applications & Students) — resolved

All five went unanswered, so the stated defaults were built. See
[12-applications-students.md](12-applications-students.md) §5.

| # | Question | Built as |
|---|---|---|
| 3 | Application fee charged on apply, or on admission? *(schema)* | On admission — enrolment starts `pending_payment` |
| 7 | May a student hold enrolments at two centres at once? *(schema)* | Yes |
| 8 | Should a centre manager see online-only revenue and enrolments? | No — online rows are invisible to a scoped role |
| 9 | May a centre manager approve admissions, or only recommend? | Recommend only — enforced in the service, not just the UI |
| 11 | May instructors see student contact details? | No — names and student numbers only |

### Before Phase 9 (Finance) — resolved

All five went unanswered, so the stated defaults were built. See
[14-finance-payments.md](14-finance-payments.md) §7.

| # | Question | Built as |
|---|---|---|
| 17 | Instalment plans for programme fees? | Supported — an invoice accepts several payments |
| 18 | Late-payment penalty? | None — overdue is flagged, not charged |
| 19 | Bank details — global setting, or per centre? *(schema)* | Global settings |
| 20 | Sequentially numbered receipts for tax? | Yes — `RCP-YYMM-nnnn`, one per payment |
| 21 | Financial audit retention period? | 7 years assumed — **still needs legal input** |

### Before Phase 11 (Affiliate)

| # | Question | Default |
|---|---|---|
| 6 | Commission on first payment only, or all payments? *(schema)* | First payment |

### Lower urgency

| # | Question | Default |
|---|---|---|
| 5 | Public certificate verification by serial? | Yes |
| 22 | SMS provider? | Deferred |
| 23 | May students opt out of attendance notices? | Yes |
| 24 | Public API for corporate clients? | No |
| 25 | Report export formats? | CSV + PDF |

**Every default above is implemented if no answer is given.** They are marked so nobody
later mistakes an assumption for a requirement.

---

## Risks

| Risk | Impact | Mitigation |
|---|---|---|
| No existing-system audit | Migration cannot be planned; features may be lost | Obtain the old LMS, or confirm none exists |
| Scope is very large for one build | Slipped dates, half-finished modules | Phases 4–7 are the minimum viable platform; 8–13 ship incrementally |
| Two commercial fonts unlicensed | Design cannot ship as specified | Buy webfont licences, or accept the fallback stack |
| Payment webhook handling | Double-credit or missed payments | Idempotency table + nightly reconciliation (05 §5, §10) |
| Centre-scope leakage | A manager sees another centre's data — §42 violation | Enforced by global query scope, not per-query `where`; covered by tests |
| Cashier/accountant separation not honoured in UI | Fraud path | Enforced in services; UI cannot bypass |
| `audit_logs` table growth | Query slowdown | Partitioning plan before ~10M rows |
| Corporate training (§46) deferred to Phase 13 | Rework if sold earlier | Polymorphic invoice + nullable-centre cohort already accommodate it |

---

## Assumptions on record

1. UltrAdemy operates in Nigeria; currency NGN, timezone Africa/Lagos.
2. Gwagwalada and Kubwa are the only centres at launch; more will follow.
3. Physical training is the primary business; online learning is growth.
4. There is no existing LMS to migrate, **unless told otherwise**.
5. Volumes are hundreds, not tens of thousands, of concurrent users in year one.
6. Staff are internal employees; no external contractor access model is needed yet.
7. Data protection follows Nigeria's NDPR — **not legally reviewed**.

Assumption 7 in particular should be checked by someone qualified before production.
Nothing in this architecture is a substitute for legal advice on data protection or
financial record-keeping obligations.

---

## What happens next

Phases 2 and 3 (Brand + Public Website, UI/UX Design System) shipped as static
markup and demo data. Phases 4 and 5 (Core Platform Foundation, Centres & Operations —
[09](09-core-foundation.md), [10](10-centres-operations.md)) are the first to write
production code: a real MySQL database (20 migrations), session-based auth enforcing
the exact permission/scope model from [03-rbac.md](03-rbac.md), and working CRUD for
centres, rooms, staff, programmes, cohorts, timetabling and attendance — all verified
against real logins, not just described.

Phase 6 (Subscriptions & Entitlements —
[11](11-subscriptions-entitlements.md)) adds the packages/features/entitlements engine
that §7 demands: no code anywhere names a package, and changing what a tier grants is a
form submission rather than a deploy.

Phase 7 (Applications & Students — [12](12-applications-students.md)) closes the loop
from README §11: a visitor registers, applies, is reviewed, admitted and becomes a
student on **one account**, with the `applicant` and `student` roles granted and revoked
automatically.

> **Release blocker raised in Phase 7.** Apache serves this project from `htdocs/ultra`,
> so `public/` was never really the web root — `.env`, all source, and uploaded ID
> documents were fetchable over HTTP. `.htaccess` denials now block them, but production
> must use a vhost whose DocumentRoot is `.../ultra/public`. See
> [12-applications-students.md](12-applications-students.md) §6 and §8.

Phase 8 (LMS — [13](13-lms.md)) builds the content chain from README §18: courses,
modules, lessons, materials, progress, assignments, grading and certificates, including
public certificate verification (Decision 5). **Assessments/quizzes are deliberately not
in it** — see [13-lms.md](13-lms.md) §7 for why they were separated rather than rushed.

Phase 9 (Finance & Payments — [14](14-finance-payments.md)) adds invoices, payments, a
gateway abstraction, manual bank-transfer verification, receipts, refunds, expenses,
reports and reconciliation — and closes the two manual bridges: paying a subscription or
enrolment invoice now activates it automatically, whatever the payment method.

> **Two bugs worth knowing about, both found by testing and fixed.** Invoice numbering
> deadlocked under concurrency (15 of 48 attempts lost); and webhook idempotency, as §5
> literally describes it, let an *unsigned* request claim the dedupe slot and permanently
> block the genuine signed webhook from crediting a payment. See
> [14-finance-payments.md](14-finance-payments.md) §3.
>
> **Live gateway calls remain untested** — signature verification and idempotency are
> fully exercised locally, but `initialise()`/`verify()` need real API keys. Run a sandbox
> transaction per provider before launch.

Phase 10 (Communication — [15](15-communication.md)) adds the notification engine and
chat/groups, and closes the notification TODOs Phases 6, 7 and 9 left open. Phase 11
(Careers Portal — [16](16-careers-portal.md)) adds a public careers site and recruitment
back office on a separate session, with its own roles.

Next up per the revised roadmap (§80): **Phase 12 — Management & Reporting**.
