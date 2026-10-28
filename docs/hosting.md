# Hosting & deployment options

Researched 2026-10-28. For a $0 start, see "Fully free setup" below. Prices are monthly, before VAT, and come from vendor pages and 2026 comparison articles.
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

## Recommendation (paid)

**Hetzner CX22-class VPS + Coolify (or plain Compose) + Cloudflare free + Brevo/Resend free.**
That's about **€5–7/month plus the domain**. It reuses the Docker image and GitHub pipeline we already have, and every
piece except the server is open source or free.

If nobody at the club wants to touch a server, use **Laravel Cloud** or **Forge + Hetzner** (~$20–40/month).

## Demo only (show the club, no hosting yet)

For a demo the site doesn't need to be online 24/7, only while someone is looking at it. Demo data comes from
`php artisan migrate --seed` (demo events, shop, content, admin user).

| Option | Cost | URL | Good / bad |
|---|---|---|---|
| **Your PC + Cloudflare Quick Tunnel** (recommended) | Free, no account | `https://<random>.trycloudflare.com` | Ready in ~5 minutes on the stack we already run. Works only while the PC and Docker are on; the URL changes each start |
| **GitHub Codespaces** | Free: 120 core-hours/month (≈60 h on a 2-core machine) + 15 GB storage | `https://<codespace>-8080.app.github.dev` (set the port to *Public*) | Runs in GitHub's cloud, so your PC can be off. It sleeps after 30 min idle (configurable up to 4 h), so someone must start it before the demo. 2-core/8 GB is enough for the stack |
| ngrok (free) | Free, account | `https://<name>.ngrok-free.app` (one fixed dev domain) | Stable URL, but visitors first see an ngrok warning page, and there's a 1 GB/month traffic cap |
| Railway trial / Render free | Free credit / free web service | `*.up.railway.app`, `*.onrender.com` | Needs the deploy setup anyway. Render's free tier sleeps and has no MySQL, so it's more work than the two options above |

### Demo from your PC (Cloudflare Quick Tunnel)

1. `docker compose up -d` and `docker compose run --rm node npm run build`. Use built assets: with the Vite dev
   server running (`public/hot` present), outside browsers would try to load CSS/JS from `localhost:5173`.
2. Start the tunnel: `docker run --rm cloudflare/cloudflared tunnel --url http://host.docker.internal:8080`.
   It prints the `https://….trycloudflare.com` URL. Share it while it runs; Ctrl+C ends the demo.
3. Set `APP_URL` in `.env` to that URL (emails and absolute links use it), then `php artisan config:clear`.

### Demo in GitHub Codespaces

1. Repo → **Code → Codespaces → Create codespace on main** (2-core). Docker works inside Codespaces.
2. In the terminal: `cp .env.example .env`, `docker compose up -d`, `docker compose exec app php artisan key:generate`,
   `docker compose exec app php artisan migrate --seed`, `docker compose run --rm node npm run build`.
3. **Ports** tab → port 8080 → *Port visibility: Public*, then copy the URL into `APP_URL`.
4. Stop the codespace after the demo (stopped codespaces only use storage). Unused ones are deleted after 30 days.

A `.devcontainer/devcontainer.json` could automate steps 1–2 later.

### Needed in the app for both demos

Both demos put a proxy (the tunnel or Codespaces) in front of Laravel. Laravel doesn't trust proxies yet
(`bootstrap/app.php`), so it would generate `http://` / `localhost` links and the browser blocks mixed content.
Fix: `$middleware->trustProxies(at: '*');` in `withMiddleware()`. Production behind Cloudflare or Coolify needs it too.

## Fully free setup (no domain yet): Cloudflare + Brevo

The club wants to start at $0 with Cloudflare and Brevo, and no domain for now. This works. Each piece and its limits:

| Piece | Free choice | Limits / notes |
|---|---|---|
| Code, CI, image | GitHub + GitHub Actions + GHCR | Unlimited Actions minutes on a public repo; a private repo gets ~2,000 min/month and 500 MB of package storage on the free plan |
| Server | **Oracle Cloud Always Free** (Ampere ARM, reported 2 OCPU / 12 GB since mid-2026, 200 GB disk) | Card needed at sign-up. Free ARM capacity is often "out of stock" (try another region / retry). Idle free instances can be reclaimed, so keep the site running. The image must also be built for **arm64** (see below) |
| ↳ fallback | Google Cloud e2-micro (Always Free, us-west1 / us-central1 / us-east1) | Only 1 GB RAM and 30 GB disk: add swap, tune MySQL down. US regions, so it's slower from Lebanon. Billing account required |
| Server management | Coolify (self-hosted) or plain Compose | Free, open source |
| Public address (no domain) | see "Getting a URL without a domain" below | |
| HTTPS | Let's Encrypt (via Coolify/Caddy) or Cloudflare | Free |
| Email | **Brevo free** | 300 emails/day, SMTP relay |
| Backups | Cloudflare R2 (10 GB free, no egress fees) | S3-compatible, so Coolify's backup and Laravel's `s3` disk work with it |

**Total: $0/month.** The trade-offs: no SLA, Oracle's free-tier policies can change, and someone at the club is the "ops person".

### What Cloudflare can and can't do here

- **It can't run the site.** Cloudflare Pages and Workers run static files and JavaScript, not PHP/Laravel with MySQL.
- **Cloudflare Tunnel** (`cloudflared` on the server) publishes the site without opening ports, and hides the server IP.
  - *Quick Tunnel* (no account, no domain): you get a random `https://<words>.trycloudflare.com` URL, which is enough for a demo
    or for the club to click around. Cloudflare says it's **not for production**: the URL changes on every restart,
    there's no uptime guarantee, it caps at 200 concurrent requests, and these URLs get indexed by Google.
  - *Named tunnel* with a fixed public URL: this needs a domain added to Cloudflare. Most of Cloudflare's value (stable URL, CDN,
    DDoS protection, free SSL, caching) starts once the club has a domain.
- **R2** is usable today without a domain, for backups and optionally uploads.

### Getting a URL without a domain

| Option | URL looks like | Good for |
|---|---|---|
| Cloudflare Quick Tunnel | `https://random-words.trycloudflare.com` (changes on restart) | demos and testing only |
| sslip.io / nip.io (Coolify sets this up automatically) | `http(s)://<server-ip>.sslip.io` | testing; ugly but stable |
| DuckDNS (free subdomain) | `https://hikingclub.duckdns.org` + Let's Encrypt | a stable, free URL until a real domain is bought |

Laravel puts `APP_URL` into emails (booking links, invites, password links, order pages), so a URL that changes
breaks old links. Use a stable one (DuckDNS) as soon as real hikers get emails.

### Brevo without a domain

- Laravel `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, `MAIL_USERNAME=<Brevo SMTP login>`,
  `MAIL_PASSWORD=<Brevo SMTP key>`, `MAIL_FROM_ADDRESS=<verified sender>`, `MAIL_FROM_NAME="${APP_NAME}"`.
- Brevo only sends from a **verified sender**. Without a domain, that has to be an existing address (e.g. the club's Gmail).
  Gmail/Yahoo enforce DMARC on their own domains, and reports say Brevo rewrites unauthenticated senders (e.g. to
  `@brevosend.com`). So expect mails to show a strange From or land in **spam**. That's fine for testing, but not
  for launch.
- 300 mails/day covers bookings, reminders and password codes for a club this size. Big newsletters would need a
  paid plan or have to be split across days.
- When the domain is bought: add it in Brevo, put the DKIM/SPF/DMARC records in Cloudflare DNS (free), and switch
  `MAIL_FROM_ADDRESS` to e.g. `noreply@<domain>`.

### Changes needed in the repo for this setup

1. `cd.yml`: build a multi-arch image so it runs on Oracle ARM. Add `docker/setup-qemu-action` and set
   `platforms: linux/amd64,linux/arm64` on `build-push-action`.
2. `compose.prod.yaml` with low-memory MySQL settings (`innodb_buffer_pool_size` ~256 MB if on the 1 GB Google VM).
3. Optional `cloudflared` service in `compose.prod.yaml` for the Quick Tunnel, swapped for a named tunnel once there's a domain.

### Upgrade path (when the club is ready)

1. Buy a domain (~$10–15/year) and add it to Cloudflare (free plan). That gives a named tunnel or proxied DNS,
   with HTTPS, CDN and a hidden server IP.
2. Authenticate the domain in Brevo, so email stops going to spam.
3. If Oracle capacity or reliability becomes a problem, move the same image to Hetzner (~€5–7/month, option A).
   Nothing in the app changes.

## Work left once a host is chosen

1. `compose.prod.yaml` (app, nginx, queue, scheduler, mysql, redis, volumes for `storage/` and MySQL)
2. Real `deploy` job in `.github/workflows/cd.yml` (SSH secrets: host, user, key)
3. Production `.env` (`APP_KEY`, `APP_URL`, DB/Redis, `MAIL_*`, `SITE_*`, payment gateway)
4. DNS + HTTPS, a daily DB + uploads backup, and `php artisan storage:link`
5. After the first deploy: `migrate --force`, `db:seed --class=RolesAndPermissionsSeeder --force`, create the first admin
