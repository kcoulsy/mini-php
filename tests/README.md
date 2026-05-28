# Testing

Pure PHP test runner — no Composer, no PHPUnit.

## Layout

```
tests/
  Unit/           Fast tests for framework classes (Router, Request, …)
  Feature/        HTTP tests through the full app (routes → controllers → views)
  Support/        Base cases and shared helpers (not executed as tests)
  bootstrap.php   Autoloading + createTestApplication()
  run.php         Test runner entry point
```

**Unit** — isolate one class; build dependencies manually.  
**Unit (models)** — extend `DatabaseTestCase` for in-memory SQLite + migrations.  
**Feature** — extend `ApplicationTestCase`; use `$this->get()` / `$this->post()`.

## Run

```cmd
php bin\test.php
php tests\run.php
php tests\run.php tests\Unit
php tests\run.php tests\Feature\ItemsTest.php
```

## Writing a test

1. Create `tests/Unit/SomethingTest.php` or `tests/Feature/SomethingTest.php`.
2. Namespace: `Tests\Unit` or `Tests\Feature`.
3. Extend `Framework\Testing\TestCase` (unit) or `Tests\Support\ApplicationTestCase` (feature).
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

- Add unit tests beside each new framework class under `tests/Unit/`.
- Add one feature test per route or user flow under `tests/Feature/`.
- Extract shared setup to `tests/Support/` (factories, traits).
- Split slow groups by passing a directory to `run.php` in CI.
