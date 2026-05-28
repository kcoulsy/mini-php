# MiniPHP

A minimal pure-PHP framework and CRUD demo — no Composer, no packages. Uses a front controller, MVC layout, PDO (SQLite), and a simple router.

I wanted to test out composer 2.5 and got carried away and ended up building out a whole framework.

## Requirements

- PHP 8.2+ (tested on 8.4)
- PDO SQLite extension

## Project layout

```
public/          Document root (front controller)
bootstrap/       Application bootstrap
config/          Configuration
routes/          Route definitions
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
php -S localhost:8000 -t public public/router.php
```

Open [http://localhost:8000/](http://localhost:8000/) — guests are sent to login; register or log in to manage items.

Apply schema changes with:

```cmd
php bin\migrate.php
```

Migrations also run automatically on app bootstrap. If you hit errors on a very old database file, delete `storage/database.sqlite` and run `php bin\migrate.php` again.

### Apache

Point the virtual host document root at `public/`. `public/.htaccess` rewrites all requests to `index.php`.

## Request flow

1. `public/index.php` — front controller
2. `bootstrap/app.php` — autoloader, config, database, routes
3. `Router` matches method + path, calls controller
4. Controller uses `Model` + `View`, returns `Response`
5. Response is sent to the client

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
