import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { RankSystem } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const ok = (data: unknown) => ({ status: 200, body: { data } });
const rankSystem: RankSystem = {
  ranks: [
    { key: 'pathfinder', label: 'Pathfinder', min_points: 0, rewards: [] },
    { key: 'trekker', label: 'Trekker', min_points: 80, rewards: [{ key: 'trekker_half_off', label: '50% off one hike', kind: 'discount', how_it_works: 'After 5 completed hikes.' }] },
    { key: 'mountaineer', label: 'Mountaineer', min_points: 200, rewards: [] },
  ],
  points: {
    per_hike: [
      { level: 1, label: 'Level 1 · Foundation', points: 10 },
      { level: 2, label: 'Level 2 · Endurance', points: 14 },
    ],
    early_booking: { points: 4, days: 5 },
    referral: 10,
    paid_only: true,
  },
  example: { level_label: 'Level 2 · Endurance', hike_points: 14, early_booking_points: 4, total: 18, rank_label: 'Trekker', hikes_to_rank: 5 },
  terms: ["Points don't expire."],
};

it('explains the ranks, how to earn points and the terms from the API', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /rank-system': ok(rankSystem) });
  await renderRouter('./src/app', { initialUrl: '/rank-system' });

  expect(await screen.findByText('🥾 Trekker')).toBeOnTheScreen();
  expect(screen.getByText('From your first hike')).toBeOnTheScreen();
  expect(screen.getByText('80+ points')).toBeOnTheScreen();
  expect(screen.getByText('🎁 50% off one hike')).toBeOnTheScreen();
  expect(screen.getByText('Completed hike: Level 2 · Endurance')).toBeOnTheScreen();
  expect(screen.getByText('Book at least 5 days before the hike')).toBeOnTheScreen();
  expect(screen.getByText(/and the hike is paid \(free hikes always count\)/)).toBeOnTheScreen();
  expect(screen.getByText('A Level 2 · Endurance hike booked early: 14 + 4 = 18 points. About 5 of those take you to Trekker.')).toBeOnTheScreen();
  expect(screen.getByText("• Points don't expire.")).toBeOnTheScreen();
  // Guests are asked to log in to see their own rank.
  expect(screen.getByText(/Log in on the Account tab to see your rank/)).toBeOnTheScreen();
});

it("marks the member's rank and opens from My points history", async () => {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /me': ok(sampleMember),
    'GET /me/bookings': ok([]),
    'GET /me/points': { status: 200, body: { data: [], links: {}, meta: { current_page: 1, last_page: 1, total: 0 } } },
    'GET /rank-system': ok(rankSystem),
  });
  await renderRouter('./src/app', { initialUrl: '/my-points' });

  await fireEvent.press(await screen.findByText('How ranks and points work'));

  expect(screen).toHavePathname('/rank-system');
  expect(await screen.findByText('🥾 Trekker · 140 points')).toBeOnTheScreen();
  expect(screen.getByText('60 points to Mountaineer')).toBeOnTheScreen();
  expect(screen.getByText('You are here')).toBeOnTheScreen();
});
