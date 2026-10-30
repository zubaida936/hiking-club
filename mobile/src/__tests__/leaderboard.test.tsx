import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { LeaderboardRow, Member } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
beforeEach(() => jest.clearAllMocks());

const ok = (data: unknown) => ({ status: 200, body: { data } });
const row = (overrides: Partial<LeaderboardRow>): LeaderboardRow => ({
  position: 1,
  name: 'Karim S.',
  rank: { key: 'mountaineer', label: 'Mountaineer' },
  points: 250,
  hikes_completed: 12,
  distance_km: 140.5,
  is_me: false,
  ...overrides,
});
const board = (rows: LeaderboardRow[], myPosition: number | null) => ({ status: 200, body: { data: rows, my_position: myPosition } });

async function asMember(routes: Record<string, unknown>) {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  const calls = fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /me/bookings': ok([]), ...(routes as Parameters<typeof fakeApi>[0]) });
  await renderRouter('./src/app', { initialUrl: '/leaderboard' });

  return calls;
}

it('shows the public leaderboard to guests', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /leaderboard': board([row({}), row({ position: 2, name: 'Rima H.', points: 140, hikes_completed: 1, distance_km: 8 })], null) });
  await renderRouter('./src/app', { initialUrl: '/leaderboard' });

  expect(await screen.findByText('Karim S.')).toBeOnTheScreen();
  expect(screen.getByText('Mountaineer · 12 hikes · 140.5 km')).toBeOnTheScreen();
  expect(screen.getByText('Mountaineer · 1 hike · 8 km')).toBeOnTheScreen();
  expect(screen.getByText('250 pts')).toBeOnTheScreen();
  expect(screen.queryByText('Your place')).toBeNull();
});

it("marks the member's place and lets them hide from the leaderboard", async () => {
  let hidden = false;
  const hiddenMember: Member = { ...sampleMember, hiker: { ...sampleMember.hiker!, show_on_leaderboard: false, leaderboard_position: null } };
  const calls = await asMember({
    'GET /me': ok(sampleMember),
    'GET /leaderboard': () => (hidden ? board([row({})], null) : board([row({}), row({ position: 2, name: 'Rima H.', is_me: true })], 2)),
    'PUT /me/leaderboard': () => {
      hidden = true;
      return ok(hiddenMember);
    },
  });

  expect(await screen.findByText('#2 on the leaderboard')).toBeOnTheScreen();
  expect(screen.getByText('Rima H. · You')).toBeOnTheScreen();

  await fireEvent(screen.getByLabelText('Show me on the leaderboard'), 'valueChange', false);

  expect(await screen.findByText("You're hidden from the leaderboard. Your points still count.")).toBeOnTheScreen();
  expect(calls.find((call) => call.method === 'PUT')?.body).toEqual({ show_on_leaderboard: false });
  expect(screen.queryByText('Rima H. · You')).toBeNull();
});

it('tells a member without points when they will appear', async () => {
  const newcomer: Member = { ...sampleMember, hiker: { ...sampleMember.hiker!, points: 0, leaderboard_position: null } };
  await asMember({ 'GET /me': ok(newcomer), 'GET /leaderboard': board([row({})], null) });

  expect(await screen.findByText("You'll appear here once you have points from a completed hike.")).toBeOnTheScreen();
});
