import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Linking } from 'react-native';

import { appConfigRoute, fakeApi, sampleEvent, sampleEventDetail } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
afterEach(() => jest.restoreAllMocks());

const home = (overrides = {}) => ({
  status: 200,
  body: {
    data: {
      slides: [
        { eyebrow: 'Autumn', heading: 'Into the cedars', text: 'Weekend hikes for every level.', cta: 'See the hike', url: 'http://localhost:8080/events/horch-ehden', image: null },
        { eyebrow: null, heading: 'Past hikes', text: null, cta: 'See the archive', url: 'http://localhost:8080/events/archive', image: null },
      ],
      next_event: sampleEvent(),
      top_hikers: [
        { position: 1, name: 'Rima H.', rank: { key: 'mountaineer', label: 'Mountaineer' }, points: 250 },
        { position: 2, name: 'Karim S.', rank: { key: 'trekker', label: 'Trekker' }, points: 90 },
      ],
      ...overrides,
    },
  },
});

it('shows the slides, the next hike and the top hikers', async () => {
  fakeApi({ ...appConfigRoute, 'GET /home': home() });
  await renderRouter('./src/app', { initialUrl: '/' });

  expect(await screen.findByText('Into the cedars')).toBeOnTheScreen();
  expect(screen.getByText('Past hikes')).toBeOnTheScreen();
  expect(screen.getByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
  expect(screen.getByText('Rima H.')).toBeOnTheScreen();
  expect(screen.getByText('250 pts')).toBeOnTheScreen();
});

it('opens a slide link in the app when there is a screen for it, otherwise in the browser', async () => {
  const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
  fakeApi({ ...appConfigRoute, 'GET /home': home(), 'GET /events/horch-ehden': { status: 200, body: { data: sampleEventDetail() } } });
  await renderRouter('./src/app', { initialUrl: '/' });

  await fireEvent.press(await screen.findByRole('button', { name: 'See the archive' }));
  expect(openURL).toHaveBeenCalledWith('http://localhost:8080/events/archive');

  await fireEvent.press(screen.getByRole('button', { name: 'See the hike' }));
  expect(screen).toHavePathname('/events/horch-ehden');
});

it('says when no hike is planned and links to all hikes', async () => {
  fakeApi({ ...appConfigRoute, 'GET /home': home({ next_event: null, top_hikers: [] }), 'GET /events': { status: 200, body: { data: [], links: {}, meta: { current_page: 1, last_page: 1, total: 0 } } } });
  await renderRouter('./src/app', { initialUrl: '/' });

  expect(await screen.findByText('No hike planned right now. Check back soon!')).toBeOnTheScreen();
  await fireEvent.press(screen.getByText('See all hikes'));
  expect(screen).toHavePathname('/hikes');
});
