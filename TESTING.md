# Testing links

Everything you can open while testing. Start the stack first: `docker compose up -d`
(run commands in PowerShell from `C:\Dev\Projects\hiking`).

## Logins

| Account | Login | Password |
|---|---|---|
| CMS super admin | `admin@hiking.test` | `password` |
| Member (after you activate one) | phone you booked with | the password you chose |
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
| Home | http://localhost:8080/ | Hero carousel (slides from CMS → Homepage slides, or the `config/site.php` placeholders while none is shown), About, Next Challenge, X Events, Top 10 Hikers (live leaderboard), newsletter sign-up, contact. Texts/contact details are placeholders in `config/site.php` |
| Leaderboard | http://localhost:8080/leaderboard | Up to 100 hikers by points (ties by km), with rank tier, hikes and km. Names shown as "Rima H."; hikers without points or who chose "Hide me" on My account are not listed. Logged-in members see their own place |
| About | http://localhost:8080/about | Story and founding year, What We Offer, Meet the Team (CMS → Team), reviews (+ "Read our Google reviews" when `SITE_GOOGLE_REVIEWS_URL` is set), club numbers once there are past hikes / attended hikers. Texts are placeholders in `config/site.php` → `about_page` |
| Gallery | http://localhost:8080/gallery | Published albums with photos (CMS → Gallery). An album: http://localhost:8080/gallery/{slug} with lightbox (click a photo, arrows / swipe, Esc). Past hikes with an album show "See the photos" |
| Events | http://localhost:8080/events | Upcoming events (soonest first) and Past events with Load More; filter by level or month, e.g. http://localhost:8080/events?level=2 or http://localhost:8080/events?month=2026-11 |
| Event detail | http://localhost:8080/events/horch-ehden-nature-reserve (or click "View Details" on any card) | Everything about one hike: level, effort, distance, elevation, fee, seats left, itinerary, meeting points, included / what to bring, technical details, route map + GPX download, WhatsApp inquiry. Cancelled example: http://localhost:8080/events/hadath-el-jebbe-to-tannourine-el-tahta. Drafts return 404 |
| X Events | http://localhost:8080/x-events | Landing page (requirements, 3 steps, upcoming X events) and the application form http://localhost:8080/x-events/apply. Only approved hikers (or Elite) can book X events, logged in: see docs/x-events.md |
| Shop | http://localhost:8080/shop | Products with price / sale / stock badges, category filter, e.g. http://localhost:8080/shop?category=footwear. Product page (sizes, photos, Add to cart): http://localhost:8080/shop/club-t-shirt (demo data, see docs/shop.md) |
| Checkout | http://localhost:8080/checkout (from the cart) | Pickup or delivery (+$4), payment method, terms → order page `/orders/{secret}` with payment instructions / "Pay now" and the order status |
| Rank System | http://localhost:8080/rank-system | The 5 ranks with their points and rewards, points per level + early-booking and invite bonuses (with an example), leaderboard, terms. Logged in: your rank, points and progress to the next rank. All numbers come from `config/points.php` and `config/rewards.php` |
| Contact | http://localhost:8080/contact | Contact form (emailed to `SITE_EMAIL`, see Mailpit; Reply-To = sender), WhatsApp, phone, email, address + Open in Google Maps, social links |
| Hiker's Guide → Difficulty Calculator | http://localhost:8080/difficulty-calculator | Enter km, elevation gain (and loss): live effort score, level, description and points; formula, level table and limitations; upcoming hikes of that level. Shareable, e.g. http://localhost:8080/difficulty-calculator?km=8&gain=220&loss=320 |
| Hiker's Guide → Equipment Guide | http://localhost:8080/equipment | Checklists for Hiking (pick a difficulty level: more items appear for harder hikes), Snowshoeing, Rafting, Camping (e.g. http://localhost:8080/equipment#camping), "Shop →" links when the shop has products in that category. Lists in `config/equipment.php` |
| Hiker's Guide → Guides | http://localhost:8080/guides | Published articles (CMS → Guide articles), 9 per page. An article: http://localhost:8080/guides/how-much-water-to-carry-on-a-day-hike (demo data). Drafts give 404 unless you are logged into the CMS as content staff (Preview banner) |
| Cart (cart icon) | http://localhost:8080/cart | Lines with size, quantity (update / remove), subtotal; the header icon shows the item count |
| Privacy Policy (footer) | http://localhost:8080/privacy-policy | Coming soon |

A green WhatsApp button floats in the bottom-right corner of every public page and opens a chat with
`SITE_WHATSAPP` with a pre-filled message (`config/site.php` → `contact.whatsapp_message`). Leave
`SITE_WHATSAPP` empty to hide it, together with the WhatsApp links in the footer and on the homepage.

### SEO & analytics

| What | Link / where |
|---|---|
| Sitemap | http://localhost:8080/sitemap.xml (real pages plus every published event page) |
| robots.txt | http://localhost:8080/robots.txt (blocks everything except when `APP_ENV=production`) |
| Default share image | http://localhost:8080/images/og-default.jpg (placeholder, 1200×630) |
| Share preview check | view page source and look for `og:` tags, or paste the live URL into https://www.opengraph.xyz once deployed |
| Analytics | set `GOOGLE_ANALYTICS_ID=G-...` in `.env` + `php artisan config:clear`; never loaded in the CMS |

### Member accounts

| What | Link |
|---|---|
| Log in (phone + password) | http://localhost:8080/login (also the person icon in the header) |
| Activate an account | http://localhost:8080/activate: phone you booked with + a booking reference, then choose a password |
| Forgot password | http://localhost:8080/forgot-password: a 6-digit code goes to the email on the account (Mailpit) |
| My account (member dashboard) | http://localhost:8080/account: rank + progress bar to the next rank, points, hikes completed, km, leaderboard position, upcoming hikes (payment state; cancel: unpaid while bookings are open, paid until 2 days before the hike → "Refund due" in the CMS), hike history with points per hike, rewards (available / locked with what is missing / used), discounts chosen on the booking form, latest points. Demo login: `+96170000001` / `demo-pass-1` (Rima, after you activated her or on this dev DB) |

To try it with demo data: book any upcoming hike (or use a demo hiker: phpMyAdmin → `event_registrations`, take a
`phone` + `reference` pair), activate with them, then log in. No open signup: only phone numbers with a booking can
activate. Codes can go to the log instead of email with `MEMBER_CODE_CHANNEL=log` in `.env` (local only).
SMS/WhatsApp codes need a provider (not chosen yet); members without an email are pointed to WhatsApp.

### Booking (Registration)

| What | Where |
|---|---|
| Booking form | "Reserve Your Spot" (or "Register for Level 3/4") on an upcoming event page, e.g. http://localhost:8080/events/horch-ehden-nature-reserve/register |
| Level 3+ warning | any Advanced/Xtreme upcoming event, e.g. http://localhost:8080/events/x30-tannourine-to-douma/register |
| Confirmation page | where the form sends you: `/bookings/{secret}` (also linked in the email) |
| Confirmation email | Mailpit, http://localhost:8025 (only when an email is given) |
| Bookings in the database | phpMyAdmin → `hiking` → `event_registrations`, or the CMS event edit screen |

Demo data: Horch Ehden has 26 of 30 seats booked (cards show "Only 4 seats left"). The X event X30 is full
(30 awaiting approval) with 2 people on the waitlist: its cards say "Sold out" and its page offers "Join the Waitlist".
X event bookings are "Awaiting approval" and still take a seat; waitlist entries don't.

- **Booking deadline:** midnight (Lebanon time) before the hike day, or the event's "Bookings close" date in the CMS
  (Booking box). The event page shows "Bookings close …".
- **Waitlist:** on by default ("Waitlist when full" in the CMS). Raising the capacity in the CMS moves people up in
  the order they joined and emails them "A seat opened up". Untick it to show plain "Fully booked" instead.
- **Payment** (paid hikes): the hiker picks Whish Money transfer, OMT Pay transfer, Cash on the day, or Online payment.
  Whish/OMT: the booking page shows the club account + reference to put in the note, and a box for the transaction
  number; organizers see method + number in Registrations and click "Mark paid". Online: "Pay $N now" on the booking
  page (once the seat is confirmed) → test payment page with Pay / Fail → back to the booking, paid automatically.
  Wallet names/numbers are placeholders: `PAYMENT_WHISH_*` / `PAYMENT_OMT_*` in `.env`. Online payment uses the built-in
  **test gateway** when `PAYMENT_ONLINE_GATEWAY=test` (set in your local `.env`; never in production) until Whish/OMT
  give the club merchant API access. Step-by-step test and go-live guide: [docs/payments.md](docs/payments.md).
  Open decisions and known gaps: [docs/open-decisions.md](docs/open-decisions.md).
- One booking (or waitlist entry) per phone number per hike. Payment and terms texts are placeholders in
  `config/site.php` → `booking`. After pulling: `php artisan migrate` and `docker compose restart queue`.

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
("View Event" opens that event's page); it disappears when nothing is coming up.

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
| Registrations — events overview | http://localhost:8080/admin/registrations (Upcoming / Past tabs: seats, approvals, waitlist, paid, attended) | Super Admin, Admin, Guide |
| Registrations — one event | click an event there, or "Manage registrations" on the event edit screen → `/admin/events/{id}/registrations`: filter by pickup point / status / search, approve, edit, cancel, mark paid, attendance, WhatsApp list, Export CSV | Admin: everything · Guide: view, WhatsApp list, CSV, attendance |
| Registrations — edit a booking | "Edit" on a row → `/admin/registrations/{id}/edit` | Super Admin, Admin |
| Members | http://localhost:8080/admin/members: every hiker (one per phone) with rank, points, hikes, last booking and account status; filter by account; "Invite to activate" / "Password link" (copy or Send on WhatsApp), Deactivate / Reactivate | Super Admin, Admin |
| Rewards used | http://localhost:8080/admin/members/rewards: every reward used (booking, discount, hand-over notes, given back), filter by reward | Super Admin, Admin |
| X applications | http://localhost:8080/admin/x-applications: review queue (waiting first), every answer, internal notes, Approve / Reject, "Send access link" (WhatsApp), Revoke access. Demo data has 2 waiting | Super Admin, Admin |
| Shop products | http://localhost:8080/admin/products: list with stock (red when low), search / category filter, "+ New product" (sizes and stock, price, sale price, tags, photos, show in shop), Edit, Delete; "Categories" → http://localhost:8080/admin/product-categories | Super Admin, Admin |
| Shop orders | http://localhost:8080/admin/orders: filter by status, search name / phone / reference; an order → items, customer + WhatsApp link, delivery, steps (Being prepared → Ready → Completed), Mark paid / unpaid, Cancel (reason, email, stock back; paid → "Refund due") | Super Admin, Admin |
| Points — hikers by rank | http://localhost:8080/admin/points (rank, tier, points, hikes, km; search by name or phone) | Super Admin, Admin |
| Points — one hiker | click a hiker → history (every line with reason), bookings, rewards (uses, "Mark handed over" for gift card / winter bonus), "Adjust points" form | Super Admin, Admin |
| Guide articles | http://localhost:8080/admin/articles: filter Published / Scheduled / Drafts, "+ New article" (Markdown text, author, cover photo, publish date: future = scheduled) → http://localhost:8080/admin/articles/create, Edit, Delete | Super Admin, Admin, Content Editor |
| Gallery | http://localhost:8080/admin/albums: albums with cover and photo count, "+ New album" → http://localhost:8080/admin/albums/create (link to a hike, upload up to 20 photos per batch, Cover / Remove per photo, Show in the gallery). From a hike: http://localhost:8080/admin/albums/create?event=1 | Super Admin, Admin, Content Editor |
| Homepage slides | http://localhost:8080/admin/hero-slides: the homepage carousel (heading, text, button text + link, background photo, order, shown / hidden), "+ New slide" → http://localhost:8080/admin/hero-slides/create. Without an active slide the homepage shows the placeholders from `config/site.php` | Super Admin, Admin, Content Editor |
| Team | http://localhost:8080/admin/team: name, role, bio, portrait, order, shown / hidden (for the About page), "+ New team member" → http://localhost:8080/admin/team/create | Super Admin, Admin, Content Editor |
| Subscribers | http://localhost:8080/admin/subscribers: newsletter sign-ups with counts, filter (confirmed / awaiting / phone / unsubscribed), search email or phone, Download CSV (http://localhost:8080/admin/subscribers/export), Unsubscribe, Delete | Super Admin, Admin |

To try the Guide view of registrations, create a user with the Guide role in Staff & roles and log in as them.
Attendance buttons appear from the hike day (Lebanon time): to test them now, set an event's date to today in the CMS.
Cancelling a booking emails the hiker (Mailpit) and gives the seat to the first person on the waitlist.
**Notifications** (all in Mailpit): cancelling a hike (Event edit → Cancel event) cancels every booking and emails each hiker the reason (paid ones become "Refund due"); "Send update to hikers" on a hike's registrations page emails everyone booked (waitlist included) and shows the update on the hike and booking pages; changing a published hike's date sends an automatic update. Reminders go out the day before at 17:00 Lebanon time (scheduler, every hour); to test now: set a hike's date to tomorrow, then run `docker compose exec app php artisan events:send-reminders` after 17:00, or lower `reminder_hour` in `config/site.php`.

**Points:** a hiker is one phone number; all their bookings and points belong to them. Points are added when a
confirmed booking is marked **Attended** (and paid, unless the hike is free): Level 1/2/3/4 = 8/14/22/35, +4 for
booking 5+ days before the hike day, +10 to the friend whose number a new hiker gave ("Invited by") once that
hiker completes a first hike. Clearing attendance, marking unpaid or cancelling adds a reversing line. Values are in
`config/points.php`; after changing them run `docker compose exec app php artisan points:sync`. Demo data: the two
past hikes have 12 checked-in demo hikers (e.g. Rima Haddad).

**Rank tiers:** Pathfinder 0+, Trekker 80+, Mountaineer 200+, Highlander 400+, Elite 700+ points (`config/points.php` →
`tiers`). Equal points are ranked by km of completed hikes. A hiker's page shows their tier, rank and points to the next
tier. To see a higher tier quickly, add points with "Adjust points".

Uploaded images are served from `http://localhost:8080/storage/...` (files live in `storage/app/public`).

## Demo data

`docker compose exec app php artisan migrate:fresh --seed` creates 6 sample events (published, draft,
cancelled, past and X events) so every screen has something to show. Each demo event has a sample route
map but no GPX file to download; upload a real `.gpx` in the CMS (Route section) to test the download.
Re-run it to reset them. After pulling this feature run `docker compose exec app php artisan migrate`.
It also adds 6 shop products without photos (`DemoShopSeeder`, skipped when products exist; run it alone with
`docker compose exec app php artisan db:seed --class=DemoShopSeeder`).

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
| Event detail page | https://app.clickup.com/t/z8q7hbmpeq |
| Registration / booking flow | https://app.clickup.com/t/z8q7hbmpet |
| Seat capacity, waitlist & sold-out status | https://app.clickup.com/t/z8q7hbmpeu |
| Meeting points & pickup times | https://app.clickup.com/t/z8q7hbmper |
| Manage registrations & attendance | https://app.clickup.com/t/z8q7hbmpfq |
| Points engine | https://app.clickup.com/t/z8q7hbmpf3 |
| Rank tiers | https://app.clickup.com/t/z8q7hbmpf4 |
| Login & account activation | https://app.clickup.com/t/z8q7hbmpf1 |
| Event payments | https://app.clickup.com/t/z8q7hbmpev |
| Member dashboard | https://app.clickup.com/t/z8q7hbmpf2 |
| Public leaderboard | https://app.clickup.com/t/z8q7hbmpf7 |
| Rewards per tier & redemption | https://app.clickup.com/t/z8q7hbmpf5 |
| Manage members, points & rewards | https://app.clickup.com/t/z8q7hbmpfr |
| Difficulty Calculator page | https://app.clickup.com/t/z8q7hbmpez |
| X Events (landing, application form, review, manage) | https://app.clickup.com/t/z8q7hbmpf9 · https://app.clickup.com/t/z8q7hbmpfa · https://app.clickup.com/t/z8q7hbmpfb · https://app.clickup.com/t/z8q7hbmpft |
| Event reminders & notifications | https://app.clickup.com/t/z8q7hbmpex |
| Shop (listing, product page, cart & checkout, payments & orders, CMS) | https://app.clickup.com/t/z8q7hbmpfc · https://app.clickup.com/t/z8q7hbmpfd · https://app.clickup.com/t/z8q7hbmpfe · https://app.clickup.com/t/z8q7hbmpff · https://app.clickup.com/t/z8q7hbmpfu |
| Manage content (CMS: articles, gallery, homepage slides, team, subscribers) | https://app.clickup.com/t/z8q7hbmpfv |
| Content pages (About, Contact, Guides, Equipment guide, Photo gallery) | https://app.clickup.com/t/z8q7hbmpfg · https://app.clickup.com/t/z8q7hbmpfh · https://app.clickup.com/t/z8q7hbmpfj · https://app.clickup.com/t/z8q7hbmpfk · https://app.clickup.com/t/z8q7hbmpfm |
| Rank System info page | https://app.clickup.com/t/z8q7hbmpf6 |
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
