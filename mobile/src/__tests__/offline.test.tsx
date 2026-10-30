import { dehydrate, QueryClient } from '@tanstack/react-query';
import { renderRouter, screen } from 'expo-router/testing-library';

import { persistOptions, shouldSaveQuery } from '@/api/query-cache';
import { appConfigRoute, fakeApi, quietTabs, sampleEventDetail } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const success = { status: 'success' } as never;

it('saves hikes, home, my profile and my bookings, nothing else', () => {
  const saved = (queryKey: unknown[]) => shouldSaveQuery({ queryKey, state: success });

  expect(saved(['events', 'upcoming', {}])).toBe(true);
  expect(saved(['events', 'detail', 'horch-ehden'])).toBe(true);
  expect(saved(['home'])).toBe(true);
  expect(saved(['me'])).toBe(true);
  expect(saved(['my', 'bookings', 'upcoming'])).toBe(true);
  expect(saved(['my', 'booking', 'secret-token'])).toBe(true);
  // Not needed on the trail: orders, points, the shop, the organizer's lists (they have their own offline store).
  expect(saved(['my', 'orders'])).toBe(false);
  expect(saved(['shop', 'products', null])).toBe(false);
  expect(saved(['organizer', 'events'])).toBe(false);
  // Failed answers are never saved.
  expect(shouldSaveQuery({ queryKey: ['home'], state: { status: 'error' } as never })).toBe(false);
});

it('shows a hike saved last time when the app starts without signal', async () => {
  // What the app saved on the phone during an earlier visit to the hike.
  const earlier = new QueryClient();
  earlier.setQueryData(['events', 'detail', 'horch-ehden'], sampleEventDetail({ meeting_points: [{ name: 'Beirut - Jnah', pickup_time: '07:30' }] }));
  await persistOptions.persister.persistClient({ timestamp: Date.now(), buster: persistOptions.buster ?? '', clientState: dehydrate(earlier, persistOptions.dehydrateOptions) });

  // Now on the trail: the server can't be reached.
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /events/horch-ehden': 'offline' });
  await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

  expect(await screen.findByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
  expect(screen.getByText('Beirut - Jnah')).toBeOnTheScreen();
  // The failed refresh is retried twice before the note appears.
  expect(await screen.findByText(/^Couldn't refresh: showing what was saved at/, {}, { timeout: 10000 })).toBeOnTheScreen();
}, 30000);
