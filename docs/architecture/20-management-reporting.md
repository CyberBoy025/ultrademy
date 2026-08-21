# 20 — Management & Reporting

Phase 12. README §32 (manager visibility), §38 (role-specific dashboards) and §16
(Gwagwalada vs Kubwa vs all centres).

Implemented in migration 096, `app/models/ManagementReport.php`,
`app/controllers/ManagementController.php` and `app/views/management/`.

---

## 1. Scope, not a second access model

Every query obeys `Auth::scopeCentres('management.report.view')` — the same resolver the
rest of the system uses. A centre manager running "students by centre" gets their centre.

This is the single most important thing about a reporting module. Reporting tools leak
data because they are built as a separate surface with their own idea of who may see
what, and then a filter dropdown quietly hands someone another centre's numbers. Here
there is no separate idea: scope comes from the same place as it does for the invoice
list.

`ManagementController::centres()` (the §16 comparison) is explicitly refused to a
centre-scoped viewer and redirects them to their own dashboard. Comparing centres means
reading another centre's operational data, which is §42's exact prohibition.

`management.report.view` is deliberately distinct from `finance.report.view`. The latter
is about money and already existed. This one is the operational picture.

---

## 2. What is reported

| Surface | Contents |
|---|---|
| **Overview** | active students, new enrolments, applications, revenue/expenses/net/outstanding · 12-month registration and enrolment trend · admissions funnel with conversion · revenue and expenses by centre · affiliate and donation rollup · students below 70% attendance |
| **Centre comparison** | students, new enrolments, rooms, attendance, revenue, expenses, net — per centre, with online as its own row |
| **Academic** | enrolments and completion by programme · assessment averages and pass rates by course · instructor load · at-risk students |

CSV export on centres, programmes, instructors and at-risk. **Exports are audited** —
a full export of student names and attendance is a data-protection event, and §38
requires it to be recorded.

---

## 3. Three places the numbers could lie, and don't

**`null` attendance is not `0%`.** An online cohort has no register to take. Reporting
0% there would read as "nobody turned up" and send a manager after a problem that does
not exist. `attendanceRate()` returns `null`, and the table prints "n/a".

**Empty months are filled with zeros, not omitted.** A trend line that skips a quiet
month draws a straight line through it and reports growth that never happened.
`monthlyGrowth()` pre-seeds every bucket.

**The funnel cannot invert.** `approved` counts applications currently approved **or**
already enrolled — an application that progressed past approval was still approved. Left
naive, the "enrolled" stage would exceed "approved" and the funnel would draw upside
down.

Also: at-risk students need at least three marked sessions before they appear, so one
missed class does not flag someone.

---

## 4. Charts

Server-rendered inline SVG. No charting library — nothing to load, nothing to fail, and
the markup is the same on every browser.

Every chart ships with a **table view** (either a `<details>` panel or the full table
below it), so the data is never available only as a picture. Every mark carries a
`<title>`, which is a native hover tooltip with no JavaScript.

### Palette

| Slot | Light | Dark | Role |
|---|---|---|---|
| 1 | `#0891B2` | `#17A3BC` | cyan — the brand hue |
| 2 | `#B45309` | `#A9690D` | amber |
| 3 | `#C026D3` | `#C048C0` | magenta |

**The amber sits between the two brand hues on purpose.** Cyan and magenta are hard to
separate under deuteranopia — adjacent ΔE 6.5, inside the "legal only with secondary
encoding" band. Placing a third hue between them raises worst-adjacent separation to
ΔE 20. Both sets were checked against the lightness band, chroma floor, CVD separation,
normal-vision floor and surface contrast, and pass all six.

Dark mode is a **separate selection**, not a flip of the light values: the light steps
fall outside the dark lightness band entirely.

Colour is assigned by slot index in fixed order and never cycled into a generated hue.
A filter that changes the series count does not repaint the survivors.

### Marks

4px rounded bar ends anchored to the baseline; a 2px surface gap between adjacent bars;
2px lines with a 2px surface ring on each point so overlapping series stay legible;
recessive gridlines; a legend whenever there are two or more series and none when there
is one, because the card title already names it.

### What looking at it caught

The palette validator checks colour, not layout. Rendering the charts headless and
screenshotting them surfaced two things no validator would have:

- **No y-axis values at all** — gridlines with no numbers tell you the shape of the data
  but not its size. A 54px left gutter and compact ticks (`1.2M`, `12k`) were added.
- **`7.5` as an axis ceiling**, which divides into quarter-ticks of 1.875. The step list
  now omits 3 and 7.5 so every ceiling divides into four clean gridlines.

---

## 5. Tests

`tests/ChartHelpersTest.php` — 9 assertions on the axis maths and the safety properties:
the ceiling never falls below the data, an empty period still yields a usable axis,
labels are escaped before reaching SVG text nodes, and a chart with no rows degrades to
a message rather than empty axes.

Not covered without a database: every query in `ManagementReport`. The scope behaviour in
particular — that a Gwagwalada manager cannot see Kubwa figures — is exactly the §42
control Phase 14 still owes a test for, and it now has one more surface to cover.

---

## 6. Not built

| Feature | Note |
|---|---|
| PDF export | CSV only. PDF needs a renderer; the pdf skill would do it |
| Scheduled email reports | "Send me Monday's numbers" — needs the notification digest plus a cron |
| Cohort retention curves | Real analysis, needs more history than the system has |
| Instructor quality metrics | Deliberately omitted — grade averages as a teaching metric is a decision with consequences, not a chart |
| Saved report views | Filters are URL parameters today, which are at least shareable |
| Aggregate tables | Reports read the transactional tables. Correct at this scale, wrong later — see 06-api-notifications.md §3 |

| # | Decision | Default taken |
|---|---|---|
| 36 | At-risk attendance threshold | 70%, minimum 3 marked sessions |
| 37 | Default reporting period | Last 90 days |
| 38 | May a centre manager see the centre comparison? | No — their own centre only |
