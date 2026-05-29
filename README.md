# MiniPHP

A minimal pure-PHP homework submission app — no Composer, no packages. Students join classes and upload assignments; teachers grade; admins manage users, classes, and overrides.

I wanted to test out composer 2.5 and got carried away and ended up building out a whole framework.

## Requirements

- PHP 8.2+ (tested on 8.4)
- PDO SQLite extension

## Project layout

```
public/          Document root (`index.php` front controller)
config/          Configuration
framework/       Framework core (+ framework/Testing for test helpers)
tests/           Unit & feature tests (see tests/README.md)
bin/migrate.php  Apply pending database migrations
bin/test.php     Run the test suite
app/
  Controllers/   HTTP handlers
  Models/        Data access
  Views/         Templates
database/        Migrations (`migrations/*.php`, up only)
storage/         SQLite database file
```

## Run locally

From the project root, using PHP's built-in server:

```cmd
php -S localhost:8000 -t public public/index.php
```

Open [http://localhost:8000/](http://localhost:8000/) — guests are sent to login; register as a **student** or log in. Teachers and admins are created in the admin UI (or via CLI below).

### First admin account

After migrating, promote or create an admin:

```cmd
php bin\make-admin.php admin@example.com yourpassword "Admin Name"
```

### Demo data (local playtesting)

Load sample users, classes, and assignments (runs pending migrations first):

```cmd
php bin\seed.php
```

Re-create demo data from scratch:

```cmd
php bin\seed.php --fresh
```

All demo accounts use password `password123` (e.g. `admin@demo.local`, `teacher@demo.local`, `alice@demo.local`). Join codes: `MATH101`, `ENG201`.

Apply schema changes with:

```cmd
php bin\migrate.php
```

If you hit errors on a very old database file, delete `storage/database.sqlite` and run `php bin\migrate.php` again.

### Apache

Point the virtual host document root at `public/`. `public/.htaccess` rewrites all requests to `index.php`.

## Request flow

1. `public/index.php` — autoload, config, session, routes
2. `Router` matches method + path, calls controller
3. Controller uses `Model` + `View`, returns `Response`
4. Response is sent to the client

## View scripts

Register page scripts from any view or partial; they are collected during content render and output once before `</body>` in the layout.

```php
<?php View::script('<script src="/assets/widget.js"></script>', 'widget'); ?>

<?php View::scriptStart('list-actions'); ?>
<script>
  document.querySelectorAll('.delete').forEach(/* ... */);
</script>
<?php View::scriptEnd(); ?>
```

- Pass an **id** as the second argument to `View::script()` (or to `View::scriptStart()`) to include that block only once per request — useful when a partial may be required multiple times.
- Omit the id when you need every push kept (e.g. per-row init), or use unique ids such as `'chart-' . $item['id']`.

**Content-Security-Policy:** the default CSP does not allow inline scripts. Use external `<script src="...">` files, or add an explicit `script-src` in `config/app.php` (e.g. `'self' 'unsafe-inline'` for local development only).

## Tests

```cmd
php bin\test.php
```

Uses an in-memory SQLite database (`config/testing.php`). See `tests/README.md` for layout and conventions.

## Authentication

Session-based auth with hashed passwords (`password_hash` / `password_verify`). CSRF protection applies to all `POST` requests. After login, the session ID is regenerated to reduce session fixation risk.

| Method | Path | Action | Access |
|--------|------|--------|--------|
| GET | `/` | Home | Redirect to `/items` or `/login` |
| GET | `/login` | Login form | Guest |
| POST | `/login` | Log in | Guest |
| GET | `/register` | Register form | Guest |
| POST | `/register` | Create account | Guest |
| POST | `/logout` | Log out | Authenticated |

## Item routes (authenticated)

Each item belongs to the logged-in user. Accessing another user's item returns **404**.

| Method | Path | Action |
|--------|------|--------|
| GET | `/items` | List your items |
| GET | `/items/create` | Create form |
| POST | `/items` | Store |
| GET | `/items/{id}` | Show |
| GET | `/items/{id}/edit` | Edit form |
| POST | `/items/{id}` | Update |
| POST | `/items/{id}/delete` | Delete |
