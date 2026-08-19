# Phase 8 — LMS

Status: **built, running against the live database**, with one part of the roadmap line
deliberately left for a follow-up — see §7. Covers README §18, §19, §20, §23 and the
`certificates` half of §18.

The chain README §18 draws is now real end to end, except the ASSESSMENT link:

```
COURSE → MODULE → LESSON → LEARNING MATERIAL → ASSIGNMENT → [assessment] → RESULT → COMPLETION → CERTIFICATE
```

---

## 1. What was built

| Area | File(s) |
|---|---|
| Schema | migrations [`031`](../../database/migrations/031_create_courses.sql)–[`040`](../../database/migrations/040_link_class_sessions_to_lessons.sql) |
| Access policy | [`app/core/Learning.php`](../../app/core/Learning.php) |
| Shared upload rules | [`app/core/Upload.php`](../../app/core/Upload.php) |
| Models | [`Course`](../../app/models/Course.php), [`Lesson`](../../app/models/Lesson.php), [`Material`](../../app/models/Material.php), [`Progress`](../../app/models/Progress.php), [`Assignment`](../../app/models/Assignment.php), [`Certificate`](../../app/models/Certificate.php) |
| Authoring | [`CourseController`](../../app/controllers/CourseController.php); `courses/index`, `courses/show`, `courses/lesson-edit` |
| Learning environment | [`LearnController`](../../app/controllers/LearnController.php); `learn/index`, `learn/course`, `learn/lesson`, `learn/certificates` |
| Grading | [`GradingController`](../../app/controllers/GradingController.php); `grading/queue`, `grading/show` |
| Public verification | [`public/verify.php`](../../public/verify.php) |

Migration 040 also adds the `class_sessions.lesson_id` foreign key that Phase 4
deliberately left as a bare column, because `lessons` did not exist until now.

## 2. Three ways to reach course content, and only three

The gate is in one place — `Learning::requireCourseAccess()` — because the answer differs
by *why* someone is asking:

| Who | Basis | Failure |
|---|---|---|
| Staff holding `education.lesson.view` | permission | 403 |
| A learner enrolled in a programme containing the course | enrolment **+** `online_learning` entitlement | 403 if not enrolled, **402** if enrolled but not entitled |
| Anyone signed in, on a lesson marked `is_preview` | the lesson advertises itself | — |

That 403/402 split is the point. Verified live, all four cases:

```
cashier   (no permission, no enrolment)        → 403 Access Denied
kelvin    (enrolled, no subscription)          → 402 upgrade wall
kelvin    (same, but on a preview lesson)      → 200
grace     (instructor, no online_learning)     → 200
```

The instructor case is the one that would break under a naive implementation. Grace has
**no** `online_learning` entitlement — Decision 16 gives staff operational features only —
and must still open the course she teaches. Gating on entitlement alone would have locked
out every instructor in the system.

## 3. Progress and certificates

`lesson_progress` is unique on `(user_id, lesson_id)`, so marking complete is idempotent.
Course percentage is derived, never stored, so it cannot drift from the lessons that
actually exist.

Completing the final lesson issues a certificate automatically — **if** the learner's
package includes `certificates`. Verified: six of six lessons marked complete produced
`UD-CERT-2026-9B2BD509A2` without any manual step.

Certificate serials are **random, not sequential**. A sequential serial would let anyone
walk the range and enumerate every graduate — a privacy hole dressed up as a feature.

Decision 5 ("public certificate verification by serial? Yes") is implemented at
`public/verify.php`, reachable without an account. It deliberately discloses only what
confirms a claim someone has already made to you — holder name, award, date, validity.
Verified that it exposes **no** email and **no** student number, and that a revoked
certificate reports as revoked rather than vanishing.

## 4. Assignments

README §23 asked for instructions, due dates, file *and* text submission, grading,
feedback, scores and "resubmission where enabled" — all present.

`assignment_submissions` is unique on `(assignment_id, user_id)`: resubmission replaces
the row rather than accumulating copies a grader would have to disambiguate, and the
superseded file is deleted from disk rather than orphaned. Resubmitting clears the
previous score and feedback, because a grade attached to work that has since been
replaced is worse than no grade.

Grading is scoped: `education.assignment.grade` is `◐` in 03-rbac.md §5, so an
instructor's queue is limited to courses reachable from the cohorts they actually teach —
a course is not a place, so centre scoping would have been the wrong axis. Out-of-range
scores are rejected server-side (verified: 150/100 refused, previous grade untouched).

## 5. Upload rules now live in one place

Phase 7 put file validation inside `ApplicationDocument`. Phase 8 needed the same rules
for lesson materials and assignment submissions — three copies of "which extensions are
allowed" is how one copy quietly ends up permitting `.phtml`. Extracted to
`app/core/Upload.php`; `ApplicationDocument` was refactored onto it, so Phase 7 keeps
working while the rules exist once.

Everything still lands outside any routable path with a random filename, is validated by
extension allow-list **and** content sniffing, and is streamed only by a controller that
authorises first. Verified during this phase: a `.zip` that was not really a zip was
rejected on content, exactly as intended.

Materials additionally honour `is_downloadable` — README §20's "download **permitted**
materials". A view-only material returns 403 to a learner and 200 to staff.

## 6. Reaching students requires a programme link

A course is invisible to learners until it is both `published` **and** linked to a
programme, because `Course::forUser()` walks `enrolments → programme_courses`. That is a
consequence of the Phase 1 model (a programme is what you enrol in; a course is content),
not an oversight — so `courses/show` warns in orange when a course has no programme link
rather than letting an author wonder why nobody can see their work.

## 7. Assessments are NOT in this phase

The roadmap line for Phase 8 includes "quizzes, assessments". Those are **not built**.

A credible assessment engine is its own subsystem — question bank, question types,
options, attempt limits, timing, auto-scoring, review and release of results — roughly
five more tables and its own controller. Building a shallow version alongside everything
above would have meant testing none of it properly, and a quiz that scores incorrectly is
worse than a quiz that does not exist yet.

What is here instead is the rest of the chain, tested: content authoring, delivery,
progress, materials, assignments, grading, results, completion and certificates. The
`assessments` entitlement already exists in the feature registry from Phase 6 and is
currently granted but unused — it gets its `requireFeature()` call when the engine lands.

## 8. Known gaps and open questions

1. **Instructors cannot edit course content.** 03-rbac.md §5 grades
   `education.course.update` as `◐` — "assigned courses" — but assignment of an
   instructor to a *course* is not modelled (they are assigned to class groups). The
   safer half was granted: instructors read content and grade their own students, but
   cannot edit the syllabus. Modelling instructor↔course assignment would close this.
2. **No lesson ordering UI.** Modules and lessons order by `sort_order`, which is set on
   creation but cannot be changed without SQL. Drag-to-reorder is a small, separate job.
3. **Programme-level certificates** are modelled (`certificates.programme_id`) but only
   course certificates are issued automatically. Whole-programme completion needs a rule
   about whether every course must be complete or only required ones.
4. **Video is a link or a file, not a player.** `content_type='video'` and MP4 materials
   are stored and served, but there is no streaming/player integration.
5. `courses.estimated_minutes` is a cache recomputed on every lesson change. If lessons
   ever get edited outside `CourseController`, that cache needs the same call.

## 9. Demo state

A fresh `migrate` + `seed` gives **Web Development Foundations** — 3 modules, 6 lessons,
125 minutes, one published assignment — linked to the Web Development programme, with the
first lesson marked as a free preview. `blessing.eze@ultrademy.com` (Premium, enrolled)
can complete it; `kelvin.musa@ultrademy.com` (enrolled, no subscription) hits the paywall,
so both paths are reachable without setup.
