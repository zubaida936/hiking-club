import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { PastEvent } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleEvent } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const past = (overrides: Partial<PastEvent>): PastEvent => ({ ...sampleEvent(), album_slug: null, ...overrides }) as PastEvent;
const page = (events: PastEvent[]) => ({ status: 200, body: { data: events, links: {}, meta: { current_page: 1, last_page: 1 } } });

it('lists past hikes by month, linked to their photos', async () => {
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /events/past': page([
      past({ id: 3, slug: 'qadisha', title: 'Qadisha Valley', starts_on: '2026-10-18', album_slug: 'qadisha-photos' }),
      past({ id: 2, slug: 'chouf', title: 'Chouf Cedars', starts_on: '2026-10-04' }),
      past({ id: 1, slug: 'tannourine', title: 'Tannourine', starts_on: '2026-09-20' }),
    ]),
    'GET /gallery/qadisha-photos': { status: 200, body: { data: { slug: 'qadisha-photos', title: 'Qadisha', description: null, taken_on: null, event_slug: 'qadisha', photos: [], web_url: '' } } },
  });
  await renderRouter('./src/app', { initialUrl: '/past-hikes' });

  expect(await screen.findByText('October 2026')).toBeOnTheScreen();
  expect(screen.getByText('September 2026')).toBeOnTheScreen();
  expect(screen.getByText('Chouf Cedars')).toBeOnTheScreen();
  // Only the hike with a published album has the photos link.
  expect(screen.getAllByText('See the photos')).toHaveLength(1);

  await fireEvent.press(screen.getByText('See the photos'));
  expect(screen).toHavePathname('/gallery/qadisha-photos');
});

it('opens from the Hikes tab', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /events': page([]), 'GET /events/past': page([]) });
  await renderRouter('./src/app', { initialUrl: '/hikes' });

  // The More tab (rendered too) has a "Past hikes" row as well: the Hikes tab's link comes first.
  await fireEvent.press((await screen.findAllByText('Past hikes'))[0]);

  expect(screen).toHavePathname('/past-hikes');
  expect(await screen.findByText('No past hikes yet.')).toBeOnTheScreen();
});
