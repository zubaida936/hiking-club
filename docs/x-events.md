# X Events

The club's most demanding hikes, open only to approved hikers. Code: `app/Domain/XEvents`; form options:
`config/xevents.php`.

## How it works

1. **Apply once:** `/x-events/apply` (also from the X Events page and from any X event's booking page). Guests can
   apply: the WhatsApp number identifies the hiker. Under 18 needs a parent or guardian. One application at a time;
   a new one is possible after "Not approved" or "Access revoked".
2. **Manual review:** CMS → **X applications** (staff with "Review applications": Super Admin, Admin). Waiting ones
   come first, oldest first. Each shows every answer, missing gear, the hiker's record with the club, internal notes.
   **Approve** or **Reject**, with an optional note.
3. **Personal access:** on an approved application, **Send access link** opens WhatsApp with a message to the hiker:
   - no account yet → a personal account invite (7 days), then they log in and book;
   - already a member → the X Events page, log in and book.

## Who can book X events

- A **logged-in member booking for themselves** (own phone) who is **approved**, or who reached **Elite** (rank reward).
- Their X bookings are **confirmed straight away**. Approval lasts for all X events.
- **Reassessing per event:** cancel that person's booking in CMS → Registrations. To take access away completely:
  **Revoke access** on their application (their future X bookings are refused; existing ones stay until cancelled).
- Everyone else sees "X Events are for approved hikers" with **Apply** / **Log in** buttons instead of the form.

## Changed behaviour (2026-10-23)

Before this feature anyone could book an X event and the booking waited as "Awaiting approval". Bookings made that way
still show "Awaiting approval" and can be approved in CMS → Registrations; new ones can't be created that way.

## To confirm with the club

- Requirement texts on the landing page (endurance 20 km+/1,000 m+, 8 h steady pace…), the gear list, frequency and
  pace options (`config/xevents.php`), and the minimum age (14) / guardian age (18).
- Whether approved hikers should still need per-event confirmation (today: no, confirmed directly).
