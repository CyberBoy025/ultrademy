# Ultrademy

Learning platform — built step by step.

## Status

Early scaffold. No features implemented yet.

## Stack

- PHP (procedural/MVC-lite, no framework for now)
- MySQL
- Plain HTML/CSS/JS frontend
- Runs under XAMPP (`htdocs/ultra`) once we get to local hosting

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
  css/ js/ uploads/
docs/            notes and design decisions
```

## Local setup (later)

1. Copy `.env.example` to `.env` and fill in the database credentials.
2. Point XAMPP's document root at `public/`, or drop the project in `htdocs/`.
3. Import the migrations in `database/migrations/` in filename order.

## Development

Work happens in a cloud session and is pushed to this repo directly.
