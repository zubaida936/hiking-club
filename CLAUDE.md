# Hiking Club — project guidelines

Laravel 13 + MySQL 8.4 + Redis, everything runs in Docker (no PHP on the host).
Public site + custom CMS (`/admin`). Feature backlog lives in ClickUp, folder "Hiking Club Website".

## Commands (always through Docker)
- `docker compose up -d` — start the stack (site: http://localhost:8080, CMS: /admin, Mailpit: :8025, phpMyAdmin: :8081)
- `docker compose exec app php artisan test` — test suite (uses the `hiking_testing` DB)
- `docker compose exec app vendor/bin/pint` — format; `vendor/bin/phpstan analyse` — static analysis
- `docker compose exec app php artisan migrate --seed`
- npm runs in the `node` service; never run npm/composer on the Windows host.

## Architecture
- `app/Domain/<Module>/` — business logic per bounded context: `Actions/` (one use case each, `handle()`),
  `Data/` (readonly DTOs), `Enums/`, `Exceptions/` (domain rule violations, user-safe messages), `Events/`.
- `app/Models/` — Eloquent models (kept in the Laravel default location for factory/policy discovery).
- `app/Http/Controllers/{Admin,Site}/` — thin controllers: authorize → validate (FormRequest → DTO) → call Action → respond.
- `app/Policies/` — authorization; always check a `Permission` enum, never a role name.
- `routes/admin.php` — CMS routes (`admin.` name prefix, `auth` + `admin` middleware).
- Views: CMS in `resources/views/admin`, reusable CMS components in `resources/views/components/admin`.
- New permissions/roles: edit `App\Domain\Identity\Enums\{Permission,Role}`; `RolesAndPermissionsSeeder` syncs them (idempotent, run on deploy).

## Workflow rules
- One branch per feature: `feature/<short-name>`. Commit locally; **never push to GitHub without asking the user first**.
- Every feature ships with Pest tests; CI (Pint, Larastan level 6, tests, Docker build) must be green.
- After each feature, add its manual test cases to the matching ClickUp task.
