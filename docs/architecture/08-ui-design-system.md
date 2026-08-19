# Phase 3 — UI/UX Design System

Status: **draft, awaiting approval.** Covers original §49 Phase 2 scope, executed as
Phase 3 under the revised §80 ordering: design system, navigation, authentication,
registration, and every role dashboard in §44. Like Phase 2, this is a visual/UX build —
static HTML previews with demo data, no database, no real auth, no live routing.

---

## 1. What was built

All under `docs/preview/`, sharing one component kit:

| Area | File(s) |
|---|---|
| Shared shell & component kit | [`assets/shell.css`](../preview/assets/shell.css), [`assets/shell.js`](../preview/assets/shell.js) |
| Student dashboard | [`dashboard.html`](../preview/dashboard.html) *(pre-existing, migrated to the shared kit)* |
| Applicant dashboard | [`applicant.html`](../preview/applicant.html) |
| Affiliate dashboard | [`affiliate.html`](../preview/affiliate.html) |
| Instructor dashboard | [`instructor.html`](../preview/instructor.html) |
| Cashier dashboard | [`cashier.html`](../preview/cashier.html) |
| Accountant dashboard | [`accountant.html`](../preview/accountant.html) |
| Centre Manager dashboard | [`centre-manager.html`](../preview/centre-manager.html) |
| Management dashboard | [`management.html`](../preview/management.html) |
| Administrator dashboard | [`administrator.html`](../preview/administrator.html) |
| Login | [`auth-login.html`](../preview/auth-login.html) |
| Register | [`auth-register.html`](../preview/auth-register.html) |

That's all 9 dashboards from README §44, plus the two authentication screens §49/§4
call for. Every file is self-contained HTML with no build step — open directly, or via
`http://localhost/ultra/docs/preview/<file>.html`.

---

## 2. Why a shared kit, not 11 one-off files

The original `dashboard.html` (Phase 3-early artifact, built from a reference
screenshot) was ~500 lines of inline CSS. Duplicating that 11 times would mean any
future token or spacing change has to be made 11 times and will drift. Instead:

- `shell.css` holds every token (identical to `docs/DESIGN-SYSTEM.md`), the three-column
  shell, sidebar/nav, cards, and a new set of components the student-only reference
  didn't need: **KPI stat cards, data tables, status pills, approval queues, a
  stepper, quick-action tiles, and a two-column auth layout.**
- `dashboard.html` was migrated to load `shell.css` externally instead of an inline
  `<style>` block. Its content and visual output are unchanged — same persona, same
  gamified course-platform framing (§ note in the file: "structure, spacing,
  proportions and content follow the reference screenshot").
- Every other page reuses the same shell markup pattern (sidebar → main → rail) and
  the same classes, so the platform reads as one system per README §43, not nine
  different apps.

## 3. Layout variants

Two shell variants come out of the same CSS:

- `.shell` (three columns: sidebar, main, right rail) — used by roles with a natural
  "today at a glance" rail: student, applicant, affiliate, instructor, accountant,
  centre manager, management, administrator.
- `.shell.no-rail` (sidebar + full-width main) — used by **Cashier**, which is a
  single-task desk view (record payment, verify transfers) where a right rail would
  just be empty space.

## 4. Nav-per-role (README §44)

Each dashboard's sidebar nav matches the brief's table exactly:

| Role | Nav items |
|---|---|
| Student | Dashboard, My Courses, Classroom, Interactive Modules, Settings *(unchanged from the original reference)* |
| Applicant | Dashboard, My Application, Required Documents, Browse Programmes, Payments, Notifications |
| Affiliate | Dashboard, My Referrals, Performance, Commissions, Payouts |
| Instructor | Dashboard, My Classes, Students, Timetable, Attendance, Assignments |
| Cashier | Dashboard, Payments, Invoices, Receipts, Daily Transactions |
| Accountant | Finance Overview, Payments, Expenses, Manual Transfers, Reconciliation, Reports |
| Centre Manager | Centre Overview, Students, Classes & Cohorts, Attendance, Staff & Instructors, Centre Finance, Operations |
| Management | Company Overview, Centres, Students, Programmes, Finance, Staff, Performance & Reports |
| Administrator | System Overview, Users, Roles & Permissions, Programmes, Applications, Subscriptions, Content & Blog, Moderation, Settings |

## 5. New components (beyond the student reference)

| Component | Class | Used by |
|---|---|---|
| KPI stat card | `.kpi-grid` / `.kpi-card` | Affiliate, Cashier, Accountant, Centre Manager, Management, Administrator |
| Data table | `table.dt` | Affiliate, Instructor, Cashier, Accountant, Centre Manager, Management, Administrator |
| Status pill | `.status-pill` (success/warning/error/info/neutral) | All staff dashboards |
| Approval / verification queue | `.queue` | Applicant, Cashier, Accountant, Administrator |
| Stepper | `.stepper` | Applicant (application progress, README §10) |
| Quick actions | `.qa-grid` | Applicant, Accountant, Administrator |
| Two-column auth layout | `.auth-wrap` | Login, Register |

All built from the same tokens — no new colours, radii or shadows introduced.

---

## 6. Content decisions — what is real vs. placeholder

Same discipline as Phase 2 (README §51, §65, §68): nothing here is presented as real.

| Content | Status |
|---|---|
| Nav structure per role | **Real** — taken directly from README §44 |
| Component set (tables, KPIs, queues, stepper) | **Real design decisions** — these are the actual proposed patterns for Phase 4+ to implement against |
| Personas (Amaka Johnson, Michael Okoro, Grace Adeyemi, Tunde Bakare, Ifeoma Chukwu, Emeka Obi, Sarah Bello, Chidi Nwosu) | **Fabricated demo personas**, same convention as the original "Dan Robertson" student reference — clearly placeholder, not real staff or students |
| Figures (revenue, attendance %, referral counts, KPI numbers) | **Fabricated demo data** for layout purposes only — not real UltrAdemy financials |
| Google sign-in button on auth screens | **Visual only** — no OAuth wired up; a decision on whether Google sign-in ships at all is open |

---

## 7. What Phase 3 deliberately does not do

- No backend, no database, no real session/auth — `auth-login.html` and
  `auth-register.html` are visual references for Phase 4 to build against, not
  functioning forms.
- No routing between dashboards — each file is standalone; a signed-in shell with
  role-based routing is Phase 4 (Core Foundation) work.
- No permission logic — the Administrator page shows every admin section as if fully
  authorized; scoped/partial admin access (README §33, §42) is a backend concern.
- No mobile app — these are responsive web layouts only (breakpoints at 1279/1023/767px,
  matching `docs/UI-REFERENCE.md` §8).

---

## 8. Open questions for Phase 4

1. Confirm the icon-only sidebar collapse at `<1023px` is acceptable, or whether a
   labeled drawer is preferred on tablet.
2. Cashier's `.no-rail` layout — should other single-task roles (e.g. a future
   receptionist role) follow the same pattern?
3. Real KPI definitions: e.g. does "Outstanding Balances" on the Accountant dashboard
   include overdue-only or all unpaid invoices? Needs a business rule before Phase 9
   builds it for real.
4. Whether Google OAuth ships in v1 or is deferred — affects whether the button on the
   auth screens stays.
