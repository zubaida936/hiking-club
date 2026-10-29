import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, sampleBooking, sampleEvent, sampleMember } from '@/test-utils/fake-api';

// Logged in: the stored token is always there.
jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

const page = (data: unknown[], current = 1, last = 1) => ({ status: 200, body: { data, links: {}, meta: { current_page: current, last_page: last, total: data.length } } });
const me = { 'GET /me': { status: 200, body: { data: sampleMember } } };

it('lists my upcoming hikes on the account tab and opens one', async () => {
  const calls = fakeApi({
    ...appConfigRoute,
    ...me,
    'GET /me/bookings': page([
      sampleBooking(),
      sampleBooking({
        token: 'waitlist-token',
        reference: 'W8AIT123',
        status: { key: 'waitlisted', label: 'On the waitlist' },
        event: sampleEvent({ id: 2, slug: 'chouf', title: 'Chouf Cedars Ridge', starts_on: '2026-11-15' }),
        payment: { state: 'after_confirmation', method: null, paid_at: null, refund_due: false, instructions: null },
      }),
    ]),
    'GET /bookings/secret-booking-token': { status: 200, body: { data: sampleBooking() } },
  });
  await renderRouter('./src/app', { initialUrl: '/account' });

  expect(await screen.findByText('My upcoming hikes')).toBeOnTheScreen();
  expect(await screen.findByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
  expect(screen.getByText('Mon, 2 Nov · Confirmed')).toBeOnTheScreen();
  expect(screen.getByText('To pay: $30')).toBeOnTheScreen();
  expect(screen.getByText('Sun, 15 Nov · On the waitlist')).toBeOnTheScreen();
  expect(screen.getByText('Pay after confirmation')).toBeOnTheScreen();
  expect(calls.find((c) => c.path === '/me/bookings')?.auth).toBe('Bearer 1|secret');

  await fireEvent.press(screen.getByRole('link', { name: /Horch Ehden Nature Reserve/ }));

  expect(screen).toHavePathname('/bookings/secret-booking-token');
  expect(await screen.findByText("You're booked!")).toBeOnTheScreen();
});

it('says when there is nothing booked', async () => {
  fakeApi({ ...appConfigRoute, ...me, 'GET /me/bookings': page([]) });
  await renderRouter('./src/app', { initialUrl: '/account' });

  expect(await screen.findByText('No upcoming bookings. Find your next hike in the Hikes tab.')).toBeOnTheScreen();
});

it('lists my completed hikes with the points each earned, page by page', async () => {
  const done = (n: number) =>
    sampleBooking({
      token: `done-${n}`,
      reference: `DONE000${n}`,
      event: sampleEvent({ id: 10 + n, slug: `hike-${n}`, title: `Completed hike ${n}`, starts_on: '2026-09-0' + n }),
      payment: { state: 'paid', method: null, paid_at: null, refund_due: false, instructions: null },
      earned_points: 10 + n,
    });
  const calls = fakeApi({
    ...appConfigRoute,
    ...me,
    'GET /me/bookings': page([done(1)], 1, 2),
  });
  await renderRouter('./src/app', { initialUrl: '/my-hikes' });

  expect(await screen.findByText('Completed hike 1')).toBeOnTheScreen();
  expect(screen.getByText('+11 points')).toBeOnTheScreen();
  expect(calls.find((c) => c.path === '/me/bookings')).toBeDefined();
  expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining('/me/bookings?status=completed&page=1'), expect.anything());
});

it('explains an empty history', async () => {
  fakeApi({ ...appConfigRoute, ...me, 'GET /me/bookings': page([]) });
  await renderRouter('./src/app', { initialUrl: '/my-hikes' });

  expect(await screen.findByText('Your completed hikes appear here once the organizers mark you present.')).toBeOnTheScreen();
});
