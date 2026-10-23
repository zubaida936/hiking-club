# Open decisions, placeholders and known gaps

Things found while building the site that need a decision from the club, real content, or a later feature.
Update this file when one is settled. Last reviewed: 2026-10-23.

## Decisions needed

| Topic | Why it matters | Where it plugs in |
|---|---|---|
| **Online payment provider access** (Whish Money / OMT Pay) | Chosen provider, but neither has a public merchant API: the club needs a merchant agreement to get API docs + credentials. Until then online payment runs only on the test gateway. | [docs/payments.md](payments.md) → "Going live" |
| **SMS / WhatsApp provider** (e.g. Twilio, WhatsApp Business API) | Password-reset codes can only go by email today; members without an email must ask on WhatsApp. Booking confirmations by SMS/WhatsApp need it too. | `config/members.php` → `code_channels` (add a class implementing `App\Domain\Members\Codes\CodeChannel`) |
| **Branding, hosting, languages** | Club name, logo, colours, domain, Arabic (RTL)? The site runs on placeholders. | `resources/css/app.css`, logo component, `config/site.php`, `public/images/og-default.jpg` |
| **Legal texts** (privacy policy, terms) | Postponed. The booking form shows short placeholder terms and links to a "coming soon" privacy page. A cookie banner is needed if Google Analytics is enabled for EU visitors. | `config/site.php` → `booking.terms`, route `legal.privacy` |

## Placeholders to replace before launch

- Club contact details and social handles: `SITE_*` in `.env` (`config/site.php`).
- Club Whish / OMT accounts shown to hikers: `PAYMENT_WHISH_*`, `PAYMENT_OMT_*` in `.env`.
- Payment note for old bookings without a method and the booking terms: `config/site.php` → `booking`.
- Default share image `public/images/og-default.jpg` and homepage texts in `config/site.php`.

## Known gaps (planned in other ClickUp tasks)

- **Cancelled events don't notify registered hikers yet.** `EventCancelled` is raised but nothing listens to it.
  → "Event reminders & notifications".
- **Refunds are manual.** Cancelling a paid booking (or a whole event) doesn't refund; organizers refund the hiker
  themselves. An online provider's refund API can be added with the real gateway.
- **Hikers can't cancel or change a booking themselves**; they ask on WhatsApp and an organizer does it in the CMS.
  → "Member dashboard".
- **Waitlist promotion** happens when an organizer raises the capacity or cancels a booking; there is no automatic
  expiry for unpaid bookings.
- **X event approval** of bookings is per booking in CMS → Registrations; the X application process itself is in
  "X Events application form" / "Manage X applications".
- **Activation needs a booking reference.** Hikers who lost theirs ask on WhatsApp; organizers can find it in CMS →
  Registrations.

## Local development notes

- The local `.env` has `PAYMENT_ONLINE_GATEWAY=test` (online payments via the simulator, see docs/payments.md) and can
  use `MEMBER_CODE_CHANNEL=log` to write reset codes to `storage/logs/laravel.log` instead of emailing them.
- After changing `config/points.php` run `php artisan points:sync`; after `config/difficulty.php` run
  `php artisan events:recalculate-difficulty`.
- Restart the queue container after pulling (`docker compose restart queue`), or emails may use old templates.
