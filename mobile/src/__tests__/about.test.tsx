import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Linking } from 'react-native';

import type { About } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const about = (overrides: Partial<About> = {}): About => ({
  intro: 'A hiking club for everyone.',
  founded: 2018,
  story: ['It started with a handful of friends.', 'Word spread.'],
  offers: [{ icon: '🧭', title: 'Expert-led', text: 'Experienced guides lead every hike.' }],
  testimonials: [{ name: 'Rima H.', text: 'My first hike ever and I felt safe.' }],
  google_reviews_url: 'https://g.page/hiking-club/review',
  team: [{ name: 'Sami K.', role: 'Lead guide', bio: 'Twenty years on Lebanese trails.', photo_url: null }],
  stats: { hikes: 120, hikers: 1 },
  ...overrides,
});

it('tells the club story with its team, numbers and reviews', async () => {
  const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /about': { status: 200, body: { data: about() } } });
  await renderRouter('./src/app', { initialUrl: '/about' });

  expect(await screen.findByText('Hiking together since 2018')).toBeOnTheScreen();
  expect(screen.getByText('120 hikes')).toBeOnTheScreen();
  expect(screen.getByText('1 hiker')).toBeOnTheScreen();
  expect(screen.getByText('Word spread.')).toBeOnTheScreen();
  expect(screen.getByText('🧭 Expert-led')).toBeOnTheScreen();
  expect(screen.getByText('Lead guide')).toBeOnTheScreen();
  expect(screen.getByText('“My first hike ever and I felt safe.”')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Read our Google reviews' }));
  expect(openURL).toHaveBeenCalledWith('https://g.page/hiking-club/review');
});

it('leaves out the team and reviews link when there are none', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /about': { status: 200, body: { data: about({ team: [], google_reviews_url: null }) } } });
  await renderRouter('./src/app', { initialUrl: '/about' });

  expect(await screen.findByText('Our story')).toBeOnTheScreen();
  expect(screen.queryByText('Our team')).toBeNull();
  expect(screen.queryByRole('button', { name: 'Read our Google reviews' })).toBeNull();
});
