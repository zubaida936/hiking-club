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
  expect(appRouteFor('http://localhost:8080/guides/how-much-water-to-carry-on-a-day-hike')).toBe('/guides/how-much-water-to-carry-on-a-day-hike');
});

it('leaves links without an app screen to the browser', () => {
  expect(appRouteFor('https://hikingclub.lb/events/archive')).toBeNull();
  expect(appRouteFor('https://hikingclub.lb/gallery')).toBeNull();
});
