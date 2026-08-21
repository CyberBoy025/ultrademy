# Phase 7 — Applications & Students

Status: **built, running against the live database.** Covers README §10, §11, §14, §34
and the admissions half of §44. Decisions 3, 7, 8, 9 and 11 were unanswered, so the
documented defaults were taken — see §5.

---

## 1. What was built

| Area | File(s) |
|---|---|
| Schema | migrations [`027`](../../database/migrations/027_create_applications.sql)–[`029`](../../database/migrations/029_add_application_id_to_enrolments.sql) |
| Models | [`Application`](../../app/models/Application.php), [`ApplicationDocument`](../../app/models/ApplicationDocument.php), extended [`Enrolment`](../../app/models/Enrolment.php), [`Role`](../../app/models/Role.php) grant/revoke |
| Applicant journey | [`ApplicationController`](../../app/controllers/ApplicationController.php); `applications/apply`, `applications/mine`, `applications/show` |
| Review queue | same controller; `applications/index` |
| Student roster | [`StudentController`](../../app/controllers/StudentController.php); `students/index`, `students/show` |
| Contact-detail policy | `Auth::maySeeContactDetails()` |

The full journey from README §11 now runs end to end:

```
APPLICANT → APPLICATION → UNDER REVIEW → APPROVED → ADMITTED → STUDENT
```

Verified live, each step by a different real login: applicant submits → centre manager
reviews → management approves and assigns a cohort → administrator admits → enrolment
`UD-2026-0003` created.

## 2. One account, changing relationships

README §10: *"Avoid duplicate accounts when an applicant becomes a student."*

There is no `applicants` table and no `students` table (02-data-model.md §12). An
applicant is a user with an open application; a student is a user with an enrolment.
The `applicant` and `student` roles are granted and revoked automatically, which is what
03-rbac.md §2 requires — Phase 4 deferred this because nothing yet created those records.

Verified against one account throughout:

| Event | Roles afterwards |
|---|---|
| Submits first application | `applicant` added |
| Admitted into a cohort | `student` added, `applicant` kept — another application still open |
| Last open application withdrawn | `applicant` removed, `student` kept |

Same `user_id` the whole way. No second account is ever created.

## 3. The entitlement question this phase had to settle

`programme_applications` is metered (Standard 1, Premium 3, Advanced unlimited, Basic
none). Applied literally to every path, a person with no subscription could never apply
for anything — which directly contradicts 04-subscriptions §8, whose Decision 3 default
states enrolment does **not** require a subscription because *"physical training is sold
on its own"*.

Resolved by metering the **self-service channel only**:

| Path | Gate |
|---|---|
| A user applying online for themselves | metered — 402 when at the limit |
| Staff recording an application for someone | permission only |
| Staff enrolling a walk-in directly | permission only |

This is consistent with `enrolments.application_id` being explicitly nullable for
"direct enrolment" in 02-data-model.md §4, and it keeps the front desk at Gwagwalada
working for someone paying cash. **Neither source document states this outright**, so it
is a reading, not a quotation — worth confirming. Recorded in the
`ApplicationController` docblock as well.

Verified: a subscriber at 1 of 3 applies successfully; a user with no subscription gets
the 402 upgrade wall rather than a 403.

## 4. Documents are treated as PII

Uploads are identity documents. Three protections, all tested:

1. **Stored outside anything routable** — random 32-hex filenames in
   `storage/app/documents/`, never the user's own filename, and served only by
   `documents.download`, which authorises first. A second student requesting another
   applicant's document gets **403**.
2. **Allow-list, plus content sniffing.** Extension must be pdf/jpg/png *and* the file's
   real MIME (via `finfo`) must match it. A `.php` shell was rejected on extension; a
   text file renamed `.pdf` was rejected on content. Neither reached disk.
3. **Served as `attachment` with `X-Content-Type-Options: nosniff`**, so nothing an
   applicant uploads can render or execute in a reviewer's browser.

## 5. Decisions 3, 7, 8, 9, 11 — defaults taken

| # | Decision | Implemented as |
|---|---|---|
| 3 | Application fee on apply or on admission | **On admission** — a new enrolment starts `pending_payment`; Phase 9 raises the invoice and flips it to `active` |
| 7 | Enrolments at two centres at once | **Yes** — nothing blocks a second enrolment at another centre |
| 8 | Centre manager sees online-only records | **No** — the queue filters on `preferred_centre_id IN (scope)`, so `NULL` (online) rows are invisible to a scoped role |
| 9 | Centre manager approves, or only recommends | **Recommends only** — `review` is a separate permission from `approve`/`reject` |
| 11 | Instructors see student contact details | **No** — names and student numbers only |

Decision 9 is enforced in the service, not the interface. A Gwagwalada centre manager
sees "Mark Under Review" and an explanatory note instead of Approve — and **posting the
approve request directly still returns 403**, which is the check that actually matters.

## 6. Three bugs found and fixed while testing

**Critical — the whole application tree was being served over HTTP.** Apache's
DocumentRoot is `htdocs`, and this project lives at `htdocs/ultra`, so `public/` was
never actually the web root. `http://localhost/ultra/.env` returned the database
credentials in plaintext; so did every source file, and — once this phase started
storing them — uploaded ID documents.

This has been true since the Phase 0 scaffold; it only became a *data* exposure now.
Fixed with `.htaccess` denials at the project root and in `app/`, `config/`, `database/`
and `storage/`. Re-tested: all five paths now return 403 while the app, the public site
and the Phase 3 previews are unaffected, and authorised downloads still work through the
controller.

`.htaccess` is defence in depth, not the real fix — see §8.

**`user_roles` duplicated every global grant.** `UNIQUE (user_id, role_id, centre_id)`
does not constrain rows where `centre_id IS NULL`, because NULLs never collide in a
unique index. `INSERT IGNORE` therefore had nothing to match and each `seed.php` run
added another copy of every global role: 41 rows where 11 were correct.
Migration [`030`](../../database/migrations/030_fix_user_roles_unique_key.sql)
deduplicates and keys on a generated `centre_key` (`IFNULL(centre_id, 0)`) instead.
Seeding three more times now leaves the count flat.

*(That migration also had to add the replacement index before dropping the old one — a
foreign key was using it, and MariaDB refuses with errno 1553 otherwise. `migrate.php`
now reports a failing migration cleanly instead of dumping a stack trace, since MySQL
DDL cannot be rolled back and a half-applied file needs to be obvious.)*

**Instructors could see student email addresses**, on the Phase 5 attendance sheet —
a Decision 11 violation that only surfaced when Decision 11 was implemented here. The
rule now lives in `Auth::maySeeContactDetails()` so the roster and the register cannot
drift apart. Verified: instructor sees names only, centre manager still sees contact
details.

## 7. Deliberately not built

- **No payment.** `pending_payment` is a real state, but nothing invoices or collects.
  Phase 9.
- **No notifications.** README §11 wants applicants notified at each transition; the
  `notifications` table is Phase 10, so nothing is queued rather than pretending.
- **No instructor-facing student list.** Instructors reach their students through the
  attendance register. A dedicated roster scoped to their own class groups needs a
  permission the RBAC matrix does not define — worth adding deliberately rather than
  inventing here.
- **No document requirements per programme.** Which documents a programme demands is
  currently the applicant's judgement; a required-documents checklist belongs with
  programme configuration.

## 8. Open questions

1. **Deployment must not rely on `.htaccess`.** The correct fix is a vhost whose
   DocumentRoot is `.../ultrademymain/public`, so nothing outside it is reachable even if
   `AllowOverride` is off. The `.htaccess` files protect the default XAMPP layout
   developers actually run; production needs the vhost. **This should be treated as a
   release blocker.**
2. Is the self-service-only metering in §3 the intended reading of
   `programme_applications`?
3. Should a programme define which documents are required, and should approval be
   blocked until they are all `accepted`?
4. Instructor student list — add a permission for it, or leave the register as the only
   route?
