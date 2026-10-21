# Testing links

Everything you can open while testing. Start the stack first: `docker compose up -d`
(run commands in PowerShell from `C:\Dev\Projects\hiking`).

## Logins

| Account | Login | Password |
|---|---|---|
| CMS super admin | `admin@hiking.test` | `password` |
| phpMyAdmin | automatic (root) | — |
| MySQL from a desktop client (HeidiSQL, DBeaver, Workbench) | host `127.0.0.1`, port `33060`, user `hiking` (or `root`) | `secret` (root: `root`) |

## Tools

| Tool | Link | Use it for |
|---|---|---|
| Website | http://localhost:8080 | Public site (placeholder page for now) |
| CMS | http://localhost:8080/admin | Back office |
| phpMyAdmin | http://localhost:8081 | Inspect the `hiking` database (`hiking_testing` is for automated tests only — ignore it) |
| Mailpit | http://localhost:8025 | Every email the app sends in development lands here |
| Health check | http://localhost:8080/up | Should show "Application up" |

## CMS pages

| Page | Link | Who can open it |
|---|---|---|
| Login | http://localhost:8080/admin/login | Guests |
| Dashboard | http://localhost:8080/admin | All staff roles |
| Staff & roles — list | http://localhost:8080/admin/staff | Super Admin |
| Staff & roles — new user | http://localhost:8080/admin/staff/create | Super Admin |
| Staff & roles — edit user | http://localhost:8080/admin/staff/{id}/edit (use the Edit link in the list) | Super Admin |
| Events — list | http://localhost:8080/admin/events | Super Admin, Admin |
| Events — upcoming only | http://localhost:8080/admin/events?when=upcoming | Super Admin, Admin |
| Events — new event | http://localhost:8080/admin/events/create | Super Admin, Admin |
| Events — edit / publish / cancel / duplicate | http://localhost:8080/admin/events/{id}/edit (click an event title) | Super Admin, Admin |

Uploaded images are served from `http://localhost:8080/storage/...` (files live in `storage/app/public`).

## Demo data

`docker compose exec app php artisan migrate:fresh --seed` creates 6 sample events (published, draft,
cancelled, past and X events) so every screen has something to show. Re-run it to reset them.

## GitHub

| What | Link |
|---|---|
| Repository | https://github.com/zubaida936/hiking-club |
| CI / CD runs | https://github.com/zubaida936/hiking-club/actions |
| Pull requests | https://github.com/zubaida936/hiking-club/pulls |
| Branch protection | https://github.com/zubaida936/hiking-club/settings/branches |
| Docker images (after CD runs) | https://github.com/zubaida936?tab=packages |

## ClickUp test cases

Each feature task has `TC-xx` subtasks — close each one when it passes.

| Feature | Task |
|---|---|
| Docker environment & CI/CD | https://app.clickup.com/t/z8q7hbmpk2 |
| Project setup & database | https://app.clickup.com/t/z8q7hbmped |
| CMS admin auth & roles | https://app.clickup.com/t/z8q7hbmpfn |
| CMS events management | https://app.clickup.com/t/z8q7hbmpfp |
| Difficulty formula & levels | see "3. Difficulty System" list in ClickUp |

## Useful commands

```powershell
docker compose up -d                                      # start everything
docker compose stop                                       # stop (data is kept)
docker compose ps                                         # what is running
docker compose exec app php artisan test                  # automated tests
docker compose exec app php artisan migrate:fresh --seed  # reset dev data to a clean state
docker compose logs -f app                                # follow app logs
docker compose exec app php artisan events:recalculate-difficulty  # after changing config/difficulty.php
```
