# Agent guide — MiniPHP

This document is for humans and coding agents working in this repo. It summarizes how to run the app, where security lives, and the conventions you must follow when adding routes, forms, views, or scripts.

## Stack and layout

- **PHP 8.2+** with PDO SQLite. No runtime Composer packages; PSR-4 autoloading via `framework/Autoloader.php` (optional `composer.json` for IDE tooling only).
- **Front controller:** `public/index.php` → `bootstrap/app.php` → `routes/web.php`.
- **MVC:** `app/Controllers`, `app/Models`, `app/Views`; core in `framework/`.
- **Config:** `config/app.php` (app, database, session, auth, security).
- **DB:** SQLite at `storage/database.sqlite`; schema applied on every bootstrap via `database/migrate.php`.

```
public/          Document root (only web-exposed tree)
bootstrap/       App bootstrap + session start
config/          Configuration
routes/          Route definitions
framework/       Router, Request, View, CSRF, Auth, security headers, tests
app/             Application code
database/        Migrations (idempotent CREATE IF NOT EXISTS)
tests/           Unit + feature tests (see tests/README.md)
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

**Schema changes:** if migrations fail on an old DB, delete `storage/database.sqlite` and reload so tables are recreated.

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
| Run tests | `php bin\test.php` |
| Human-oriented overview | `README.md` |
| Test layout | `tests/README.md` |
