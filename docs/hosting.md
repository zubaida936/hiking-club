# Hosting & deployment options

Researched 2026-10-28. Prices are monthly, before VAT, and come from vendor pages and 2026 comparison articles.
They change often (Hetzner raised prices in April 2026), so check the vendor's pricing page before buying.

## What the site needs in production

- PHP 8 app (the `prod` target of `docker/php/Dockerfile`, built by CI and pushed to GHCR by `.github/workflows/cd.yml`)
- **Queue worker** (`php artisan queue:work`) and **scheduler** (`schedule:work` / cron, hourly `events:send-reminders`)
- MySQL 8.4, Redis
- Persistent disk for uploads (event photos, GPX, gallery, products) under `storage/app/public`
- Outgoing email (SMTP) for confirmations, reminders, contact form, password codes
- HTTPS on the club's domain

A Docker-capable server covers all of this. Pure static hosts do not.

## Can it be hosted on GitHub?

**No, GitHub can't run the site itself.** GitHub Pages only serves static HTML/JS, with no PHP, database, queue or
scheduler. What GitHub *does* cover:

- the code (repo), CI (Pint, Larastan, tests) and the production image (`ghcr.io/<owner>/<repo>:<sha>`), which is
  already set up and free for this usage;
- the **deploy step**: the `deploy` job in `cd.yml` (placeholder today, behind the `production` environment approval)
  can SSH into the server, pull the image, migrate and restart. So the flow is "push to `main` → CI green → approve →
  live", with GitHub driving it and the site running on one of the hosts below.

## Options

### A. VPS + open-source panel (recommended)

Rent one small Linux server and run the Docker stack on it, managed by a free open-source tool.

| Server | Specs | Price |
|---|---|---|
| Hetzner Cloud CX22 / CX23 (Germany/Finland) | 2 vCPU, 4 GB RAM, 40 GB SSD, 20 TB traffic | ~€4.5–6.5 |
| DigitalOcean Basic Droplet | 1 vCPU, 1–2 GB RAM | $6–12 (backups +20%) |
| Oracle Cloud Always Free (Ampere ARM) | reported cut to 2 OCPU / 12 GB in mid-2026 | **$0**, but free capacity is often "out of stock", the account can be reclaimed, and support is minimal |

Pick one of these to manage it (all free and open source):

| Tool | What it is | Cost |
|---|---|---|
| **Coolify** (Apache-2.0) | Self-hosted Heroku/Vercel alternative: web UI, Git/GitHub deploys, free HTTPS (Let's Encrypt), one-click MySQL/Redis, scheduled DB backups to S3 | Free self-hosted; Coolify Cloud (they host the panel) $5/mo |
| **Dokploy** | Similar to Coolify, Docker/Compose-based | Free self-hosted; cloud from $4.50/server |
| **Plain Docker Compose + GitHub Actions** | `compose.prod.yaml` on the server, the `cd.yml` deploy job runs it over SSH | Free, no panel, fewest moving parts |

**Total: about €5–7/month** (Hetzner plus a self-hosted panel, or Compose).

### B. Laravel-specific managed panels (server provisioning as a service)

They set up and patch the server for you (Nginx, PHP, MySQL, Redis, queue workers, cron, SSL) and deploy on every
push to GitHub. You pay for the panel **plus** the VPS.

| Service | Price | Total with a Hetzner/DO server |
|---|---|---|
| Laravel Forge | Hobby $12, Growth $19, Business $39 (yearly is cheaper) | ~$17–25 |
| Ploi | from ~$9 (yearly) | ~$14–20 |

Note: Forge and Ploi deploy the code, not our Docker image. That still works, but the Dockerfile and image
would only be used for CI.

### C. Fully managed platforms (no server to look after)

| Platform | Pricing model | Realistic cost for this site |
|---|---|---|
| Laravel Cloud | from $5/mo plus usage (compute, DB, bandwidth) | ~$20–40 with MySQL, worker and scheduler |
| Railway | Hobby $5/mo including $5 usage | ~$15–25 (app + worker + MySQL + Redis) |
| Render | paid web services from ~$7; managed DB extra | ~$25+ |
| Fly.io | pay-as-you-go | ~$10–20; MySQL isn't managed there, you run it yourself |
| DigitalOcean App Platform + Managed MySQL | app ~$5–12 plus MySQL from $15 | ~$25–35 |

Less ops work, but costs grow with each extra process, since the queue worker and scheduler each count as a service.

### D. Shared hosting (cPanel), not recommended

It's cheap ($3–10), but there's no Docker, the queue needs a cron workaround, and Redis is usually missing. It
fights the current architecture.

## Extra costs whatever the host

| Item | Cost |
|---|---|
| Domain (.com / .org) | ~$10–15 per year (.lb domains go through the LBDR registry) |
| Cloudflare in front (DNS, CDN, DDoS) | Free plan is enough |
| Transactional email (SMTP) | Free tiers: Brevo ~300/day, Resend ~3,000/month; Mailgun/Postmark paid |
| Off-site backups | Hetzner Storage Box or S3-compatible storage (Backblaze B2, Cloudflare R2) ~€1–4 |

## Recommendation

**Hetzner CX22-class VPS + Coolify (or plain Compose) + Cloudflare free + Brevo/Resend free.**
That's about **€5–7/month plus the domain**. It reuses the Docker image and GitHub pipeline we already have, and every
piece except the server is open source or free.

If nobody at the club wants to touch a server, use **Laravel Cloud** or **Forge + Hetzner** (~$20–40/month).

## Work left once a host is chosen

1. `compose.prod.yaml` (app, nginx, queue, scheduler, mysql, redis, volumes for `storage/` and MySQL)
2. Real `deploy` job in `.github/workflows/cd.yml` (SSH secrets: host, user, key)
3. Production `.env` (`APP_KEY`, `APP_URL`, DB/Redis, `MAIL_*`, `SITE_*`, payment gateway)
4. DNS + HTTPS, a daily DB + uploads backup, and `php artisan storage:link`
5. After the first deploy: `migrate --force`, `db:seed --class=RolesAndPermissionsSeeder --force`, create the first admin
