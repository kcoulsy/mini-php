# MiniPHP

A minimal pure-PHP framework and CRUD demo — no Composer, no packages. Uses a front controller, MVC layout, PDO (SQLite), and a simple router.

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
bin/test.php     Run the test suite
app/
  Controllers/   HTTP handlers
  Models/        Data access
  Views/         Templates
database/        Schema migration
storage/         SQLite database file
```

## Run locally

From the project root, using PHP's built-in server:

```cmd
php -S localhost:8000 -t public public/router.php
```

Open [http://localhost:8000/items](http://localhost:8000/items).

### Apache

Point the virtual host document root at `public/`. `public/.htaccess` rewrites all requests to `index.php`.

## Request flow

1. `public/index.php` — front controller
2. `bootstrap/app.php` — autoloader, config, database, routes
3. `Router` matches method + path, calls controller
4. Controller uses `Model` + `View`, returns `Response`
5. Response is sent to the client

## Tests

```cmd
php bin\test.php
```

Uses an in-memory SQLite database (`config/testing.php`). See `tests/README.md` for layout and conventions.

## CRUD routes

| Method | Path | Action |
|--------|------|--------|
| GET | `/items` | List |
| GET | `/items/create` | Create form |
| POST | `/items` | Store |
| GET | `/items/{id}` | Show |
| GET | `/items/{id}/edit` | Edit form |
| POST | `/items/{id}` | Update |
| POST | `/items/{id}/delete` | Delete |
