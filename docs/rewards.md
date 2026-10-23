# Rank rewards

What each rank unlocks and how rewards are used. Catalog: `config/rewards.php`; code: `app/Domain/Rewards`.

## The rewards

| Rank | Reward | How it is used |
|---|---|---|
| Trekker (80+) | 50% off one hike, after 5 completed hikes | Member picks it on the booking form → the amount to pay is halved |
| Mountaineer (200+) | One free Moderate (Level 2) hike | Booking form, Level 2 hikes only → nothing to pay |
| Mountaineer | +5 bonus points | Added automatically once, the moment the hiker reaches Mountaineer |
| Highlander (400+) | 15% off two Challenging (Level 3) hikes | Booking form, Level 3 hikes, 2 uses |
| Highlander | Gift card | Organizer hands it over and records it: CMS → Points → hiker → **Mark handed over** |
| Highlander | Winter bonus | Same as the gift card (what it is is up to the club) |
| Elite (700+) | One free hike | Booking form, any paid hike |
| Elite | Eligible for Xtreme hikes | The member's own X event bookings are **confirmed straight away** (no approval) |
| Elite | Invite-only events | Shown only; invite-only events don't exist yet |

## Rules

- **Personal:** a discount only works on a booking made by the logged-in member with their own phone number.
- **One per booking** (no stacking); each reward has a number of uses (1 unless stated).
- **Cancelling** a booking gives its reward back (the use is "released").
- A reward is unlocked while the hiker's points reach its rank (and its condition is met). If points drop below
  (reversed hike, adjustment) unused rewards lock again; uses already made stay recorded.
- "Completed hikes" for the Trekker condition = confirmed bookings marked Attended (free or paid).
- Points for a hike are the same whether it was paid in full or with a reward; a fully free booking counts like a
  free hike (no payment needed for points).

## Where to see them

- **Member:** My account → Rewards: available (and uses left), locked (with what's missing), used.
- **Booking form:** "Use a reward?" with the price after each reward (only rewards valid for that hike).
- **Organizers:** CMS → Points → hiker → Rewards (uses, dates, bookings, notes, hand-over form);
  CMS → Registrations shows "🎁 Reward: pays $X instead of $Y" on discounted bookings.

## Changing rewards

Edit `config/rewards.php` (label, rank, percent, uses, levels, condition). Add new keys instead of renaming existing
ones: past uses are stored under the key. Kinds: `discount`, `bonus_points`, `perk`, `xtreme`, `info`.
