import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { DifficultyResult } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleEvent, sampleLevels } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const ok = (data: unknown) => ({ status: 200, body: { data } });

const result = (overrides: Partial<DifficultyResult> = {}): DifficultyResult => ({
  effort_score: 156,
  difficulty: { level: 2, name: 'Endurance', label: 'Level 2 · Endurance', description: 'Moderate. Longer distance or steady climbs; needs regular hiking fitness.' },
  points: 15,
  upcoming_hikes: [],
  ...overrides,
});

it('explains the formula and the levels from the API', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /difficulty/levels': ok(sampleLevels) });
  await renderRouter('./src/app', { initialUrl: '/difficulty-calculator' });

  expect(await screen.findByText('score = 10 × (km + gain ÷ 100 + loss ÷ 200)')).toBeOnTheScreen();
  expect(screen.getByText('score 0–120')).toBeOnTheScreen();
  expect(screen.getByText('score 121–200')).toBeOnTheScreen();
  expect(screen.getByText('score 301+')).toBeOnTheScreen();
  expect(screen.getByText('30 points per hike')).toBeOnTheScreen();
});

it('calculates on the server and suggests upcoming hikes of that level', async () => {
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /difficulty/levels': ok(sampleLevels),
    'GET /difficulty/calculate': ok(result({ upcoming_hikes: [sampleEvent({ title: 'Qadisha Valley loop' })] })),
  });
  await renderRouter('./src/app', { initialUrl: '/difficulty-calculator' });

  await fireEvent.changeText(await screen.findByLabelText('Distance (km)'), '12,5');
  await fireEvent.changeText(screen.getByLabelText('Elevation gain (m)'), '600');
  await fireEvent.press(screen.getByRole('button', { name: 'Calculate' }));

  expect(await screen.findByText('156')).toBeOnTheScreen();
  expect(screen.getByText('Completing a hike at this level earns 15 points.')).toBeOnTheScreen();
  expect(screen.getByText('Upcoming Level 2 · Endurance hikes')).toBeOnTheScreen();
  expect(screen.getByText('Qadisha Valley loop')).toBeOnTheScreen();
  expect(screen.getByText('Your route')).toBeOnTheScreen();
  // A decimal comma is sent as a point; the empty loss isn't sent.
  expect(calls.find((call) => call.path === '/difficulty/calculate')?.query).toEqual({ km: '12.5', gain: '600' });
});

it('says when no hike of that level is planned', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /difficulty/levels': ok(sampleLevels), 'GET /difficulty/calculate': ok(result()) });
  await renderRouter('./src/app', { initialUrl: '/difficulty-calculator' });

  await fireEvent.changeText(await screen.findByLabelText('Distance (km)'), '12');
  await fireEvent.press(screen.getByRole('button', { name: 'Calculate' }));

  expect(await screen.findByText('No upcoming hikes at this level right now.')).toBeOnTheScreen();
});

it("shows the API's validation messages under the fields", async () => {
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /difficulty/levels': ok(sampleLevels),
    'GET /difficulty/calculate': { status: 422, body: { message: 'The km field is required.', errors: { km: ['The km field is required.'] } } },
  });
  await renderRouter('./src/app', { initialUrl: '/difficulty-calculator' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Calculate' }));

  expect(await screen.findByText('The km field is required.')).toBeOnTheScreen();
});
