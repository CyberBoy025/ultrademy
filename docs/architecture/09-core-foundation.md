# Phase 4 — Core Platform Foundation

Status: **built, running against a real database.** This is the first phase that
writes production code (README §49 architecture note) — everything before it was
markup and demo data. Decision 1 (framework) is now made: **vanilla PHP**, matching
the scaffold already committed, not Laravel (the doc's stated default). See the
decision record in §7 below.

---

## 1. What was built

| Area | File(s) |
|---|---|
| DB connection | [`app/core/Database.php`](../../app/core/Database.php) — PDO singleton, no query builder |
| Migrations | [`database/migrations/`](../../database/migrations/) (20 files) + [`database/migrate.php`](../../database/migrate.php) runner |
| Seed data | [`database/seed.php`](../../database/seed.php) |
| Session | [`app/core/Session.php`](../../app/core/Session.php) |
| Auth + permissions | [`app/core/Auth.php`](../../app/core/Auth.php) — implements the GLOBAL/CENTRES scope model from `03-rbac.md` §4 |
| CSRF | [`app/core/Csrf.php`](../../app/core/Csrf.php) — one token per session, checked on every POST |
| Audit trail | [`app/core/Audit.php`](../../app/core/Audit.php) — insert-only, per `02-data-model.md` §10 |
| View rendering + app shell | [`app/core/View.php`](../../app/core/View.php), [`app/views/layout/shell.php`](../../app/views/layout/shell.php) |
| Permission-filtered nav | [`app/core/Nav.php`](../../app/core/Nav.php) |
| Real login / register / logout | [`public/login.php`](../../public/login.php), [`public/register.php`](../../public/register.php), [`public/logout.php`](../../public/logout.php) |
| Authenticated-app front controller | [`public/app.php`](../../public/app.php) |
| Users & roles management | [`app/controllers/StaffController.php`](../../app/controllers/StaffController.php) (`users`, `users.store`, `users.status`) |
| Settings | [`app/controllers/PlatformController.php`](../../app/controllers/PlatformController.php) (`settings`) |
| Audit log viewer | same file (`audit`) |

`public/login.php` and `public/register.php` **replace** the Phase 2 "coming soon"
placeholders — same URLs, so nothing linking to them (the marketing nav, the CTA
buttons) needed to change.

---

## 2. Database

Tables from `02-data-model.md` §1 (identity), §10 (platform), built exactly as
specified: `users`, `user_profiles`, `roles`, `permissions`, `role_permissions`,
`user_roles`, `settings`, `audit_logs`. Two intentional sequencing deviations from the
doc's section order, both purely about **migration file ordering**, not schema intent:

- `centres` is created (migration 006) before `user_roles` (007), because
  `user_roles.centre_id` is a foreign key to it. The doc's §2 (centres) comes after
  §1 (identity) in the narrative; the migration files can't follow that order and
  still pass FK constraints.
- `class_sessions.lesson_id` is a plain nullable column with **no FK constraint** yet
  — the `lessons` table doesn't exist until Phase 8 (LMS). The constraint gets added
  by a Phase 8 migration once it can reference something real.

## 3. Auth & permission model

Implements `03-rbac.md` §1 and §4 directly:

```
authenticated?  → Auth::check()
permitted?      → Auth::can('module.resource.action')
in scope?       → Auth::scopeCentres('module.resource.action')
                   null = GLOBAL, [] = no access, [ids] = restricted to these centres
```

`super_admin` short-circuits both `can()` and `scopeCentres()` — the one shortcut the
RBAC doc allows (§7).

Seeded permissions are a **subset** of the full matrix in `03-rbac.md` §5 — only the
identity/staff/education/operations/platform permissions that something in Phase 4/5
actually checks. Finance, affiliate and communication permissions aren't seeded
because those modules don't exist yet; seeding permissions nothing checks would just
be dead rows. The full matrix is the target — see `database/seed.php` for exactly
what's granted to which role today.

**A real scoping bug was caught and fixed during this build**: the Users page
originally showed every user in the system to a centre manager, even though
`identity.user.view_any` is marked `◐` (scoped) for that role in the matrix. Fixed in
`User::allWithRoles()` to filter to users with a role grant or staff posting at the
manager's centre(s) — verified with an actual centre-manager login showing 3 users
instead of 10.

## 4. Routing

No router class, no `.htaccess` rewriting. `public/app.php` dispatches on
`?r=route.name` through a `match()` block — chosen over pretty URLs because it works
identically regardless of `AllowOverride` in the Apache vhost, which wasn't verified
for this XAMPP install. Clean URLs (`/programmes/web-development`, per README §73) are
a cheap rewrite to add later without touching a single controller — the route names
are already resource-shaped (`programmes.show`, `centres.store`).

## 5. Registration behaviour

Per `03-rbac.md` §2: *"student, applicant and affiliate are granted automatically...
never assigned by hand."* A user who self-registers via `public/register.php` gets
**no role at all** until something creates one — an enrolment, an application, an
affiliate signup. None of those workflows exist yet (Phases 6/7/11), so a fresh
signup today lands on a dashboard with an honest empty state ("No role assigned yet")
rather than a fabricated one. Verified end-to-end with a real registration.

## 6. Demo credentials

Seeded by `database/seed.php`, password `Password123!` for all:

| Email | Role | Centre |
|---|---|---|
| `super@ultrademy.com` | Super Administrator | Global |
| `chidi.nwosu@ultrademy.com` | Administrator | Global |
| `sarah.bello@ultrademy.com` | Management | Global |
| `manager.gwagwalada@ultrademy.com` | Centre Manager | Gwagwalada Hub |
| `emeka.obi@ultrademy.com` | Centre Manager | Kubwa Hub |
| `ifeoma.chukwu@ultrademy.com` | Accountant | Global |
| `tunde.bakare@ultrademy.com` | Cashier | Gwagwalada Hub |
| `grace.adeyemi@ultrademy.com` | Instructor | Gwagwalada Hub |
| `blessing.eze@ultrademy.com` / `kelvin.musa@ultrademy.com` | Student | — (enrolled at Gwagwalada) |

Not for production — this is a dev seed, and the script says so.

## 7. Decision record

| # | Decision | Chosen | Why |
|---|---|---|---|
| 1 | Framework | **Vanilla PHP** | The scaffold committed in Phase 0 (`app/controllers`, `app/models`, `config/bootstrap.php`) was already vanilla PHP, not Laravel — the architecture doc's stated default. Building on Laravel now would mean discarding that structure. User confirmed vanilla PHP explicitly when asked. |
| — | Autoloading | `spl_autoload_register` over 3 folders (`core`, `models`, `controllers`), no namespaces | Small app, small win from namespacing; convention-based lookup is enough |
| — | CSRF failure status | 403, not 419 | 419 (the Laravel convention) isn't a registered HTTP status; Apache/PHP on this box turned it into a raw 500. 403 is standard and was verified to work. |

---

## 8. What Phase 4 deliberately does not build

- Password reset / email verification flows (the fields exist — `email_verified_at`
  — but nothing sends mail yet; no mail transport has been chosen).
- Profile editing UI (`identity.profile.update` is seeded but unused this phase).
- Any of finance, LMS content, affiliate, communication — those are later phases and
  their permissions aren't seeded because there's nothing yet to check them against.
