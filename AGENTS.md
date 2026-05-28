# Agent guide — MiniPHP

This document is for humans and coding agents working in this repo. It summarizes how to run the app, how database migrations work, where security lives, and the conventions you must follow when adding routes, forms, views, or scripts.

## Stack and layout

- **PHP 8.2+** with PDO SQLite. No runtime Composer packages; PSR-4 autoloading via `framework/Autoloader.php` (optional `composer.json` for IDE tooling only).
- **Front controller:** `public/index.php` → `bootstrap/app.php` → `routes/web.php`.
- **MVC:** `app/Controllers`, `app/Models`, `app/Views`; core in `framework/`.
- **Config:** `config/app.php` (app, database, session, auth, security).
- **DB:** SQLite at `storage/database.sqlite`; versioned migrations in `database/migrations/`; applied on bootstrap via `database/migrate.php` or manually with `php bin/migrate.php`.

```
public/          Document root (only web-exposed tree)
bootstrap/       App bootstrap + session start
config/          Configuration
routes/          Route definitions
framework/       Router, Request, View, CSRF, Auth, security headers, tests
app/             Application code
database/        Migration runner + `migrations/*.php` (up only)
tests/           Unit + feature tests (see tests/README.md)
bin/migrate.php  Apply pending migrations
bin/test.php     Test runner entry
```

## How to build and verify

There is no asset bundler or compile step. “Building” means: PHP available, database migrated, tests green.

### Run locally (Windows cmd)

From the project root:

```cmd
php -S localhost:8000 -t public public/router.php
```

Open http://localhost:8000/. Guests are redirected to login; register or log in to use items CRUD.

**Apache:** point the vhost document root at `public/`; `public/.htaccess` rewrites to `index.php`.

**Schema changes:** see [Database migrations](#database-migrations) below.

### Run migrations

From the project root (uses `config/app.php` → `storage/database.sqlite`):

```cmd
php bin\migrate.php
```

Prints each applied filename, or `Nothing to migrate.` when the database is current. Safe to run repeatedly.

### Run tests

```cmd
php bin\test.php
php tests\run.php tests\Feature
```

Tests use in-memory SQLite (`config/testing.php`). Feature tests extend `Tests\Support\ApplicationTestCase` and dispatch through `App::handle()` without sending real headers.

### Request flow (for debugging)

1. `Request::capture()` in `App::run()`
2. **CSRF check** on every `POST` (if enabled)
3. Router → middleware → controller → `Response`
4. **Security headers** applied to every response
5. `Response::send()`

When adding behavior, prefer extending existing patterns in `ItemController`, `AuthController`, and `routes/web.php` rather than new abstractions.

---

## Database migrations

Schema is **versioned** and **up-only** (no `down` / rollback commands). Each change is a PHP file under `database/migrations/` that runs once per database and is recorded in a `migrations` table.

### Layout

| Piece | Role |
|--------|------|
| `database/migrations/*.php` | One file per schema change; filename is the migration identity |
| `framework/Migrations/Migrator.php` | Discovers files, runs pending `up` steps, records history |
| `database/migrate.php` | Thin wrapper: `Migrator::fromPath(...)->up()` on the **current** PDO connection |
| `bin/migrate.php` | CLI: load config, connect to file DB, run pending migrations |

There is no separate migration config: the database path comes from `config/app.php` (`database.path` → `storage/database.sqlite`).

### When migrations run

| Context | How |
|---------|-----|
| Web / local app | `bootstrap/app.php` creates `App` (which calls `Database::connect()`), then `require database/migrate.php` |
| Tests | `tests/bootstrap.php` and `InteractsWithDatabase::refreshDatabase()` connect first (in-memory SQLite via `config/testing.php`), then require `database/migrate.php` |
| Manual / deploy | `php bin\migrate.php` connects using `config/app.php` and migrates the file database |

**Important:** `database/migrate.php` does **not** load config or connect. It only uses `Database::pdo()`. The caller must connect first so tests stay on `:memory:` and the app uses the right path.

### Execution order and tracking

1. `Migrator` ensures the `migrations` table exists.
2. All `database/migrations/*.php` files are collected and sorted by **filename** (`SORT_STRING`). Use a zero-padded numeric prefix: `001_…`, `002_…`, `010_…`.
3. Any filename not yet in `migrations.migration` is pending.
4. Each pending file is `require`d; it **must** return a `callable(PDO $pdo): void`.
5. That callable runs inside a **transaction**; on success a row is inserted with the **basename** (e.g. `004_add_priority.php`).
6. Failed migrations roll back and throw `RuntimeException` with the file name.

`migrations` table (created automatically):

```sql
CREATE TABLE migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    applied_at TEXT NOT NULL DEFAULT (datetime('now'))
);
```

There is **no** `down` migration: to undo a change in development, restore a backup or delete `storage/database.sqlite` and re-run all migrations from scratch.

### Writing a migration file

Naming: `NNN_short_snake_case.php` where `NNN` sorts after existing files. Never rename a file after it has been applied on any environment—the stored name is the filename.

Template:

```php
<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS example (
    id INTEGER PRIMARY KEY AUTOINCREMENT
)
SQL);
};
```

Guidelines:

- **Return** a `static function (PDO $pdo): void { ... }` (or any callable accepting `PDO`). Do not run SQL at file load time outside the callable.
- Prefer **idempotent DDL** where SQLite allows it (`CREATE TABLE IF NOT EXISTS`). The migrator will not re-run the file once recorded, but idempotency helps local debugging if you reset the `migrations` table by mistake.
- Use `$pdo->exec()` for static DDL; use **prepared statements** if you ever bind values (unusual in migrations).
- For **additive** changes on databases that might predate a column, check `PRAGMA table_info(...)` and no-op if already applied (see `003_add_user_id_to_items.php`).
- Do **not** concatenate user input into SQL in migrations.
- Do **not** edit the body of a migration that has already shipped; add a new numbered file instead.

After adding a file locally:

```cmd
php bin\migrate.php
php bin\test.php
```

### Agent checklist (new schema change)

1. Add `database/migrations/NNN_description.php` with the callable pattern above.
2. Update models/controllers/views if the app must use the new columns/tables.
3. Run `php bin\migrate.php` on your dev database.
4. Run `php bin\test.php` (migrations run automatically in test bootstrap).
5. Commit the new migration file with application code in the same change when possible.

### Testing

- Feature/unit tests get a fresh in-memory DB per run; `database/migrate.php` applies every migration file that is not yet recorded (typically all of them on empty memory).
- `tests/Unit/MigratorTest.php` covers “run pending once, second `up()` is empty” using a temp directory of fake migration files.

To exercise migration logic in isolation, use `Migrator::fromPath($pdo, $path)` in a test after `Database::connect()`.

### Troubleshooting

| Problem | What to do |
|---------|------------|
| `Migration … failed` | Fix the SQL in the **new** file or add a follow-up migration; check CLI output for the wrapped exception message |
| Old/corrupt `storage/database.sqlite` | Stop the server, delete the file, run `php bin\migrate.php`, restart |
| Migration ran locally but not on server | Run `php bin\migrate.php` in deploy; ensure the new `.php` file is deployed |
| Renamed or deleted an applied migration file | Avoid—history still references the old name; restore the file or fix the `migrations` table manually |
| Column already exists | Use `PRAGMA table_info` guard or split into a safe additive migration |

### Do not

- Put one-off schema SQL only in `database/migrate.php`—add a file under `database/migrations/`.
- Change or delete applied migration files in git history without a deliberate DB repair plan.
- Call `database/migrate.php` before `Database::connect()`.
- Assume `bin/migrate.php` runs during tests (tests use `migrate.php` on the in-memory connection, not the CLI entry).

---

## Security model

Security is layered: global POST CSRF, automatic view escaping, HTTP security headers (including CSP), session-hardened auth, and per-user data scoping in models/controllers.

### Configuration (`config/app.php`)

```php
'security' => [
    'csrf' => true,      // validate all POST bodies
    'headers' => true,   // X-Frame-Options, CSP, etc.
    'csp' => "default-src 'self'; ...",  // no script-src → blocks inline JS
    'hsts' => false,     // enable only behind HTTPS in production
],
'session' => [
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => null,    // auto-detect HTTPS; set true in production
],
```

Toggle `security.csrf` or `security.headers` only in tests or local experiments—not in committed production config without review.

### CSRF (all POST requests)

Implementation: `framework/Csrf.php`, enforced in `framework/App.php` before routing.

| Piece | Detail |
|--------|--------|
| Field name | `_csrf` (`Csrf::FIELD`) |
| Storage | `$_SESSION['_csrf_token']` (`Session::TOKEN_KEY`) |
| Validation | `hash_equals` on submitted vs session token |
| Failure | **403** HTML: `Invalid or missing CSRF token.` |

Token is created on first `Csrf::token()` call per session. Use `Csrf::regenerate()` only if you add explicit rotation (not done on every request today).

**Agents: every new `<form method="post">` must include the hidden field.** There is no exemption for “small” or AJAX-style posts unless you change `App::handle()`.

### XSS and view output

- `View::render()` wraps string data in `Escaped`; `<?= $var ?>` emits HTML-escaped output.
- Keys prefixed with `unsafe_` skip escaping (layout uses `unsafe_content`, `unsafe_scripts` for pre-built HTML).
- Manual escaping: `View::e($string)` in attributes or mixed markup.
- Trusted HTML: pass `Framework\Escaped` or use `unsafe_*` keys deliberately—never for user input.

User-controlled fields in forms (e.g. `title`, `description`) must stay in normal escaped variables. Tests assert stored HTML is escaped on show (`ItemsTest::testStoredHtmlInTitleIsEscapedOnShow`).

### HTTP security headers

`framework/HttpSecurity.php` adds on every response (when `security.headers` is true):

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `X-XSS-Protection: 0` (modern browsers rely on CSP)
- `Content-Security-Policy` from config (default restricts to `'self'`)
- Optional `Strict-Transport-Security` when `security.hsts` is true

Extend CSP in `config/app.php` when adding external scripts, styles, or fonts. The default CSP has **no `script-src`**, so inline `<script>` blocks are blocked unless you add e.g. `script-src 'self'` (and avoid `'unsafe-inline'` in production).

### Authentication and sessions

- `Framework\Auth`: session user id, `password_hash` / `password_verify`, `session_regenerate_id(true)` on login and logout.
- Middleware: `Authenticate` (redirect to `/login`, store intended URL), `GuestOnly` (logged-in users away from login/register).
- Password rules: `config/app.php` → `auth.password_min_length` (default 8).

### Authorization (multi-tenant items)

Items are scoped by `user_id`. Controllers call `Item::findForUser($id, Auth::id())`; another user’s id returns **404**, not 403, to avoid leaking existence. Follow this pattern for any new per-user resources.

### PDO / SQL

Use prepared statements via `Framework\Database` / models (see `App\Models\Item`, `User`). Do not concatenate user input into SQL.

---

## Forms and CSRF

### Required pattern

Every POST form includes:

```php
<?= \Framework\View::csrfField() ?>
```

Or `<?= View::csrfField() ?>` when `use Framework\View` is in scope.

`View::csrfField()` outputs a hidden input with escaped name and value. Place it inside `<form method="post">`, typically right after the opening tag (see `app/Views/items/_form.php`, `auth/login.php`, `layouts/main.php` logout form).

### Method and actions

- Mutations use **POST** only (no PUT/PATCH routes). Updates: `POST /items/{id}`; deletes: `POST /items/{id}/delete`.
- `form-action` in CSP is `'self'`; keep `action` paths on the same app origin.

### Validation errors

Controllers re-render forms with `$errors` (list of strings) and `$old` input. Error messages should be plain text from the server, not raw HTML. Field values in `$old` go through normal escaping when echoed in inputs.

### Testing POST from feature tests

`InteractsWithHttp::post()` adds `_csrf` automatically. To assert rejection:

```php
$this->post('/items', ['title' => 'x'], withCsrf: false);
```

See `tests/Feature/CsrfTest.php`.

---

## View scripts

Scripts are **not** inlined in the layout by default. They are queued during view rendering and emitted once before `</body>` via `$unsafe_scripts` in `app/Views/layouts/main.php`.

### Registering scripts

**External file (preferred — works with default CSP):**

```php
<?php
use Framework\View;

View::script('<script src="/assets/password-toggle.js"></script>', 'password-toggle');
?>
```

Put files under `public/assets/`. Example partial: `app/Views/auth/_password_toggle_script.php`, included from login/register views.

**Inline block (requires CSP change):**

```php
<?php View::scriptStart('my-feature'); ?>
<script>
  // ...
</script>
<?php View::scriptEnd(); ?>
```

### De-duplication

- Second argument to `View::script()` or `View::scriptStart()` is an **id**: same id is only output once per request (safe for partials included multiple times).
- Omit the id only when every push must be kept, or use unique ids per row (`'chart-' . $id`).

Script stack resets at the start of each `View::render()` call.

### CSP checklist for new JS

1. Prefer `public/assets/*.js` + `View::script(..., 'unique-id')`.
2. If inline JS is unavoidable, add `script-src` to `config/app.php` `security.csp` (local dev only; avoid `'unsafe-inline'` in production).
3. Add a feature or unit test if behavior is critical (`tests/Unit/ViewScriptStackTest.php`).

---

## Conventions for agents

### Adding a POST route

1. Register route in `routes/web.php` with correct middleware (`$authMiddleware` / `$guestMiddleware`).
2. Implement controller action; validate input; use models with prepared statements.
3. Add a view with `View::csrfField()` in the form.
4. Add `tests/Feature/...` covering success, validation failure, and CSRF if non-obvious.

### Adding a migration

Follow [Database migrations](#database-migrations): new file under `database/migrations/`, callable returns `up` SQL, then `php bin\migrate.php` and `php bin\test.php`.

### Adding a view

- Extend layout via `$this->render('template', $data)` on `Controller`.
- Use normal keys for user-visible strings; reserve `unsafe_` for layout slots and script HTML only.
- Use `View::e()` when building attributes manually.

### Changing security behavior

- CSRF: `framework/App.php`, `framework/Csrf.php`, `config/app.php`
- Headers/CSP: `framework/HttpSecurity.php`, `config/app.php`
- Session cookies: `framework/Session.php`, `config/app.php`

Run `php bin\test.php` after changes; include updates to `tests/Feature/CsrfTest.php` or `tests/Unit/ResponseSecurityTest.php` when behavior changes.

### Do not

- Commit secrets or `.env` with credentials (this app uses SQLite file path only).
- Disable CSRF globally to “fix” a failing form—add `csrfField()` instead.
- Echo user input with `unsafe_` keys or unescaped `<?= ?>`.
- Add inline scripts without updating CSP and documenting the exception in the PR.

---

## Quick reference

| Task | Location / command |
|------|---------------------|
| CSRF field in forms | `View::csrfField()` |
| POST without token | 403 from `App::handle()` |
| Escape string | `View::e()` or default `<?= $var ?>` |
| Raw HTML block | `unsafe_*` view key or `Escaped` |
| Page scripts | `View::script()` / `scriptStart`/`scriptEnd` |
| Auth guard | `Authenticate` / `GuestOnly` middleware |
| Run app | `php -S localhost:8000 -t public public/router.php` |
| Run migrations (CLI) | `php bin\migrate.php` |
| Migration files | `database/migrations/NNN_name.php` |
| Migrator / runner | `framework/Migrations/Migrator.php`, `database/migrate.php` |
| Auto-migrate on boot | `bootstrap/app.php` → `database/migrate.php` |
| Run tests | `php bin\test.php` |
| Human-oriented overview | `README.md` |
| Test layout | `tests/README.md` |
