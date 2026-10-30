import { redirectSystemPath } from '@/app/+native-intent';
import { appRouteFor } from '@/links';

it('opens website links on the matching app screen', () => {
  expect(appRouteFor('http://localhost:8080/events/horch-ehden')).toBe('/events/horch-ehden');
  expect(appRouteFor('https://hikingclub.lb/events/')).toBe('/hikes');
  expect(appRouteFor('/events')).toBe('/hikes');
  expect(appRouteFor('https://hikingclub.lb/')).toBe('/');
  expect(appRouteFor('https://hikingclub.lb/account')).toBe('/account');
  expect(appRouteFor('https://hikingclub.lb/shop')).toBe('/shop');
  expect(appRouteFor('https://hikingclub.lb/shop/club-t-shirt')).toBe('/shop/club-t-shirt');
  expect(appRouteFor('https://hikingclub.lb/orders/AbC123xyz')).toBe('/orders/AbC123xyz');
  expect(appRouteFor('https://hikingclub.lb/x-events')).toBe('/x-events');
  expect(appRouteFor('https://hikingclub.lb/x-events/apply/')).toBe('/x-events/apply');
  expect(appRouteFor('https://hikingclub.lb/difficulty-calculator')).toBe('/difficulty-calculator');
  expect(appRouteFor('https://hikingclub.lb/rank-system')).toBe('/rank-system');
  expect(appRouteFor('https://hikingclub.lb/leaderboard')).toBe('/leaderboard');
  expect(appRouteFor('https://hikingclub.lb/guides')).toBe('/guides');
  expect(appRouteFor('https://hikingclub.lb/equipment')).toBe('/equipment');
  expect(appRouteFor('https://hikingclub.lb/contact')).toBe('/contact');
  expect(appRouteFor('https://hikingclub.lb/about')).toBe('/about');
  expect(appRouteFor('https://hikingclub.lb/gallery')).toBe('/gallery');
  expect(appRouteFor('https://hikingclub.lb/gallery/horsh-ehden')).toBe('/gallery/horsh-ehden');
  expect(appRouteFor('http://localhost:8080/guides/how-much-water-to-carry-on-a-day-hike')).toBe('/guides/how-much-water-to-carry-on-a-day-hike');
  // Links from emails and the website's account pages.
  expect(appRouteFor('https://hikingclub.lb/events/horch-ehden/register')).toBe('/book/horch-ehden');
  expect(appRouteFor('https://hikingclub.lb/bookings/Ab12Cd34')).toBe('/bookings/Ab12Cd34');
  expect(appRouteFor('https://hikingclub.lb/checkout')).toBe('/checkout');
  expect(appRouteFor('https://hikingclub.lb/x-events/apply/sent')).toBe('/x-events');
  expect(appRouteFor('https://hikingclub.lb/login')).toBe('/account');
  expect(appRouteFor('https://hikingclub.lb/account/profile')).toBe('/edit-profile');
  expect(appRouteFor('https://hikingclub.lb/account/delete')).toBe('/delete-account');
  expect(appRouteFor('https://hikingclub.lb/activate')).toBe('/activate');
  expect(appRouteFor('https://hikingclub.lb/forgot-password')).toBe('/forgot-password');
});

it('leaves links without an app screen to the browser', () => {
  expect(appRouteFor('https://hikingclub.lb/events/archive')).toBeNull();
  // Signed links from the CMS and the public delete-account page (Google Play) stay on the website.
  expect(appRouteFor('https://hikingclub.lb/activate/invite/12?signature=abc')).toBeNull();
  expect(appRouteFor('https://hikingclub.lb/password/set/7?signature=abc')).toBeNull();
  expect(appRouteFor('https://hikingclub.lb/delete-account')).toBeNull();
});

describe('links that open the app', () => {
  it('turns website links into app screens', () => {
    expect(redirectSystemPath({ path: 'https://hikingclub.lb/events/horch-ehden', initial: true })).toBe('/events/horch-ehden');
    expect(redirectSystemPath({ path: 'https://hikingclub.lb/events', initial: false })).toBe('/hikes');
    expect(redirectSystemPath({ path: 'https://hikingclub.lb/account/profile', initial: true })).toBe('/edit-profile');
    // A path the app claims but has no screen for: Home.
    expect(redirectSystemPath({ path: 'https://hikingclub.lb/events/archive', initial: true })).toBe('/');
  });

  it("keeps the app's own links, and maps website paths sent to them", () => {
    expect(redirectSystemPath({ path: 'hikingclub://my-points', initial: true })).toBe('hikingclub://my-points');
    expect(redirectSystemPath({ path: '/my-rewards', initial: false })).toBe('/my-rewards');
    // Expo Go dev links (exp://<ip>:8082/--/<path>) work the same, so website paths can be tried in Expo Go.
    expect(redirectSystemPath({ path: 'exp://192.168.1.4:8082/--/account/profile', initial: true })).toBe('/edit-profile');
    expect(redirectSystemPath({ path: 'exp://192.168.1.4:8082/--/my-hikes', initial: true })).toBe('exp://192.168.1.4:8082/--/my-hikes');
  });

  it('never throws', () => {
    expect(redirectSystemPath({ path: 'https://', initial: true })).toBe('/');
  });
});
