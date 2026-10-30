import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { PointLine, RewardState } from '@/api/types';
import { appConfigRoute, fakeApi, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

const page = (data: unknown[], current = 1, last = 1) => ({ status: 200, body: { data, links: {}, meta: { current_page: current, last_page: last, total: data.length } } });
const base = { ...appConfigRoute, 'GET /me': { status: 200, body: { data: sampleMember } }, 'GET /me/bookings': page([]) };

const line = (overrides: Partial<PointLine>): PointLine => ({
  id: 1,
  type: { key: 'hike', label: 'Completed hike' },
  points: 14,
  description: 'Completed Qadisha Valley',
  event: { slug: 'qadisha', title: 'Qadisha Valley' },
  created_at: '2026-10-12T15:00:00+00:00',
  ...overrides,
});

const reward = (overrides: Partial<RewardState>): RewardState => ({
  key: 'trekker_half_off',
  label: '50% off one hike',
  kind: 'discount',
  rank: { key: 'trekker', label: 'Trekker' },
  how_it_works: 'Choose it on the booking form when you book.',
  unlocked: true,
  locked_reason: null,
  uses_left: 1,
  used: [],
  perk_code: null,
  ...overrides,
});

it('opens my points history from the account tab', async () => {
  fakeApi({
    ...base,
    'GET /me/points': page([line({}), line({ id: 2, type: { key: 'adjustment', label: 'Adjustment' }, points: -4, description: 'Correction', event: null })]),
  });
  await renderRouter('./src/app', { initialUrl: '/account' });

  await fireEvent.press(await screen.findByText('My points history'));

  expect(screen).toHavePathname('/my-points');
  expect(await screen.findByText('Completed Qadisha Valley')).toBeOnTheScreen();
  expect(screen.getByText('+14')).toBeOnTheScreen();
  expect(screen.getByText('Qadisha Valley')).toBeOnTheScreen();
  expect(screen.getByText('Correction')).toBeOnTheScreen();
  expect(screen.getByText('-4')).toBeOnTheScreen();
});

it('says when there are no points yet', async () => {
  fakeApi({ ...base, 'GET /me/points': page([]) });
  await renderRouter('./src/app', { initialUrl: '/my-points' });

  expect(await screen.findByText('No points yet. You earn points for every hike you complete.')).toBeOnTheScreen();
});

it('shows my rewards by rank: available, used and locked', async () => {
  fakeApi({
    ...base,
    'GET /me/rewards': {
      status: 200,
      body: {
        data: [
          reward({}),
          reward({
            key: 'mountaineer_free_moderate',
            label: 'One free Moderate (Level 2) hike',
            rank: { key: 'mountaineer', label: 'Mountaineer' },
            uses_left: 0,
            used: [{ event: { slug: 'chouf', title: 'Chouf Cedars Ridge' }, discount_cents: 3000, note: null, used_at: '2026-10-20T09:00:00+00:00' }],
          }),
          reward({ key: 'highlander_gift_card', label: 'Gift card', kind: 'perk', rank: { key: 'highlander', label: 'Highlander' }, unlocked: false, locked_reason: 'Reach Highlander (400 points)' }),
        ],
      },
    },
  });
  await renderRouter('./src/app', { initialUrl: '/account' });

  await fireEvent.press(await screen.findByText('My rewards'));

  expect(await screen.findByText('Trekker rank')).toBeOnTheScreen();
  expect(screen.getByText('Available: 1 use left')).toBeOnTheScreen();
  expect(screen.getByText('Mountaineer rank')).toBeOnTheScreen();
  expect(screen.getByText('Used')).toBeOnTheScreen();
  expect(screen.getByText(/^Used on Chouf Cedars Ridge/)).toBeOnTheScreen();
  expect(screen.getByText('Reach Highlander (400 points)')).toBeOnTheScreen();
});
