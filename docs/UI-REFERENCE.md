# Reference Dashboard — Layout Analysis

Decomposition of the supplied reference screenshot. This document is the structural
source of truth; `DESIGN-SYSTEM.md` is the visual identity source of truth. Structure
comes from here, colour and type come from there.

---

## 1. Overall composition

Three fixed regions across a light page background, edge-padded, no full-bleed content.

```
┌──────────┬──────────────────────────────┬──────────────────┐
│          │  HERO BANNER                 │  Calendar        │
│ SIDEBAR  ├──────────────────────────────┤                  │
│          │  New Courses  (3 cards)      ├──────────────────┤
│ profile  ├──────────────────────────────┤  My Schedule     │
│ nav      │  Course Statistics           │                  │
│ rank     │  ┌────────────┬────────────┐ │                  │
│ achieve  │  │ Overview   │ Word Usage │ ├──────────────────┤
│          │  ├────────────┼────────────┤ │  Achievements &  │
│ promo    │  │ Hours/Week │ Fluency    │ │  Improvement     │
└──────────┴──────────────────────────────┴──────────────────┘
```

Measured from the reference and normalised to a 1440px desktop viewport:

| Region | Reference ratio | Target width |
|---|---|---|
| Sidebar | ~19.5% | **260px** fixed |
| Main content | ~50.5% | fluid, `1fr` |
| Right panel | ~30% | **380px** fixed |

- Page gutter: 24px
- Gap between the three regions: 20px
- Sidebar and right panel are independently scrollable; the page itself does not
  scroll horizontally at any breakpoint.

Grid implementation: `grid-template-columns: 260px minmax(0,1fr) 380px`.

---

## 2. Sidebar (left, 260px)

Top to bottom:

1. **User profile** — 36px circular avatar, name (`nav` token, 600), status line
   ("Paid Member") in `caption` muted, chevron on the right. Whole row is a button.
2. **Primary nav** — Dashboard, My Courses, Classroom, Interactive Modules, Settings.
   - 20px icon, 12px icon→label gap, 40px row height, 10px vertical padding.
   - "My Courses" carries a count badge (`2`) right-aligned.
   - Active item in the reference is a filled dark pill spanning the sidebar width.
3. **Divider** — 1px border, 16px margin block.
4. **Your Rank** — icon + label + value ("Gold" with a medal glyph).
5. **Your Achievement** — label, then an avatar grid: 4 columns × 2 rows, ~28px
   circles, 8px gap.
6. **Promo card** (pinned to the bottom) — rounded `--radius-lg`, gradient fill,
   credit counter in a circular chip, "Upgrade to Pro" as the heading, "Get 1 Month
   Free!" as a small link, rocket illustration bottom-right.

### UltrAdemy adaptation

- Active nav: cyan-tinted fill (`--cyan-50` light / `--cyan-800` dark) + `#22C7E3`
  icon + a 3px cyan left indicator. Not a black pill — the reference's dark pill reads
  as that product's identity, and cyan is ours. Label goes 600 weight so the active
  state is not conveyed by colour alone.
- Rank/achievement block uses magenta as the accent.
- Promo card uses `--gradient-brand`.
- Theme toggle sits directly above the promo card.

---

## 3. Hero banner

- Spans the full main-content width, `--radius-xl`, ~250px tall at 1440px.
- Left half: greeting on two lines (`display` token, white), email beneath in
  `body-sm` at ~80% opacity, then a pill CTA ("Continue Learning").
- Right half: photograph bleeding to the card's right and bottom edges, masked by the
  card radius.
- Internal padding 32px; text block is vertically centred.

**UltrAdemy adaptation:** background becomes `--gradient-brand-soft` (cyan → magenta,
135°). CTA is a white pill with `--cyan-700` label — white-on-gradient gives the
strongest contrast and keeps the button unmistakably the primary action. Greeting text
white. The photograph is a content slot; a neutral placeholder ships until real imagery
exists.

---

## 4. New Courses

Section header (`h2`) with 20px space beneath, then a 3-column grid, 16px gap, equal
heights.

Each card (`--radius-md`, surface, 1px border, `--shadow-sm`, 16px padding):

- 44px rounded-square icon tile with a soft tinted background
- Progress label above the title in `caption` muted — "2/8 Watched"
- Course title in `h4`

Tiles alternate cyan and magenta tints across the row (`--cyan-50`, `--magenta-50`,
`--cyan-50`) so the row reads as one family rather than three unrelated colours.
Hover: border shifts to `--cyan-300`, card lifts to `--shadow-md`.

---

## 5. Course Statistics

Section header, then two rows.

### Row 1 — split ~55 / 45, 16px gap

**Course Overview** (left)
- Card header: title `h3` + a dropdown filter pill on the right ("Spoken English")
- Course name in `h3`, one line of supporting `body-sm` muted
- Large percentage in `metric` with "(completed)" in `caption` alongside
- Full-width progress bar, `--radius-full`, 8px tall
- Track `--cyan-100`, fill `#22C7E3`. Percentage is stated numerically as well as
  shown, so the bar is never the only signal.

**Word Usage** (right)
- Donut chart, ~140px, thick ring, rounded caps
- Centre: big number (`metric`) + unit word beneath in `caption`
- Legend below in one row: three dot+label pairs
- Segments: `#22C7E3`, `#FF00FF`, `--color-border` (grammar / idiom / vocabulary)

### Row 2 — split ~50 / 50, 16px gap

**Hours Spend Each Week**
- Header: title `h3` + "This Week" dropdown
- Vertical bar chart, 7 bars, rounded tops, ~14px wide
- Y axis 2h–8h in `caption` muted, horizontal gridlines at `--color-border`
- Bars `#22C7E3`; the current day's bar `#FF00FF` to mark it without a legend

**Language Fluency Score**
- Two-series line chart with a legend of two dot+label pairs at the top
- Smooth curves, no area fill, one highlighted point with a dark tooltip showing the
  value
- Series 1 `#22C7E3`, series 2 `#FF00FF`; series are also distinguished by line
  style (solid / dashed) so the chart survives greyscale and colour-blindness

---

## 6. Right panel (380px)

### Calendar card
- Header `h3` ("Calendar")
- Month row: `‹` , "November 2023", `›` — arrows are 32px icon buttons
- Weekday initials in `caption` muted, 7-column grid
- Date cells ~36px square, centred `body-sm`
- Selected date: filled `#22C7E3` circle, white numeral, plus a bold weight so
  selection is not colour-only
- Today (unselected): 1px cyan ring
- Outside-month dates at `--color-text-muted`

### My Schedule
- Header `h3`, then the selected date in `body-sm` with a chevron
- "All Days" filter label
- Vertical timeline: hour labels in `caption` down the left, 1px rule per hour
- Event block spans its duration, `--radius-md`, `--magenta-50` fill with a 3px
  `#FF00FF` left bar
- Inside: a small category badge, event title in `h4`, one line of `body-sm` muted

### Achievements and Areas For Improvement
- Card header `h3` (wraps to two lines)
- Two circular gauges side by side, ~90px
- Each: ring, centred percentage in `metric`, caption label beneath
- "Your Goals" ring `#22C7E3`; "Improvement" ring `#FF00FF`; remainder
  `--color-surface-muted`

---

## 7. Fonts — interim plan

Neulis Alt and Neue Helvetica are both commercial and cannot be loaded from a free
CDN. Until licensed webfont files are placed in `public/fonts/`, the stacks resolve to:

```css
--font-primary:  "Neulis Alt", "Outfit", "Poppins", system-ui, sans-serif;
--font-secondary:"Neue Helvetica", "Helvetica Neue", "Inter", Helvetica, Arial,
                 system-ui, sans-serif;
```

`Outfit` is the closest free geometric humanist sans to Neulis Alt's proportions;
`Inter` stands in for Neue Helvetica's neutral grotesque. Dropping the licensed
`.woff2` files into `public/fonts/` and uncommenting the `@font-face` block switches
the whole UI over with no other change — that is the point of routing everything
through two tokens.

---

## 8. Responsive strategy

| Breakpoint | Behaviour |
|---|---|
| `≥1280px` | Full three-region grid as above. |
| `1024–1279px` | Sidebar 220px, right panel 320px, card padding drops one step, hero 210px. |
| `768–1023px` | Right panel moves **below** main content as a 2-column row (calendar \| schedule), achievements full width. Sidebar collapses to a 72px icon rail with tooltips. |
| `<768px` | Sidebar becomes an off-canvas drawer behind a header hamburger. Everything stacks to one column. Course cards stack. Charts keep full width and a fixed aspect ratio. Calendar stays a 7-column grid with smaller cells. Schedule keeps its timeline. |

No horizontal overflow at any width. The desktop composition is never sacrificed to
make the mobile case simpler.

---

## 9. Component inventory

To build:

`AppShell` · `Sidebar` · `UserProfile` · `NavItem` · `RankBlock` · `AchievementAvatars`
· `PromoCard` · `ThemeToggle` · `WelcomeBanner` · `SectionHeader` · `CourseCard`
· `CourseOverviewCard` · `ProgressBar` · `DonutChart` · `BarChart` · `LineChart`
· `Calendar` · `Schedule` · `ScheduleItem` · `GaugeCard` · `Dropdown` · `Badge`
· `Avatar` · `IconButton` · `Card`

Existing to preserve: `config/bootstrap.php` (env + config helper) and the front
controller entry point. No backend logic changes are required for any of the above.
