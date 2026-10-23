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
| Website | http://localhost:8080 | Public site |
| CMS | http://localhost:8080/admin | Back office |
| phpMyAdmin | http://localhost:8081 | Inspect the `hiking` database (`hiking_testing` is for automated tests only — ignore it) |
| Mailpit | http://localhost:8025 | Every email the app sends in development lands here |
| Health check | http://localhost:8080/up | Should show "Application up" |

## Public site pages

Every header link works. Pages marked *coming soon* show a placeholder until their feature ships.

| Page | Link | Status |
|---|---|---|
| Home | http://localhost:8080/ | Hero carousel, About, Next Challenge, X Events, contact. Leaderboard and newsletter are placeholders; texts/contact details are placeholders in `config/site.php` |
| About | http://localhost:8080/about | Coming soon |
| Events | http://localhost:8080/events | Upcoming events (soonest first) and Past events with Load More; filter by level or month, e.g. http://localhost:8080/events?level=2 or http://localhost:8080/events?month=2026-11 |
| X Events | http://localhost:8080/x-events | Coming soon |
| Shop | http://localhost:8080/shop | Coming soon |
| Rank System | http://localhost:8080/rank-system | Coming soon |
| Contact | http://localhost:8080/contact | Coming soon |
| Hiker's Guide → Difficulty Calculator | http://localhost:8080/difficulty-calculator | Coming soon |
| Hiker's Guide → Equipment Guide | http://localhost:8080/equipment | Coming soon |
| Hiker's Guide → Guides | http://localhost:8080/guides | Coming soon |
| My account (person icon) | http://localhost:8080/account | Coming soon |
| Cart (cart icon) | http://localhost:8080/cart | Coming soon |
| Privacy Policy (footer) | http://localhost:8080/privacy-policy | Coming soon |

A green WhatsApp button floats in the bottom-right corner of every public page and opens a chat with
`SITE_WHATSAPP` with a pre-filled message (`config/site.php` → `contact.whatsapp_message`). Leave
`SITE_WHATSAPP` empty to hide it, together with the WhatsApp links in the footer and on the homepage.

### SEO & analytics

| What | Link / where |
|---|---|
| Sitemap | http://localhost:8080/sitemap.xml (real pages only; events appear once the event page exists) |
| robots.txt | http://localhost:8080/robots.txt (blocks everything except when `APP_ENV=production`) |
| Default share image | http://localhost:8080/images/og-default.jpg (placeholder, 1200×630) |
| Share preview check | view page source and look for `og:` tags, or paste the live URL into https://www.opengraph.xyz once deployed |
| Analytics | set `GOOGLE_ANALYTICS_ID=G-...` in `.env` + `php artisan config:clear`; never loaded in the CMS |

### Newsletter (Next Destination Alerts)

| What | Where |
|---|---|
| Sign-up form | http://localhost:8080/#newsletter (email, phone, or both) |
| Confirmation and announcement emails | Mailpit, http://localhost:8025 |
| Confirm link | in the "Confirm your Next Destination Alerts" email → `/newsletter/confirm/{token}` |
| Unsubscribe page | link at the bottom of a "New hike" email → `/newsletter/unsubscribe/{token}` |
| Subscribers list | phpMyAdmin → `hiking` → `subscribers` (no CMS screen yet) |

Announcements go out when an upcoming event is published for the first time, to confirmed email
subscribers only. Emails are sent by the `queue` container: after pulling new code run
`docker compose restart queue`, or emails may use old templates.

An orange bar above the header announces the next published upcoming event on every public page
("View Event" opens Events until the event detail page exists); it disappears when nothing is coming up.

The footer (contact details, social links, site map, copyright) is on every public page. Its address, phone,
email and social handles are placeholders in `config/site.php`, or set them in `.env` with `SITE_ADDRESS`,
`SITE_PHONE`, `SITE_WHATSAPP`, `SITE_EMAIL`, `SITE_FACEBOOK`, `SITE_INSTAGRAM`, `SITE_TIKTOK`, `SITE_YOUTUBE`
(leave a social one empty to hide it).

To check the mobile menu, make the browser narrower than 1024 px (or press F12 → device toolbar).

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

The demo data has only 2 past events, so the Events page shows no "Load More" button. To get one, add
8 random published past events:

```powershell
docker compose exec app php artisan tinker --execute="App\Models\Event::factory()->published()->past()->count(8)->create()"
```

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
| Header & navigation | https://app.clickup.com/t/z8q7hbmpee |
| Homepage | https://app.clickup.com/t/z8q7hbmpeh |
| Footer | https://app.clickup.com/t/z8q7hbmpeg |
| Next-event announcement bar | https://app.clickup.com/t/z8q7hbmpef |
| Newsletter signup | https://app.clickup.com/t/z8q7hbmpej |
| Floating WhatsApp button | https://app.clickup.com/t/z8q7hbmpek |
| SEO, performance & analytics | https://app.clickup.com/t/z8q7hbmpem |
| Events listing page | https://app.clickup.com/t/z8q7hbmpep |
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
