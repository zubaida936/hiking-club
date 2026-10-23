# Event payments

How hikers pay for paid hikes, how to test it locally, and what is needed to take real online payments.
Code: `app/Domain/Payments`, settings: `config/payments.php` + the `PAYMENT_*` variables in `.env`.

## The four ways to pay

When a hiker books a paid hike they choose one (free hikes skip the question):

| Method | What the hiker does | Who marks it paid |
|---|---|---|
| **Whish Money transfer** | Sends the fee to the club's Whish account, writes the booking reference in the transfer note, and types the transaction number on their booking page. | An organizer, after checking the transfer: CMS → Registrations → **Mark paid** |
| **OMT Pay transfer** | Same, with the club's OMT account. | An organizer (**Mark paid**) |
| **Cash on the day** | Pays the organizers at the pickup point. | An organizer (**Mark paid**) |
| **Online payment** | Clicks **Pay $N now** on the booking page (once the seat is confirmed) and pays on the provider's page. | Automatic |

- Payment details only appear once the seat is confirmed: bookings awaiting approval (X events) and waitlist entries
  see "You'll pay once your seat is confirmed".
- Only paid bookings earn points (free hikes always do): `config/points.php` → `paid_only`.
- Organizers see the method and the transaction number in CMS → Registrations (Paid column) and in the CSV export.
## Cancellations and refunds

- Hikers cancel from **My account**: unpaid bookings while bookings are open, **paid bookings until 2 days before the
  hike** (Saturday hike → Wednesday 23:59, Lebanon time). The exact last moment is shown on My account and on the
  booking page. Setting: `config/site.php` → `booking.paid_cancellation_days`.
- After that, hikers message the club on WhatsApp; organizers can still cancel any booking in the CMS.
- Refunds are paid back **by hand** (Whish, OMT, cash or the provider's dashboard). A cancelled paid booking shows
  **Refund due** in CMS → Registrations (and "N refunds due" in the overview) until an organizer clicks
  **Mark refunded**. The hiker's cancellation email says the refund is coming.

## Settings (`.env`)

```dotenv
# Club wallet accounts shown to hikers (PLACEHOLDERS until the club gives the real ones)
PAYMENT_WHISH_NAME="Hiking Club"
PAYMENT_WHISH_NUMBER="+961 70 000 000"
PAYMENT_OMT_NAME="Hiking Club"
PAYMENT_OMT_NUMBER="+961 70 000 000"

# Online payment: empty = hidden; "test" = built-in simulator (local / staging only)
PAYMENT_ONLINE_GATEWAY=
```

Run `docker compose exec app php artisan config:clear` after changing them. To hide a manual method (e.g. OMT),
remove its key from `methods` in `config/payments.php`.

## Testing online payments with the test gateway

The test gateway (`app/Domain/Payments/Gateways/TestGateway.php`) pretends to be a payment provider: instead of a
real payment page it shows a page of our own site with **Pay** and **Fail** buttons. No money moves. It is refused in
production (`APP_ENV=production`): the online option is hidden and its page returns 404.

1. In `.env` set `PAYMENT_ONLINE_GATEWAY=test` (already set in the local development `.env`), then
   `docker compose exec app php artisan config:clear`.
2. Open an upcoming **paid** hike, e.g. http://localhost:8080/events/horch-ehden-nature-reserve, click
   **Reserve Your Spot**, fill the form and choose **Online payment**.
3. On the booking page click **Pay $N now**. You land on the yellow "Test payment" page.
4. Click **Pay**: you return to the booking page with "Payment received. Thank you!" and "✓ Paid (Online payment)".
   In CMS → Registrations the booking is paid, with a `TEST-…` reference.
5. Or click **Fail**: you return with "The payment did not go through…"; the booking stays unpaid and **Pay $N now**
   can be tried again.

Webhooks (provider → site notifications) are tested in `tests/Feature/PaymentsTest.php`: `POST /payments/webhook/test`
with `external_id`. The site never trusts what a notification says; it always asks the gateway for the status.

## Going live with Whish Money or OMT Pay

**Finding (October 2026):** neither Whish Money nor OMT Pay publishes a merchant/developer API. Online merchants get
API access through a business (merchant) agreement, which comes with their API documentation and test credentials.
Lebanese shops without it fall back to manual wallet transfers, which is what the Whish/OMT transfer methods above do.

Steps once the club has a merchant agreement:

1. Get from the provider: API documentation, sandbox and live credentials, and the webhook / callback rules.
2. Add a class in `app/Domain/Payments/Gateways/` implementing `PaymentGateway`:
   - `startCheckout(Payment $payment, string $returnUrl): string`: create the payment at the provider (amount
     `$payment->amount_cents`, currency `$payment->currency`, our id `$payment->external_id`, return URL) and return
     the provider page to send the hiker to.
   - `fetchStatus(Payment $payment): array`: ask the provider for the status of `$payment->external_id` and return
     `['status' => PaymentStatus::Succeeded|Failed|Pending, 'reference' => provider transaction id]`.
3. Register it in `config/payments.php` → `gateways` (e.g. `'whish' => WhishGateway::class`) and put its credentials
   in `.env` (read them in `config/payments.php`, never commit them).
4. Give the provider the webhook URL `https://<site>/payments/webhook/whish` (CSRF-exempt; it only identifies the
   payment, the status is fetched again).
5. Test against the provider's sandbox with `PAYMENT_ONLINE_GATEWAY=whish`, then set it in production.

Each payment remembers which gateway created it, so switching gateways never breaks payments in progress.
