# 17 — Assessments

Phase 8 completion. Covers README §18 (quizzes, examinations, assessments, results),
§19 (course outline) and §20 (take quizzes, complete assessments, view results).

Implemented in migrations 081–086, `app/models/Assessment.php`,
`app/controllers/AssessmentController.php` and `app/views/assessments/`.

---

## 1. Why this is not part of `assignments`

They look similar and are not.

| | Assignment | Assessment |
|---|---|---|
| Shape | one piece of submitted work | a set of questions |
| Marking | always a human | mostly automatic |
| Timing | a due date | an open/close window and a clock |
| Attempts | one, resubmission optional | a configured limit |
| Outcome | a score | a score **and** pass/fail against a mark |

Folding them together would mean either assignments carry timing and attempt columns
that never apply, or assessments lose them. Two tables, one shared idea.

---

## 2. Schema

```
assessments
    └── assessment_questions            (points per question)
            └── assessment_options      (choice questions only; is_correct lives here)

assessment_attempts                     (one row per sitting)
    └── assessment_answers              (one row per question per attempt)
```

**The maximum mark is never stored on the assessment.** It is `SUM(questions.points)`,
computed on read. A cached total goes stale the moment a question is added, and a stale
total silently produces wrong percentages.

**`assessment_attempts.max_points` is the exception, and deliberately so.** It is
snapshotted at submission. If an instructor edits the paper afterwards, an already-graded
attempt keeps the mark it was actually awarded out of the total that actually applied.
Recomputing from the live questions would quietly rewrite history.

**`selected_options` is a JSON array, not a join table.** An answer is always read and
written whole and never queried by individual option, so a join table would add a table
and a join for no query it enables.

---

## 3. Question types

| Type | Marked by | Rule |
|---|---|---|
| `single_choice` | server | exactly the one correct option |
| `multi_choice` | server | every correct option and no incorrect one |
| `true_false` | server | generated as a two-option single choice |
| `short_text` | server | case- and whitespace-insensitive match against `expected_answer`, `\|`-separated alternatives |
| `essay` | **human** | attempt waits in the marking queue |

`true_false` generating real options rather than being a special case means grading has
**one** code path for every choice question. Special cases in a marking routine are
where wrong grades come from.

### Choice questions are all-or-nothing

Partial credit is not implemented, and that is a decision rather than an omission.
Partial credit needs a scheme that is defensible before it is fair — proportional?
negative marking for wrong selections? — and guessing at one produces marks nobody can
justify to a student who queries them. If UltrAdemy wants it, the scheme gets agreed
first and `Assessment::markAnswer()` is the single place it lands.

---

## 4. Three rules the code exists to hold

**Correct answers never leave the server before grading.**
`questionsForTaking()` is the only query the take-page uses, and it does not select
`is_correct`, `expected_answer` or `explanation` — by construction, not by filtering
afterwards where a later edit could reintroduce them. The result page reveals the answer
key only once the mark itself is visible; otherwise a candidate with an attempt in hand
could read the answers off their first result.

**The clock is server-side.**
`started_at + duration_minutes` decides whether an attempt has expired. The countdown in
the browser is a courtesy. Someone who leaves the tab open past the deadline gets the
attempt closed and marked on their next request, not extra time.

**A provisional mark is worse than no mark.**
An attempt containing an unmarked essay stays `submitted` with `needs_manual_grade = 1`
and shows the candidate nothing. Showing "42%" that later becomes "78%" misinforms
rather than informs.

---

## 5. Lifecycle

```
 in_progress ──submit──▶ submitted ──all auto-marked──▶ graded
      │                      │
      │                      └──contains essay──▶ marking queue ──marks saved──▶ graded
      │
      └──clock runs out──▶ finalised on next request
```

`startAttempt()` runs inside a transaction. Two tabs starting at once would otherwise
both read the same attempt count, both insert `attempt_no = 2`, and the unique key would
hand the loser a raw SQL error. Taking the lock means the second call simply resumes the
first one's attempt.

A late submission is still recorded rather than discarded — silently binning a
candidate's work because the clock ran out mid-save is the worse failure, and
`time_spent_seconds` preserves the evidence for whoever reviews it.

---

## 6. Access control

Two gates, as everywhere else in this system (03-rbac.md §6):

| Actor | Gate | Failure |
|---|---|---|
| Candidate — enrolment | `Learning::requireCourseAccess()` | 403 |
| Candidate — package | `Entitlements::requireFeature('assessments')` | **402** |
| Author | `education.assessment.manage` | 403 |
| Marker | `education.assessment.grade` | 403 |
| Results viewer | `education.assessment.results` | 403 |

`education.assessment.grade` is deliberately separate from
`education.assignment.grade`. They are different acts, and an organisation may want a
senior examiner marking papers without also handing them every assignment submission.
Both are granted to instructors by default, so nothing changes operationally unless
someone chooses to split them.

The `assessments` feature was already granted by the Premium and Advanced packages
before any of this existed — it now delivers something.

---

## 7. Guards worth knowing about

- **Publishing is refused** for an assessment with no questions, or one whose questions
  are all worth zero. Better to block it than let a student sit a paper that divides by
  zero.
- **Marks are clamped** on save: a marker cannot award 20 out of 5.
- **A blank mark box defers** rather than awarding zero — the attempt stays unmarked
  until every written answer has a decision.
- **A question with no correct option** never marks as correct, which stops an authoring
  slip from awarding marks to everyone who answered nothing.
- **Publishing notifies** everyone actively enrolled on a programme containing the
  course. Nobody should discover an exam by refreshing a page.
- **`show_results = after_close` with no closing date never releases.** There is no
  moment at which release would be correct, and defaulting to visible would leak every
  mark.

---

## 8. Tests

`tests/AssessmentMarkingTest.php` — 29 assertions, no database required, run with:

```
php tests/run.php
```

They cover every marking rule, the open/closed window, the expiry clock and result
visibility, including the cases that are easy to get wrong: duplicated option ids in a
posted form, a question with no correct option, `after_close` with no close date, and
a zero-mark question that is still *correct* even though it awards nothing.

This is the first test directory in the project. What it does **not** cover is the
database layer — attempt creation, the transaction, marking persistence — because
running those needs a MySQL instance. Those tests belong in Phase 14 alongside the
permission and centre-scope tests the brief asks for in §42.

---

## 9. Demo data

```
php database/migrate.php
php database/seed-assessments.php
```

Creates a published five-question quiz on the first course: single choice, multiple
choice, true/false, short answer with alternatives, and one essay — so the manual
marking path is exercised rather than merely present. Idempotent.

---

## 10. Not built

Named so they are decisions rather than gaps:

- **Question banks and random selection per candidate.** Meaningful anti-cheating for
  exams; needs a pool model and a sampling rule.
- **Partial credit.** See §3.
- **Per-question time limits.** The clock is per attempt.
- **Proctoring, lockdown, IP restriction.** A different problem with a different budget.
- **Import from CSV or QTI.** Worth having once someone is authoring at volume.
- **Certificates gated on passing an assessment.** Currently a certificate is earned by
  completing every lesson (`LearnController::toggleProgress`). Tying it to a pass mark is
  a small change and a real policy decision — see Decision 26 below.

| # | Decision | Default taken |
|---|---|---|
| 26 | Should a course certificate require passing its assessments, or only completing the lessons? | Lessons only — unchanged |
| 27 | Partial credit for multi-choice? | No |
| 28 | Should the best attempt or the last attempt count as the result? | Best — `bestPercent()` |
