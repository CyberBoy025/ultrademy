# UltrAdemy Design System

The single source of truth for colour, type, spacing and elevation. Every component
reads from these tokens. Nothing hard-codes a hex value.

### Surfaces

Two stylesheets declare these tokens, one per surface:

| Stylesheet | Surface |
|---|---|
| `public/css/site.css` | the public website **and the careers portal** |
| `public/css/shell.css` | the authenticated app (LMS, admin, recruitment back office) |

`public/careers/css/careers.css` is **not** a third system — it is a component add-on
loaded after `site.css`, declaring no custom properties of its own. See
`docs/architecture/16-careers-portal.md` §1.1 for why careers shares the public site's
tokens rather than having its own.

---

## 1. Core palette

| Role | Name | HEX |
|---|---|---|
| Primary | Cyan | `#22C7E3` |
| Secondary | Magenta | `#FF00FF` |
| Dark base | Black | `#000000` |
| Light base | White | `#FFFFFF` |

Cyan and magenta together are the identity. Neither is decoration — cyan carries
navigation, progress and primary action; magenta carries achievement, highlight and
secondary data.

### Cyan ramp

| Token | HEX | Use |
|---|---|---|
| `--cyan-50` | `#EDFBFD` | page tints, soft badge fill |
| `--cyan-100` | `#D2F4F9` | hover fill on light surfaces |
| `--cyan-200` | `#A9E9F3` | progress track, chart fill (light) |
| `--cyan-300` | `#6FDAEB` | secondary chart series |
| `--cyan-500` | `#22C7E3` | **primary** — buttons, active nav, progress |
| `--cyan-600` | `#0FA6C0` | hover on primary, text on white |
| `--cyan-700` | `#0C8499` | pressed, links on light bg |
| `--cyan-800` | `#0A6675` | dark-mode surfaces, deep accents |

### Magenta ramp

| Token | HEX | Use |
|---|---|---|
| `--magenta-50` | `#FFEBFF` | soft badge fill |
| `--magenta-100` | `#FFD1FF` | hover fill |
| `--magenta-200` | `#FFA6FF` | chart fill (light) |
| `--magenta-300` | `#FF6BFF` | secondary chart series |
| `--magenta-500` | `#FF00FF` | **secondary** — fills, badges, gradient end |
| `--magenta-600` | `#D400D4` | hover; **minimum for magenta text on white** |
| `--magenta-700` | `#A600A6` | pressed, accessible magenta body text |
| `--magenta-800` | `#7A007A` | dark accents |

> **Contrast note.** `#FF00FF` on white is ~3.0:1 — it passes for large text and UI
> borders but **fails WCAG AA for body text**. Pure magenta is for *fills* (badges,
> chart segments, gradient stops, filled buttons with white text). Magenta *text* on a
> light surface uses `--magenta-600` or darker. Same discipline for cyan: `#22C7E3` on
> white is ~1.9:1, so cyan text uses `--cyan-600`/`--cyan-700`; `#22C7E3` is a fill
> colour with black or very dark text on top.

### Logo

Two files in `public/img/`, both 1080×210 transparent PNG (5.14:1):

| File | Wordmark ink | Use on |
|---|---|---|
| `black-logo.png` | black | light surfaces |
| `white-logo.png` | white | dark surfaces |

The mark and the "Ultr" half of the wordmark are cyan in **both** files; only "Ademy"
changes. Two consequences worth knowing before placing it:

- **It is swapped by `[data-theme]`, not by `prefers-color-scheme`** — same mechanism as
  every token here. Ship both `<img>` tags with `.brand-logo.on-light` / `.on-dark`; the
  pre-paint script in each page's `<head>` settles which shows before first paint. Put
  the accessible name on the enclosing `<a>` and `alt=""` on both images, so a screen
  reader announces the brand once.
- **Never place it on cyan.** The brand gradient runs 135°, so its top-left corner — the
  usual home for a logo — is `--cyan-500`, and the cyan mark and "Ultr" disappear into
  it. On a permanently dark panel use `white-logo.png` alone with neither modifier, and
  keep the area behind it dark: see `.auth-brand-pane` in `public/careers/css/careers.css`
  (dark ground, brand washed in from the far corners) and `.auth-side::before` in
  `public/css/shell.css` (a corner scrim over the gradient).

Rendered height is 32px in headers and footers. The source files are ~7× that, which is
deliberate retina headroom.

---

## 2. Neutrals (light theme)

```
--color-background      #F7F8FA
--color-surface         #FFFFFF
--color-surface-muted   #F1F3F5
--color-border          #E5E7EB
--color-text-primary    #000000
--color-text-secondary  #4B5563
--color-text-muted      #6B7280
```

The interface is predominantly white and light. Brand colour is applied in small,
deliberate quantities.

## 3. Neutrals (dark theme)

Dark mode is a token swap, not a separate stylesheet. Applied via `data-theme="dark"`
on `<html>`.

```
--color-background      #0B0F14
--color-surface         #141A21
--color-surface-muted   #1C242D
--color-border          #2A343F
--color-text-primary    #FFFFFF
--color-text-secondary  #B4BECA
--color-text-muted      #8794A3
```

Brand behaviour in dark mode:

- Cyan `#22C7E3` on `#141A21` is ~8.4:1 — safe for text *and* fills.
- Magenta `#FF00FF` on `#141A21` is ~5.1:1 — safe for text and fills.
- So the ramps invert: dark mode uses the **500** values directly where light mode
  needed 600/700.
- Surfaces never become pure black; `#0B0F14` keeps elevation legible.

### Theme switching

- A toggle in the sidebar footer (sun/moon) sets `data-theme` on `<html>`.
- Choice persists in `localStorage` under `ultrademy.theme`.
- Default is `system`, following `prefers-color-scheme`.
- An inline script in `<head>` applies the stored theme *before* first paint to avoid
  a white flash.
- The toggle is a real `<button>` with `aria-pressed` and a text label for screen
  readers.

---

## 4. Typography

```
--font-primary:   "Neulis Alt", <fallbacks>;
--font-secondary: "Neue Helvetica", <fallbacks>;
```

**Neulis Alt** — headings, navigation, buttons, course titles, hero copy, metrics.
Sets the personality: modern, friendly, digital, premium.

**Neue Helvetica** — body, descriptions, metadata, captions, helper text, chart labels.

The two never mix arbitrarily. If it is structural or a call to action, it is Neulis
Alt. If it is explanatory, it is Neue Helvetica.

> **Licensing.** Both are commercial. Neulis Alt is licensed through Adobe Fonts /
> MyFonts; Neue Helvetica through Monotype. Neither can be pulled from a free CDN.
> Web font files must be self-hosted in `public/fonts/` under a valid webfont licence.
> Until those files exist, `--font-primary` and `--font-secondary` resolve to the
> fallback chain — see `docs/UI-REFERENCE.md` §Fonts for the agreed substitutes.

### Scale

| Token | Font | Size / Line | Weight | Use |
|---|---|---|---|---|
| `display` | Primary | 40 / 46 | 700 | hero greeting |
| `h1` | Primary | 30 / 38 | 700 | page titles |
| `h2` | Primary | 22 / 30 | 600 | section headings |
| `h3` | Primary | 18 / 26 | 600 | card titles |
| `h4` | Primary | 16 / 24 | 600 | sub-blocks |
| `metric` | Primary | 32 / 36 | 700 | big numbers in charts |
| `body-lg` | Secondary | 16 / 26 | 400 | lead paragraphs |
| `body` | Secondary | 14 / 22 | 400 | default |
| `body-sm` | Secondary | 13 / 20 | 400 | dense supporting text |
| `caption` | Secondary | 12 / 16 | 400 | metadata, dates, axis labels |
| `label` | Secondary | 12 / 16 | 500 | form labels, small caps tags |
| `button` | Primary | 14 / 20 | 600 | all buttons |
| `nav` | Primary | 14 / 20 | 500 | sidebar items |

---

## 5. Spacing

4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64

Derived from the reference: card internal padding is 20–24, gaps between cards 16–20,
gaps between major sections 24–32.

## 6. Radius

| Token | Value | Use |
|---|---|---|
| `--radius-sm` | 8px | inputs, tags, badges, small controls |
| `--radius-md` | 14px | cards, dropdowns, standard controls |
| `--radius-lg` | 20px | large panels, promo card |
| `--radius-xl` | 26px | hero banner |
| `--radius-full` | 999px | pills, avatars, progress bars |

## 7. Elevation

| Token | Value |
|---|---|
| `--shadow-none` | none |
| `--shadow-sm` | `0 1px 2px rgba(16,24,40,.04)` |
| `--shadow-md` | `0 2px 8px rgba(16,24,40,.06)` |
| `--shadow-lg` | `0 8px 24px rgba(16,24,40,.08)` |

Most cards use `--shadow-sm` plus a 1px `--color-border`. Heavy floating shadows are
not part of this system. In dark mode shadows are near-invisible; separation comes
from `--color-border` and surface lightness instead.

## 8. Semantic colours

Kept deliberately outside the brand ramps so status never competes with identity.

```
--color-success  #12B76A
--color-warning  #F79009
--color-error    #F04438
--color-info     #22C7E3   /* info reuses cyan intentionally */
```

Status is never communicated by colour alone — always paired with an icon or text.

## 9. Gradients

Permitted: cyan → magenta, or tints derived from the two. Used sparingly — the hero
banner, the promo card, and occasional chart fills. Nothing else.

```
--gradient-brand: linear-gradient(135deg, #22C7E3 0%, #FF00FF 100%);
--gradient-brand-soft: linear-gradient(135deg, #22C7E3 0%, #A855C7 55%, #FF00FF 100%);
```

The soft variant adds a mid-stop because a direct cyan→magenta interpolation passes
through a muddy grey-violet in sRGB. Interpolating in Oklab avoids this where browser
support allows:

```
linear-gradient(in oklab, #22C7E3, #FF00FF)
```
