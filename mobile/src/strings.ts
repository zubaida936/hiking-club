/**
 * Every text the app shows, in one place (English only in v1; Arabic can be added as a second file later).
 * Texts that come from the API (hike details, club rule messages) are shown as they arrive.
 */
export const strings = {
  tabs: {
    hikes: 'Hikes',
    account: 'Account',
  },
  hikes: {
    title: 'Upcoming hikes',
    empty: 'No hikes planned right now. Check back soon!',
    seatsLeft: (count: number) => (count === 1 ? '1 seat left' : `${count} seats left`),
    soldOut: 'Fully booked',
    waitlist: 'Waitlist open',
    cancelled: 'Cancelled',
  },
  account: {
    title: 'My account',
    comingSoon: 'Logging in from the app is coming soon.',
  },
  update: {
    title: 'Please update the app',
    text: 'This version of the app is too old to keep working. Update it to continue.',
    button: 'Update',
    noStore: 'Install the latest version from the store.',
  },
  errors: {
    offline: "Can't reach the server. Check your internet connection and try again.",
    generic: 'Something went wrong. Please try again.',
    retry: 'Try again',
  },
} as const;
