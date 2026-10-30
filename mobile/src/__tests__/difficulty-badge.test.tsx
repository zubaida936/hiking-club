import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, quietTabs, sampleEvent, sampleEventDetail, sampleLevels } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const ok = (data: unknown) => ({ status: 200, body: { data } });
const list = { status: 200, body: { data: [sampleEvent({ difficulty: { level: 2, name: 'Endurance', label: 'Level 2 · Endurance', description: 'Moderate. Longer distance or steady climbs; needs regular hiking fitness.' }, effort_score: 156 })], links: {}, meta: {} } };
const badge = { name: 'Level 2 · Endurance, effort score 156' };

it('explains the levels from a hike card without opening the hike', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /events': list, 'GET /difficulty/levels': ok(sampleLevels) });
  await renderRouter('./src/app', { initialUrl: '/hikes' });

  await fireEvent.press(await screen.findByRole('button', badge));

  expect(await screen.findByText('Difficulty levels')).toBeOnTheScreen();
  expect(screen.getByText('Effort score 156: Level 2 · Endurance')).toBeOnTheScreen();
  expect(await screen.findByText('score 121–200')).toBeOnTheScreen();
  expect(screen.getByText('score 301+')).toBeOnTheScreen();
  expect(screen.getByText('This hike')).toBeOnTheScreen();
  expect(screen).toHavePathname('/hikes');

  await fireEvent.press(screen.getByRole('button', { name: 'Close' }));
  expect(screen.queryByText('Difficulty levels')).toBeNull();
});

it('opens the calculator from the sheet on a hike', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /events/horch-ehden': ok(sampleEventDetail()), 'GET /difficulty/levels': ok(sampleLevels) });
  await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Level 1 · Foundation, effort score 90' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Open the difficulty calculator' }));

  expect(screen).toHavePathname('/difficulty-calculator');
  expect(screen.queryByText('Difficulty levels')).toBeNull();
});
