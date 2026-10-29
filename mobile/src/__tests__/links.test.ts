import { appRouteFor } from '@/links';

it('opens website links on the matching app screen', () => {
  expect(appRouteFor('http://localhost:8080/events/horch-ehden')).toBe('/events/horch-ehden');
  expect(appRouteFor('https://hikingclub.lb/events/')).toBe('/hikes');
  expect(appRouteFor('/events')).toBe('/hikes');
  expect(appRouteFor('https://hikingclub.lb/')).toBe('/');
  expect(appRouteFor('https://hikingclub.lb/account')).toBe('/account');
});

it('leaves links without an app screen to the browser', () => {
  expect(appRouteFor('https://hikingclub.lb/events/archive')).toBeNull();
  expect(appRouteFor('https://hikingclub.lb/shop/club-t-shirt')).toBeNull();
  expect(appRouteFor('https://hikingclub.lb/x-events')).toBeNull();
});
