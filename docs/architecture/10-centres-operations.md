# Phase 5 — Centres & Operations

Status: **built, running against a real database.** Depends on Phase 4 (auth, roles,
permissions) — see [09-core-foundation.md](09-core-foundation.md). Covers README §12–17,
§9 (programme catalogue only), §21–22 (attendance/calendar via `class_sessions`).

---

## 1. What was built

| Area | Controller | Views |
|---|---|---|
| Centres, rooms, equipment, staff postings | [`CentreController`](../../app/controllers/CentreController.php) | `centres/index`, `centres/show` |
| Programme catalogue + approval workflow | [`ProgrammeController`](../../app/controllers/ProgrammeController.php) | `programmes/index`, `programmes/show` |
| Cohorts, class groups, sessions (timetable), attendance | [`OperationsController`](../../app/controllers/OperationsController.php) | `cohorts/index`, `cohorts/show`, `timetable/index`, `attendance/index`, `attendance/mark` |
| Cross-centre staff roster & user management | [`StaffController`](../../app/controllers/StaffController.php) | `staff/index`, `users/index` |

Tables: `centres`, `rooms`, `equipment`, `staff_centres`, `programme_categories`,
`programmes`, `programme_centres`, `cohorts`, `class_groups`, `class_sessions`,
`enrolments`, `attendance_records` — all per `02-data-model.md` §2–5, migrations
006–020.

---

## 2. Proof it actually works — README §45

> *"The initial centres are Gwagwalada Hub and Kubwa Hub, but the architecture must
> support future centres without code changes... Administrators should be able to
> create new centres from the backend."*

Tested for real during this build: logged in as `super@ultrademy.com`, submitted the
"New Centre" form on `/app.php?r=centres` with **Abuja Central Hub** — the exact
example name §45 uses — and it exists in the `centres` table with zero code changes.
That's not a claim in a document; it's a row that exists right now.

## 3. Centre scoping, enforced not just documented

`CentreController::show()` checks `Auth::scopeCentres('staff.member.view_any')`
before rendering — a centre manager hitting another centre's `centres.show` gets a
real 403, not a UI that merely hides a button. Verified with an actual second login
as `manager.gwagwalada@ultrademy.com`:

- `centres.show&id=1` (their own, Gwagwalada) → 200
- `centres.show&id=2` (Kubwa) → 403, "Access Denied" page rendered

Same pattern protects the Users list (§4 of the Phase 4 doc — the scoping bug found
and fixed there).

## 4. `class_sessions` is the timetable, attendance is real

Per `02-data-model.md` §5: *"There is no separate timetable table — a second
representation of the same fact would drift."* `/app.php?r=timetable` queries
`class_sessions` directly, scoped by centre. `/app.php?r=attendance` lists sessions
in the same window and, for anyone with `operations.attendance.mark`, links to a real
marking form (`attendance.mark` → `attendance.save`) that writes to
`attendance_records` with the `(class_session_id, enrolment_id)` unique constraint
the doc specifies — re-marking a session updates the existing row via
`ON DUPLICATE KEY UPDATE` rather than erroring or duplicating.

Verified end-to-end: marked attendance for the seeded Web Development session, confirmed
both the `attendance_records` rows and the resulting audit log entry
(`attendance.marked`) exist.

## 5. Programme status workflow

Matches README §78's table exactly: `draft → pending_approval → approved →
published → archived`. Each transition is gated by a specific permission
(`education.programme.create` to submit, `.approve` to approve, `.publish` to publish
or archive) rather than one generic "edit" permission — same separation-of-duties
principle the finance permissions in `03-rbac.md` §5 use for cashier vs. accountant.

## 6. Enrolments — a deliberate scope compromise

`attendance_records.enrolment_id` is a hard foreign key in the Phase 1 ERD, but the
full applicant → admission → enrolment workflow is Phase 7 (Applications & Students),
not this one. Rather than leave attendance marking un-buildable, the `enrolments`
table was created now (schema matches `02-data-model.md` §4 exactly, minus the
`application_id` column — nullable and pointless before an `applications` table
exists) and **seeded with two demo students** directly enrolled into the Web
Development cohort, so attendance marking has real rows to work against.

This is explicitly a seed-data shortcut, not a built feature: there is no "enrol a
student" UI in this phase. `cohorts/show.php` says so directly when a cohort has zero
enrolments: *"Direct enrolment isn't built in this phase — the full applicant →
admission → enrolment workflow is Phase 7."*

## 7. What's real vs. what's still a shortcut

| Content | Status |
|---|---|
| Centres, rooms, equipment, staff postings | **Real** — created through the UI, not seeded-only |
| Programme catalogue (5 programmes) | **Seeded**, but the same catalogue the Phase 2 public site already showed as demo data — now backed by real rows instead of a PHP array. `public/programmes.php` (Phase 2) still reads its own static array; wiring the public site to this database is a follow-up, not done in this phase. |
| Cohort, class group, session data | **Mixed** — one cohort/group/two sessions seeded for testing continuity with the Phase 3 Instructor mockup (Grace Adeyemi, "Cohort A"); anything created via the UI from here is real |
| Attendance records | **Real** — written live through the marking UI, not pre-filled |
| Enrolments | **Seed-only shortcut**, documented above — not a built workflow |

## 8. What Phase 5 deliberately does not build

- No pagination anywhere yet — fine at seed-data volumes, will matter once Programmes
  or Users lists grow. Cheap to add (`LIMIT`/`OFFSET`) when it does.
- No conflict checking on `class_sessions` (double-booking a room or instructor isn't
  prevented) — needs a real business decision on how strict to be, not guessed here.
- No calendar UI — README §22 asks for one eventually; `/app.php?r=timetable` is a
  table, which is the same data, not the same UX.
- The public marketing site's programme pages (Phase 2) are not yet reading from this
  database — see §7 above.

## 9. Open questions for Phase 6+

1. Should the Phase 2 public site's programme pages switch to reading `programmes`
   from the database now, or wait until a CMS/admin content layer exists (README §72)?
2. Room/instructor double-booking — hard block, warn-and-allow, or not enforced at
   all until Phase 13 (testing) surfaces it as a real problem?
3. Pagination threshold — at what row count does the Users/Programmes list need one?
