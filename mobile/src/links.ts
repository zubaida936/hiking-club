import type { Href } from 'expo-router';

/**
 * The app screen for a website link (homepage slide buttons, shared links, links opening the installed app), or
 * null when the app has no such screen yet: then the link opens in the browser. Add a line here when a matching
 * screen is built, and the path to `app.config.ts` + Laravel's config/mobile.php → app_links.paths.
 */
export function appRouteFor(url: string): Href | null {
  let path: string;
  try {
    path = new URL(url, 'https://site.invalid').pathname.replace(/\/+$/, '') || '/';
  } catch {
    return null;
  }

  const hike = path.match(/^\/events\/([a-z0-9-]+)$/);
  if (hike && hike[1] !== 'archive') return `/events/${hike[1]}`;
  if (path === '/events') return '/hikes';
  if (path === '/events/archive') return '/past-hikes';
  const booking = path.match(/^\/events\/([a-z0-9-]+)\/register$/);
  if (booking) return `/book/${booking[1]}`;
  const myBooking = path.match(/^\/bookings\/([A-Za-z0-9]+)$/);
  if (myBooking) return `/bookings/${myBooking[1]}`;
  const product = path.match(/^\/shop\/([a-z0-9-]+)$/);
  if (product) return `/shop/${product[1]}`;
  if (path === '/shop') return '/shop';
  if (path === '/cart') return '/cart';
  if (path === '/checkout') return '/checkout';
  if (path === '/x-events' || path === '/x-events/apply/sent') return '/x-events';
  if (path === '/x-events/apply') return '/x-events/apply';
  if (path === '/difficulty-calculator') return '/difficulty-calculator';
  if (path === '/rank-system') return '/rank-system';
  if (path === '/leaderboard') return '/leaderboard';
  if (path === '/equipment') return '/equipment';
  if (path === '/contact') return '/contact';
  if (path === '/about') return '/about';
  if (path === '/gallery') return '/gallery';
  const album = path.match(/^\/gallery\/([a-z0-9-]+)$/);
  if (album) return `/gallery/${album[1]}`;
  if (path === '/guides') return '/guides';
  const guide = path.match(/^\/guides\/([a-z0-9-]+)$/);
  if (guide) return `/guides/${guide[1]}`;
  const order = path.match(/^\/orders\/([A-Za-z0-9]+)$/);
  if (order) return `/orders/${order[1]}`;
  if (path === '/') return '/';
  if (path === '/account' || path === '/login') return '/account';
  if (path === '/account/profile') return '/edit-profile';
  if (path === '/account/delete') return '/delete-account';
  if (path === '/activate') return '/activate';
  if (path === '/forgot-password') return '/forgot-password';

  return null;
}
