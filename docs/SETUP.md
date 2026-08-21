# Running UltrAdemy locally on XAMPP

Step by step, from a clean machine to a working login.

> **Your current machine already has all of this done** — Apache 2.4.58, PHP 8.2.12,
> MariaDB 10.4.32, database created, 80 migrations applied, seed data loaded. Follow
> these steps for a *second* machine, a teammate's laptop, or a full reset.
> Skip to **Step 7** if you only want the URLs and logins.

---

## Step 1 — Install XAMPP

Download XAMPP with **PHP 8.2 or newer** from apachefriends.org and install to the
default `C:\xampp`.

Check the version afterwards. Open Command Prompt:

```cmd
C:\xampp\php\php.exe -v
```

Anything below PHP 8.0 will not run this project.

---

## Step 2 — Enable the PHP extensions

Open `C:\xampp\php\php.ini` in a text editor. Find each line below and **remove the
leading semicolon**:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=curl
extension=gd
extension=zip
extension=intl
```

The first five are required. `gd` (profile photos, certificates), `zip` and `intl` are
needed as the project grows.

> On the current machine `gd`, `zip` and `intl` are still commented out. Worth fixing
> now — `zip` becomes blocking the moment anything needs Composer.

Save, then restart Apache.

---

## Step 3 — Put the project in htdocs

The project must sit at:

```
C:\xampp\htdocs\ultra
```

From a fresh clone:

```cmd
cd C:\xampp\htdocs
git clone https://github.com/CyberBoy025/ultrademy.git ultra
```

The folder name matters — `.env` and every URL in this guide assume `ultra`.

---

## Step 4 — Start Apache and MySQL

Open the **XAMPP Control Panel** and click **Start** on:

- **Apache**
- **MySQL**

Both should turn green. If Apache refuses to start, something else is on port 80 —
usually IIS, Skype, or another web server. Change Apache's port in `httpd.conf`, or
stop the conflicting service.

Confirm Apache is up: **http://localhost/** should show the XAMPP dashboard.

---

## Step 5 — Create the database

Open **http://localhost/phpmyadmin**, then:

1. Click **New** in the left sidebar
2. Database name: `ultrademy`
3. Collation: `utf8mb4_general_ci`
4. Click **Create**

Or from the command line:

```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ultrademy CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

Leave it empty — the migrations build every table.

---

## Step 6 — Configure the environment

Copy the example file and edit it:

```cmd
cd C:\xampp\htdocs\ultra
copy .env.example .env
```

For a default XAMPP install the values are already correct:

```ini
APP_NAME=Ultrademy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/ultrademymain
CAREERS_URL=http://localhost/ultrademymain/careers

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ultrademy
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

If you set a MySQL root password during installation, put it in `DB_PASS`.

`.env` is gitignored and blocked by `.htaccess`. Never commit it.

---

## Step 7 — Run the migrations

From `C:\xampp\htdocs\ultra`:

```cmd
C:\xampp\php\php.exe database/migrate.php
```

You should see each file applied in order:

```
Applying 001_create_users.sql ... done
Applying 002_create_user_profiles.sql ... done
...
80 migration(s) applied.
```

Running it again is safe — it tracks what has already run in a `migrations` table and
prints `Nothing to migrate — up to date.`

If a migration fails it stops immediately and tells you which file. It is **not**
recorded as applied, but MySQL cannot roll back DDL, so check the schema before
re-running.

---

## Step 8 — Load the seed data

```cmd
C:\xampp\php\php.exe database/seed.php
```

This creates the centres, roles, permissions, packages, features, demo programmes,
demo users and sample records. It uses `INSERT IGNORE`, so running it twice will not
duplicate anything.

---

## Step 9 — Open it

| What | URL |
|---|---|
| Public website | http://localhost/ultrademymain/ |
| Login | http://localhost/ultrademymain/login.php |
| Register | http://localhost/ultrademymain/register.php |
| Platform (after login) | http://localhost/ultrademymain/app.php |
| Careers portal | http://localhost/ultrademymain/careers/ |
| phpMyAdmin | http://localhost/phpmyadmin |

**Start with the public website.** If it renders, PHP, the `.htaccess` rewrite and the
database connection are all working — the homepage reads programmes and centres out of
the database to build itself, so a blank or broken page means one of those three, in
that order.

Then run `php tests/run.php`, which exercises the money, gateway-signature, marking and
campaign logic without needing a browser.

---

## Step 10 — Log in

All seeded accounts share one password:

```
Password123!
```

| Email | Role | Use it to see |
|---|---|---|
| `super@ultrademy.com` | Super Administrator | everything |
| `chidi.nwosu@ultrademy.com` | Administrator | users, content, moderation |
| `sarah.bello@ultrademy.com` | Management | cross-centre overview |
| `manager.gwagwalada@ultrademy.com` | Centre Manager — Gwagwalada | centre scoping |
| `emeka.obi@ultrademy.com` | Centre Manager — Kubwa | the *other* centre |
| `ifeoma.chukwu@ultrademy.com` | Accountant | full finance |
| `tunde.bakare@ultrademy.com` | Cashier — Gwagwalada | the restricted finance view |
| `grace.adeyemi@ultrademy.com` | Instructor — Gwagwalada | classes, attendance, grading |
| `blessing.eze@ultrademy.com` | Student | learning, payments, chat |
| `kelvin.musa@ultrademy.com` | Student | second student |
| `ngozi.eze@ultrademy.com` | Recruitment Administrator | careers backend |
| `femi.okoro@ultrademy.com` | Recruiter — Gwagwalada | job postings, interviews |
| `bola.adeleke@ultrademy.com` | Reporting User | read-only reports |

**A worthwhile five-minute test of the architecture:** log in as
`manager.gwagwalada@ultrademy.com`, note which students and rooms you can see, then log
in as `emeka.obi@ultrademy.com` and confirm you see Kubwa's instead. Then log in as
`tunde.bakare@ultrademy.com` and confirm the bank-transfer verify queue is not
available. Those are the two controls §42 of the brief cares most about.

> These accounts are demo data with a shared, published password. Delete them before
> this system ever holds real people's records.

---

## Optional — scheduled jobs

Three cron scripts exist and are not wired to anything:

```cmd
C:\xampp\php\php.exe database/expire-subscriptions.php
C:\xampp\php\php.exe database/finance-cron.php
C:\xampp\php\php.exe database/notifications-cron.php
```

Run them by hand for now. In production these go on a scheduler — Windows Task
Scheduler locally, cron on a Linux host.

---

## Troubleshooting

**"Object not found" / 404 on every page**
Apache is not running, or the folder is not at `C:\xampp\htdocs\ultrademymain`. Check
the control panel and the path.

**Directory listing, or a 403, instead of the site**
The root `.htaccess` is not being read, so nothing is mapping `/ultrademymain/` onto
`public/`. Two usual causes: `AllowOverride None` for `htdocs` in `httpd.conf` (it must
be `AllowOverride All`), or `mod_rewrite` still commented out — check that
`LoadModule rewrite_module modules/mod_rewrite.so` is uncommented, then restart Apache.
`http://localhost/ultrademymain/public/` still works meanwhile, so this is cosmetic
rather than blocking.

**Blank white page**
A PHP fatal error. Set `APP_DEBUG=true` in `.env`, reload, and read the message. If it
is still blank, check `C:\xampp\apache\logs\error.log`.

**"Cannot connect" on the health check**
MySQL is not started, or `DB_PASS` in `.env` does not match your MySQL root password.

**"could not find driver"**
`extension=pdo_mysql` is still commented out in `php.ini`. Uncomment and restart
Apache.

**`php` is not recognised as a command**
Windows does not have PHP on the PATH. Use the full path
`C:\xampp\php\php.exe`, or add `C:\xampp\php` to your system PATH.

**Migration fails partway**
The error names the file. Fix the SQL, check whether part of it applied, then re-run
`migrate.php` — it resumes from where it stopped.

**Changes to `.env` seem ignored**
Restart Apache. Some setups cache the environment.

---

## Starting completely over

Drop and rebuild:

```cmd
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE ultrademy; CREATE DATABASE ultrademy CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
cd C:\xampp\htdocs\ultra
C:\xampp\php\php.exe database/migrate.php
C:\xampp\php\php.exe database/seed.php
```

Uploaded files live in `storage/app/` and are not touched by this — clear that folder
too if you want a genuinely clean state.

---

## Before this is ever internet-reachable

Not optional:

1. Delete any diagnostic endpoint added during development — anything that reports
   PHP, schema or server detail belongs on a laptop, not the internet.
2. Delete every seeded demo account, or change the passwords.
3. Set `APP_DEBUG=false`.
4. Give MySQL's root user a password, or better, create a dedicated database user with
   only the privileges this app needs.
5. Point the web server's document root at `.../ultrademymain/public` rather than
   relying on `.htaccess` to hide the rest. The rewrite that shortens the URL locally
   becomes unnecessary then — the short URL is simply what the vhost serves.
