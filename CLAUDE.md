# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

This is the **Laravel Vue Starter Kit** scaffold (Laravel 13, Vue 3, Inertia 2). Despite the
`laravel-pgvector-rag` directory name, **no pgvector or RAG code exists yet** — the repo is
currently just the auth/settings starter. New RAG functionality (vector storage, embeddings,
retrieval) has to be built from scratch.

## Commands

```bash
composer dev          # Run everything: PHP server, queue worker, pail logs, Vite (concurrently)
composer setup        # First-time setup: install deps, .env, key:generate, migrate, npm build
composer test         # config:clear + lint:check (Pint) + php artisan test (Pest)
composer lint         # Pint --parallel (auto-fix PHP)
composer ci:check     # Full CI gate locally: eslint + prettier + vue-tsc + tests

php artisan test --filter=ProfileUpdateTest   # Run a single test class
php artisan test tests/Feature/Auth           # Run a directory of tests

npm run dev           # Vite dev server only
npm run build         # Production build (use build:ssr for SSR)
npm run types:check   # vue-tsc --noEmit (TypeScript check)
npm run lint          # eslint --fix
npm run format        # prettier --write resources/
```

Tests run on in-memory SQLite (see `phpunit.xml`); the dev DB defaults to `database/database.sqlite`.
Tests are written with **Pest 4**, not raw PHPUnit.

## Architecture

**Inertia 2 SPA bridge.** No separate API or client-side router. Laravel controllers return
`Inertia::render('PageName', [...props])`; each maps to a `.vue` file in `resources/js/pages/`.
Routes live in `routes/web.php` and `routes/settings.php`; trivial pages use `Route::inertia(...)`.
Props shared with every page (auth user, app name, sidebar state) are defined in
`app/Http/Middleware/HandleInertiaRequests.php::share()`.

**Wayfinder (critical).** `resources/js/actions/` and `resources/js/routes/` are **generated**
from PHP controllers and named routes by `laravel/wayfinder` (runs via the Vite plugin). Do not
hand-edit these — they regenerate on build/dev. Import typed route/action helpers from there in
Vue components instead of hardcoding URLs. Backend route or controller changes propagate to the
frontend through regeneration.

**Auth via Fortify (headless).** `laravel/fortify` provides the backend auth logic; there are no
Fortify Blade views. `app/Providers/FortifyServiceProvider.php` wires every auth screen
(login, register, reset, 2FA challenge, confirm password, verify email) to an Inertia page under
`resources/js/pages/auth/`, and registers the user-creation / password-reset actions in
`app/Actions/Fortify/`. Rate limiting is also configured there. Passkeys (`@laravel/passkeys`)
and two-factor auth are enabled.

**Frontend UI.** Vue 3 + TypeScript + Tailwind v4 (via `@tailwindcss/vite`). UI primitives in
`resources/js/components/ui/` follow the shadcn-vue convention (config in `components.json`,
built on `reka-ui`); use `cn()` from `resources/js/lib`. Layouts in `resources/js/layouts/`
(app / auth / settings). Icons from `lucide-vue-next`.

## Conventions

- PHP style is enforced by **Pint** (`laravel` preset); the linter CI runs on push/PR to
  `develop`, `main`, `master`, `workos`.
- Validation rules shared across requests live in `app/Concerns/` traits
  (`PasswordValidationRules`, `ProfileValidationRules`); reuse these rather than redefining.
- Settings controllers (`app/Http/Controllers/Settings/`) pair with `app/Http/Requests/Settings/`
  form requests — follow this controller+FormRequest pattern for new settings screens.
- This project ships `laravel/chisel` and `laravel/pao` — check their generators/conventions
  before scaffolding new code by hand.
