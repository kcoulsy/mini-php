# Testing

Pure PHP test runner — no Composer, no PHPUnit.

## Layout

```
tests/
  Framework/      Unit tests for `framework/` (Router, View, Auth, Migrator, …)
  App/            Application tests separate from the framework
    Unit/         Models and app-only logic (in-memory SQLite)
  Feature/        HTTP tests through the full app (routes → controllers → views)
  Support/        Base cases and shared helpers (not executed as tests)
  bootstrap.php   Autoloading + createTestApplication()
  run.php         Test runner entry point
```

**Framework** — isolate classes under `framework/`; build dependencies manually or use `DatabaseTestCase` when SQLite is needed (e.g. `Auth`, `Migrator`).

**App** — `app/` models and repositories; extend `Tests\Support\DatabaseTestCase`.

**Feature** — extend `Tests\Support\ApplicationTestCase`; use `$this->get()` / `$this->post()`.

When you add or change a class in `framework/`, add or update a matching test under `tests/Framework/`.

## Run

```cmd
php bin\test.php
php tests\run.php
php tests\run.php tests\Framework
php tests\run.php tests\App
php tests\run.php tests\Feature\ItemsTest.php
php tests\run.php --quiet
```

By default each passing test is logged as it runs (`PASS Class::testName (N assertions)`), grouped by file. Use `--quiet` or `-q` for the compact dot progress style.

## Writing a test

1. Create `tests/Framework/SomethingTest.php`, `tests/App/Unit/SomethingTest.php`, or `tests/Feature/SomethingTest.php`.
2. Namespace: `Tests\Framework`, `Tests\App\Unit`, or `Tests\Feature` (must mirror the path under `tests/`).
3. Extend `Framework\Testing\TestCase` (framework/app unit) or `Tests\Support\ApplicationTestCase` (feature).
4. Add public methods named `test*` (e.g. `testUserCanLogin`).

## Framework helpers

| Piece | Purpose |
|--------|---------|
| `TestCase` | `assertEquals`, `assertTrue`, `setUp` / `tearDown` |
| `Assert` | Static assertions (used by `TestCase`) |
| `InteractsWithHttp` | `get()`, `post()`, `assertOk`, `assertRedirect`, `assertSee` |
| `InteractsWithDatabase` | `refreshDatabase()` for custom DB setup |
| `Request::from()` | Synthetic requests without `$_SERVER` |
| `App::handle()` | Dispatch a request, get `Response` without sending headers |

## Scaling up

- Add framework tests under `tests/Framework/` for each new `framework/` class.
- Add app model tests under `tests/App/Unit/`.
- Add one feature test per route or user flow under `tests/Feature/`.
- Extract shared setup to `tests/Support/` (factories, traits).
- Split slow groups by passing a directory to `run.php` in CI.
