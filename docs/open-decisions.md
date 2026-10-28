# Open decisions, placeholders and known gaps

Things found while building the site that need a decision from the club, real content, or a later feature.
Update this file when one is settled. Last reviewed: 2026-10-24.

## Decisions needed

| Topic | Why it matters | Where it plugs in |
|---|---|---|
| **Online payment provider access** (Whish Money / OMT Pay) | Chosen provider, but neither has a public merchant API: the club needs a merchant agreement to get API docs + credentials. Until then online payment runs only on the test gateway. | [docs/payments.md](payments.md) → "Going live" |
| **SMS / WhatsApp provider** (e.g. Twilio, WhatsApp Business API) | Password-reset codes can only go by email today; members without an email must ask on WhatsApp. Booking confirmations by SMS/WhatsApp need it too. | `config/members.php` → `code_channels` (add a class implementing `App\Domain\Members\Codes\CodeChannel`) |
| **Branding, hosting, languages** | Club name, logo, colours, domain, Arabic (RTL)? The site runs on placeholders. Hosting options & prices: [docs/hosting.md](hosting.md). | `resources/css/app.css`, logo component, `config/site.php`, `public/images/og-default.jpg` |
| **Legal texts** (privacy policy, terms) | Postponed. The booking form shows short placeholder terms and links to a "coming soon" privacy page. A cookie banner is needed if Google Analytics is enabled for EU visitors. | `config/site.php` → `booking.terms`, route `legal.privacy` |

## Choices made that the club may want to revisit

- **Public leaderboard privacy:** names are shown as first name + last initial ("Rima H."), never phone numbers; only
  hikers with points are listed; members can hide themselves on My account ("Hide me"). Full names or an opt-in
  instead of opt-out are one-line changes (`Hiker::publicName()`, `hikers.show_on_leaderboard` default).
- **Leaderboard period:** points are counted for all time (no seasons / yearly reset yet).
- **Rank System terms** (`/rank-system`, `resources/views/site/rank-system.blade.php`): written from how the system
  works today, plus two promises to confirm: points don't expire, and the club may correct points and change the
  system (announced on the page). Rewards can't be exchanged for cash or transferred.

## Placeholders to replace before launch

- Club contact details and social handles: `SITE_*` in `.env` (`config/site.php`).
- Club Whish / OMT accounts shown to hikers: `PAYMENT_WHISH_*`, `PAYMENT_OMT_*` in `.env`.
- Payment note for old bookings without a method and the booking terms: `config/site.php` → `booking`.
- Default share image `public/images/og-default.jpg` and homepage texts in `config/site.php`.
- Homepage carousel: add real slides with photos in CMS → Homepage slides (until then the 3 placeholder slides show).
- **About page:** the story, founding year (`SITE_FOUNDED_YEAR`, 2018 placeholder), "What We Offer" texts and the 3
  testimonials are invented placeholders (`config/site.php` → `about_page`); replace the testimonials with real quotes
  (with the hikers' permission). Team members: CMS → Team. Set `SITE_GOOGLE_REVIEWS_URL` to show "Read our Google
  reviews". Showing live Google reviews on the page would need a Google Places API key (not built).
- **Equipment guide** lists (`config/equipment.php`) are a first draft: guides should check them, especially rafting
  (operator, river) and what the club rents (snowshoes, tents).
- **Contact:** the club's real map link (`SITE_MAPS_URL`) and address; contact-form messages go to `SITE_EMAIL` (not
  stored on the site, no CMS inbox).

## Known gaps (planned in other ClickUp tasks)

- **Cancelled events don't notify registered hikers yet.** `EventCancelled` is raised but nothing listens to it.
  → "Event reminders & notifications".
- **Notifications are email only.** Cancelling a hike cancels its bookings and emails every hiker (paid ones become
  "Refund due"); organizers' updates and date changes are emailed and shown on the hike/booking pages; a reminder goes
  out the day before at 17:00 (`config/site.php` → `booking.reminder_*`). Hikers without an email get nothing
  automatically: organizers use the WhatsApp list / group. An SMS/WhatsApp provider would cover them.
- **Refunds** are paid back by hand (an online provider's refund API can be added with the real gateway).
- **Refunds are paid back by hand.** Cancellation policy (decided 2026-10-23): members cancel from My account, unpaid
  bookings until bookings close, **paid bookings until 2 days before the hike** (Saturday hike → until Wednesday 23:59,
  Lebanon time; `config/site.php` → `booking.paid_cancellation_days`). A cancelled paid booking shows **Refund due** in
  CMS → Registrations until an organizer pays the hiker back and clicks **Mark refunded**. No cancellation fee.
  Changing a booking (pickup point, details) is still done by organizers in the CMS.
- **Rewards** work as described in [docs/rewards.md](rewards.md). Still to define by the club: what the Highlander
  "gift card" and "winter bonus" are (organizers hand them over and record it), and the "invite-only events" for
  Elite (no such events exist yet, shown only). Interpretation used: "after 5 paid hikes" for Trekker = 5 completed
  hikes (attended), free or paid.
- **Waitlist promotion** happens when an organizer raises the capacity or cancels a booking; there is no automatic
  expiry for unpaid bookings.
- **X Events** work as in [docs/x-events.md](x-events.md): apply once, review in the CMS, then approved (or Elite)
  members book and are confirmed directly. To confirm: requirement texts, gear list, ages, and whether approved hikers
  still need per-event confirmation.
- **Account help without SMS:** hikers who lost their booking reference, or have no email for a password reset, ask
  on WhatsApp; organizers send them an **invite link** (7 days) or a **password link** (24 hours, works once) from
  CMS → Members, with a "Send on WhatsApp" button. An SMS/WhatsApp provider would let hikers do this themselves.

- **Shop** works as in [docs/shop.md](shop.md). To confirm by the club: delivery fee (flat $4 placeholder, one
  price for all of Lebanon) and delivery areas/times, pickup arrangement and texts (`config/shop.php`), who receives
  "new order" emails (`SHOP_NOTIFY_EMAIL`), the real products and photos, and the shop terms. Cancelled paid orders
  are refunded by hand ("Refund due"). Stock is not held while items sit in a cart, only once the order is placed;
  unpaid orders don't expire, so staff cancel them by hand to free the stock. No shop discounts / rank rewards yet.

- **Content** (articles, gallery, homepage slides, team, subscribers) is edited in the CMS, see
  [docs/content.md](content.md), and shown on /guides, /gallery and /about. Choices to confirm: the subscriber list is
  limited to Admins (personal data; Content Editors don't see it), articles have no topic/category filter yet (add one
  if the Guides list grows), photos have no captions, and gallery uploads are capped by the server at
  20 photos / 25 MB per batch (raise `upload_max_filesize`, `post_max_size`, `max_file_uploads` and nginx
  `client_max_body_size` to change it).

## Local development notes

- The local `.env` has `PAYMENT_ONLINE_GATEWAY=test` (online payments via the simulator, see docs/payments.md) and can
  use `MEMBER_CODE_CHANNEL=log` to write reset codes to `storage/logs/laravel.log` instead of emailing them.
- After changing `config/points.php` run `php artisan points:sync`; after `config/difficulty.php` run
  `php artisan events:recalculate-difficulty`.
- Restart the queue container after pulling (`docker compose restart queue`), or emails may use old templates.
