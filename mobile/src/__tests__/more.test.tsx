import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

it('lists the club screens and opens one', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /equipment': { status: 200, body: { data: [] } } });
  await renderRouter('./src/app', { initialUrl: '/more' });

  expect(await screen.findByRole('header', { name: 'The club' })).toBeOnTheScreen();
  expect(screen.getByRole('header', { name: 'Plan your hike' })).toBeOnTheScreen();
  expect(screen.getByRole('header', { name: 'About and contact' })).toBeOnTheScreen();
  for (const row of ['X Events', 'Past hikes', 'Photo gallery', 'Leaderboard', 'How ranks and points work', "Hiker's guides", 'Difficulty calculator', 'About the club', 'Contact us']) {
    // All tabs render at once: the Hikes tab has a "Past hikes" link too.
    expect(screen.getAllByRole('link', { name: row }).length).toBeGreaterThan(0);
  }
  // Not logged in with a staff account: no Organizer row.
  expect(screen.queryByRole('link', { name: 'Organizer: check-in and perks' })).not.toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('link', { name: 'Equipment guide' }));
  expect(screen).toHavePathname('/equipment');
});

it('says the Organizer screen is for staff when opened without a staff account', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs });
  await renderRouter('./src/app', { initialUrl: '/organizer' });

  expect(await screen.findByText('This screen is for organizers. Log in with a staff account to see it.')).toBeOnTheScreen();
});
