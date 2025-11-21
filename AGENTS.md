# Repository Guidelines

## Project Structure & Module Organization
Application logic sits in `app/` (HTTP controllers, console commands, Filament panels) with routes under `routes/web.php` and `routes/api.php`; scheduled/CLI work belongs in `routes/console.php`. Front-end views/components stay inside `resources/views` and `resources/js`, then Vite outputs to `public/`. Configuration lives in `config/`, persistence assets in `database/` (migrations, factories, seeders), and automated checks in `tests/Feature` + `tests/Unit`. Container assets (`deploy/`, `Dockerfile`, `frankenphp/`) must remain production-safe and isolated from app concerns.

## Build, Test, and Development Commands
- `composer install && npm install` – fetch PHP and Vite dependencies.
- `cp .env.example .env && php artisan key:generate` – initialize local secrets.
- `php artisan migrate --seed` – sync schemas and baseline data.
- `php artisan serve` or `php artisan octane:start --server=frankenphp` – run the backend.
- `npm run dev` / `npm run build` – start Vite watcher or produce versioned assets in `public/build`.
- `php artisan test` (optionally `--filter`, `--coverage`) – execute the PHPUnit 10 suite.

## Coding Style & Naming Conventions
Adhere to PSR-12 with four-space indentation for PHP and run `./vendor/bin/pint` before committing. Keep classes StudlyCase (`UserProfileService`), config keys snake_case, Blade components kebab-case, and JavaScript modules two-space indented ES modules (`resources/js/app.js`). Route files should stay thin; push domain rules into dedicated services or actions inside `app/`.

## Testing Guidelines
Use PHPUnit via `php artisan test`; Pest is not configured. Organize request/response scenarios under `tests/Feature/*Test.php`, logic units under `tests/Unit`, and mirror filenames after the class under test. Leverage factories in `database/factories` and `RefreshDatabase` to isolate state, and target at least ~80% coverage on new code paths before opening a PR.

## Commit & Pull Request Guidelines
Follow the existing history’s imperative, prefixed style (`Refactor: dashboard response api`, `Fix: auth middleware`). Keep commits small, include migrations or seeders alongside the feature they support, and mention related issue IDs in the body. Pull requests need a short summary, test evidence (commands or screenshots for Filament screens), notes on any `.env` or schema changes, and at least one reviewer plus green CI before merge.

## Security & Configuration Tips
Do not commit `.env`, `storage/`, or other secrets; update `.env.example` whenever new keys appear. Cache configuration for releases with `php artisan config:cache` and clear with `php artisan optimize:clear` when debugging. Provision Filament admin credentials and Sanctum tokens via deployment variables referenced in `deploy/` manifests rather than hard-coding values in `config/`.
