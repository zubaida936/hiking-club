# Hiking Club Website

Hiking community website (events, bookings, difficulty levels, rank/loyalty system, X events, shop, guides)
with a custom CMS to manage all content. Built with Laravel 13, MySQL 8.4 and Redis, fully Dockerized.

## Quick start

Requirements: Docker Desktop.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

| Service | URL |
|---|---|
| Website | http://localhost:8080 |
| CMS | http://localhost:8080/admin (seeded: `admin@hiking.test` / `password`) |
| Mailpit (outgoing mail) | http://localhost:8025 |
| phpMyAdmin | http://localhost:8081 (auto-login as root) |
| MySQL (from host) | `127.0.0.1:33060` |

The `node` service runs the Vite dev server with hot reload. For a production asset build:
`docker compose run --rm node npm run build`.

See [TESTING.md](TESTING.md) for every link and login available while testing.

## Quality checks

```bash
docker compose exec app php artisan test          # Pest, against the hiking_testing database
docker compose exec app vendor/bin/pint           # code style
docker compose exec app vendor/bin/phpstan analyse # static analysis (Larastan level 6)
```

## Docker services

`app` (PHP 8.4-FPM), `nginx`, `mysql`, `redis` (cache, sessions, queues), `queue` (worker),
`scheduler`, `node` (Vite), `mailpit`. The production image is the `prod` target of
`docker/php/Dockerfile` (built assets, no dev dependencies, OPcache + JIT).

## CI/CD (GitHub Actions)

- **CI** (`.github/workflows/ci.yml`), on every PR and push to `main`: Pint → Larastan → Pest on MySQL
  (with coverage ≥ 70%) → production Docker image build.
- **CD** (`.github/workflows/cd.yml`), after CI passes on `main`: publish the image to GHCR, then deploy
  through the `production` environment (needs manual approval; the deploy target isn't configured yet).

Recommended: protect `main` and require the CI checks to pass before merging.

## Documentation

- [TESTING.md](TESTING.md): every page, login and link to try while testing.
- [docs/payments.md](docs/payments.md): how hikers pay, testing online payments with the test gateway, going live with Whish Money / OMT.
- [docs/rewards.md](docs/rewards.md): what each rank unlocks and how rewards are used.
- [docs/x-events.md](docs/x-events.md): applying, reviewing and booking X Events.
- [docs/shop.md](docs/shop.md): products, stock, cart, checkout, order steps and how to test the shop.
- [docs/open-decisions.md](docs/open-decisions.md): decisions the club still has to take, placeholders to replace, known gaps.

## Architecture

A modular monolith. See [CLAUDE.md](CLAUDE.md) for the layer conventions.

```
app/
  Domain/<Module>/{Actions,Data,Enums,Exceptions,Events}   business logic per module
  Http/Controllers/{Admin,Site}                            thin controllers
  Http/Requests                                            validation → DTOs
  Models, Policies
routes/web.php, routes/admin.php
docker/                                                    Dockerfile, nginx, php, mysql init
```

## Roles

| Role | CMS access |
|---|---|
| Super Admin | Everything, including staff & roles |
| Admin | Everything except staff management |
| Content Editor | Content and media |
| Guide | View registrations and mark attendance |
| Member | No CMS access (public site account) |
