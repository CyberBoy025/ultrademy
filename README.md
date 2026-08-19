# Ultrademy

EdTech learning platform — student dashboard first.

## Status

Scaffold in place. UI design system and layout analysis documented. Implementation
not started.

## Stack

- PHP (MVC-lite, no framework for now)
- MySQL
- Plain HTML/CSS/JS frontend — no build step
- Runs under XAMPP (`htdocs/ultra`)

## Brand

| Role | Colour |
|---|---|
| Primary | Cyan `#22C7E3` |
| Secondary | Magenta `#FF00FF` |
| Dark base | Black `#000000` |
| Light base | White `#FFFFFF` |

Typography: **Neulis Alt** (primary — headings, nav, buttons) and **Neue Helvetica**
(secondary — body, metadata). Both commercial; see `docs/UI-REFERENCE.md` §7 for the
interim fallback stack.

Light and dark themes are both first-class. Theme is a token swap driven by
`data-theme` on `<html>`, toggled from the sidebar and persisted to `localStorage`.

## Docs

- `docs/DESIGN-SYSTEM.md` — colour ramps, typography scale, spacing, radius,
  elevation, dark theme, gradients
- `docs/UI-REFERENCE.md` — reference dashboard decomposition, grid, component
  inventory, responsive strategy

## Layout

```
app/
  controllers/   request handlers
  models/        database access
  views/         page templates
config/          app + database configuration
database/
  migrations/    schema changes, in order
  seeds/         sample data
public/          web root — the only folder the browser should reach
  css/ js/ fonts/ uploads/
docs/            design system and notes
```

## Local setup

1. Copy `.env.example` to `.env` and fill in the database credentials.
2. Visit `http://localhost/ultra/public`.
3. Import the migrations in `database/migrations/` in filename order.

## Pushing changes

From `C:\xampp\htdocs\ultra` in PowerShell:

```powershell
.\push.bat "what changed"
```

Stages, commits and pushes to `origin/main`.
