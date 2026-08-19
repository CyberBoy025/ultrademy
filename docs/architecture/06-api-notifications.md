# 06 — API, Notifications & Reporting

Phase 1 deliverable. Covers §37 (notification engine), §38 (reporting) and §41 (API
architecture for future mobile apps).

---

## 1. API design (§41)

The brief anticipates a Student App, Instructor App, Staff App and Management App. The
architectural consequence is stated once and holds everywhere: **the web UI is one
client, not the system.**

### Versioning and shape

```
/api/v1/...
```

Version in the path. Breaking changes bump the version; additive changes do not. A
deployed mobile app cannot be forced to update, so v1 stays alive until telemetry says
nobody is on it.

### Authentication

Token-based, not session cookies. In Laravel this is Sanctum personal access tokens.

```
POST /api/v1/auth/login       → { token, user, abilities }
POST /api/v1/auth/refresh
POST /api/v1/auth/logout      → revokes the presented token
```

- One token per device, named, individually revocable — a lost phone does not force a
  password reset.
- Tokens carry **abilities** derived from the user's permissions at issue time, and are
  re-checked server-side on every request. The ability list is a convenience for the
  client's UI, never the authority.
- Rate limited: 5 login attempts per minute per IP, 60 authenticated requests per
  minute per token (§42).

### Response envelope

Consistent shapes so clients need one parser:

```jsonc
// success
{ "data": { … }, "meta": { … } }

// collection
{ "data": [ … ], "meta": { "page": 1, "per_page": 20, "total": 137 } }

// error
{ "error": { "code": "entitlement_required",
             "message": "Your package does not include assignments.",
             "feature": "assignments" } }
```

### Status codes that carry meaning

| Code | Means | Client should |
|---|---|---|
| 401 | not authenticated | send to login |
| 403 | authenticated, lacks permission | show "not available to you" |
| **402** | lacks **entitlement** | show upgrade prompt |
| 422 | validation failed | show field errors |
| 409 | state conflict (already applied) | refresh and explain |
| 429 | rate limited | back off, respect `Retry-After` |

402 vs 403 is the distinction from `03-rbac.md` §6, surfaced to the client. Collapsing
them means a paywall renders as a security error.

### Endpoint groups

```
/api/v1/auth/*             login, refresh, logout, verify, password reset
/api/v1/me                 profile, entitlements, dashboard summary
/api/v1/programmes         browse, detail          (public — no token)
/api/v1/centres            list, detail            (public — no token)
/api/v1/applications       submit, track, documents
/api/v1/enrolments         mine, progress
/api/v1/courses            enrolled courses, modules, lessons, materials
/api/v1/assignments        list, submit, results
/api/v1/assessments        take, submit, results
/api/v1/attendance         mine; instructor: mark
/api/v1/calendar           my events
/api/v1/invoices           mine, detail
/api/v1/payments           initialise, verify, submit manual proof
/api/v1/subscriptions      packages, subscribe, cancel
/api/v1/conversations      list, messages, send
/api/v1/notifications      list, mark read
/api/v1/affiliate          stats, referrals, commissions
/api/v1/instructor/*       classes, students, attendance, grading
/api/v1/admin/*            scoped administrative operations
```

`/programmes` and `/centres` are unauthenticated on purpose — the public website (§57,
§59) consumes the same endpoints, which keeps published data in one place rather than
two.

### The rule that makes this work

Controllers call services. The web controller and the API controller for the same
operation call the **same** service method. If a business rule can be bypassed by
hitting the API instead of the form, the rule is in the wrong layer.

---

## 2. Notification engine (§37)

### Structure

```
EVENT (domain)              →  NOTIFICATION (what to say)  →  CHANNELS (how)
ApplicationApproved            ApplicationApprovedNotice       in_app
PaymentVerified                PaymentReceiptNotice            email
SubscriptionExpiring           SubscriptionExpiringNotice      sms (later)
ClassRescheduled               ClassRescheduledNotice
```

Services fire domain events. Listeners decide who is notified. Notifications decide the
content. Channels decide delivery. Four small responsibilities rather than one
`sendEmail()` call buried in a service.

### Channels

| Channel | Phase | Notes |
|---|---|---|
| In-app | 4 | `notifications` table, bell icon, unread count |
| Email | 4 | queued, retried 3× with backoff |
| SMS | later | §37 says future; interface exists from day one |
| Push | later | when mobile apps land |

Every notification lands **in-app** regardless of other channels, so the platform is
always the complete record.

### Triggers

Drawn from §37, with the actor each one reaches:

| Event | Recipients |
|---|---|
| Registration | user (verification link) |
| Account verified | user |
| Application submitted | applicant, centre manager of preferred centre |
| Application approved / rejected | applicant |
| Admission offered | applicant |
| Enrolment confirmed | student, instructor, centre manager |
| Invoice issued | payer |
| Payment successful | payer, accountant |
| Manual payment submitted | cashier queue, accountant |
| Manual payment verified / rejected | payer |
| Subscription expiring (7d, 1d) | subscriber |
| Subscription expired | subscriber |
| Assignment posted | enrolled students |
| Assignment graded | student |
| Assessment opens / closes soon | enrolled students |
| Class scheduled / rescheduled / cancelled | class group |
| Attendance marked absent | student |
| Programme published | subscribers to that category |
| Referral qualified | affiliate |
| Commission approved / paid | affiliate |
| Role granted or revoked | affected user |
| Account suspended | affected user |

### Delivery rules

- **All notifications queue.** Nothing user-facing waits on SMTP.
- **User preferences** per notification type per channel, with a small set that cannot
  be disabled: security, payment, and admission decisions.
- **Digest, not flood.** More than 5 notifications of one type within an hour collapses
  into a single digest — otherwise a bulk attendance mark sends 200 emails.
- **Quiet hours** for SMS: nothing between 21:00 and 07:00 Africa/Lagos except security
  alerts.
- **Failures retry** 3× with exponential backoff, then land in a failed-jobs queue that
  an administrator can see. A silently dropped admission notification is a real-world
  problem.

---

## 3. Reporting (§38)

### Approach

Reports read from the transactional tables in Phase 11. That is correct at UltrAdemy's
starting scale and wrong later — when reports begin to slow the app, introduce nightly
aggregate tables (`daily_centre_revenue`, `daily_attendance_summary`). Designing for
that now would be premature; leaving room for it is not.

Every report obeys the same scope rules as the rest of the system (`03-rbac.md` §4). A
centre manager running "revenue by centre" gets their centre. There is no separate
reporting permission model — that is how reporting tools leak data.

### Report catalogue

| Audience | Reports |
|---|---|
| Management | users and growth · active students · applications and conversion · enrolments by programme · revenue vs expenses · centre comparison · affiliate performance · completion rates |
| Accountant | revenue by period · expenses by category · outstanding balances · aged debtors · manual transfers pending · reconciliation exceptions · refunds |
| Centre manager | students at centre · attendance rates · class utilisation · instructor load · centre revenue and expenses · equipment status |
| Instructor | class roster · attendance per student · assignment submission rates · grade distribution · at-risk students |
| Administrator | user registrations · application queue · content pending approval · moderation queue · system activity |

### Filters and export

Common filter set across all reports: date range, centre, programme, cohort,
instructor, status. Export to CSV and PDF; exports are **audited** (who exported what,
when), because a full student export is a data-protection event.

---

## 4. Scheduled jobs

| Job | Frequency | Purpose |
|---|---|---|
| `subscriptions:expire` | hourly | move lapsed subscriptions to `expired`, fire events |
| `subscriptions:remind` | daily | 7-day and 1-day expiry notices |
| `invoices:mark-overdue` | daily | `issued` past `due_on` → `overdue` |
| `payments:poll-pending` | 10 min | verify payments stuck in `initiated` |
| `payments:reconcile` | nightly | gateway settlement vs local records |
| `attendance:remind` | daily | nudge instructors with unmarked sessions |
| `cohorts:transition` | daily | `open → running → completed` by date |
| `notifications:digest` | hourly | collapse floods |
| `audit:archive` | monthly | move rows past retention to cold storage |
| `backup:database` | nightly | full dump, 30-day retention |

Every job is idempotent — a double run must not double-charge, double-notify or
double-expire.

---

## 5. Open decisions

| # | Decision | Default taken |
|---|---|---|
| 22 | SMS provider (Termii, Africa's Talking, Twilio) | deferred; interface only |
| 23 | Can students opt out of attendance notifications | yes |
| 24 | Public API for third parties (corporate clients) | no — internal clients only for now |
| 25 | Report export formats | CSV and PDF |
