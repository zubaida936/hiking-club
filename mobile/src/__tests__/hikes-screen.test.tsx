import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen } from '@testing-library/react-native';

import { apiFetch } from '@/api/client';
import type { Event } from '@/api/types';
import HikesScreen from '@/app/(tabs)/index';

jest.mock('@/api/client', () => ({ ...jest.requireActual('@/api/client'), apiFetch: jest.fn() }));

function hike(overrides: Partial<Event> = {}): Event {
  return {
    id: 1,
    slug: 'horch-ehden',
    title: 'Horch Ehden Nature Reserve',
    location: 'Ehden',
    season_tag: null,
    starts_on: '2026-11-02',
    summary: null,
    distance_km: 8,
    elevation_gain_m: 220,
    elevation_loss_m: 320,
    duration_hours: 2.7,
    effort_score: 90,
    difficulty: { level: 1, name: 'Foundation', label: 'Level 1 · Foundation', description: 'Easy.' },
    fee: { cents: 3000, formatted: '$30' },
    is_xtreme: false,
    status: 'published',
    cancellation_reason: null,
    seats: { capacity: 30, left: 4, sold_out: false },
    registration: { open: true, closes_at: '2026-11-01T22:00:00+00:00', waitlist_open: false, requires_level_acknowledgement: false },
    cover_image_url: null,
    web_url: 'http://localhost:8080/events/horch-ehden',
    ...overrides,
  } as Event;
}

function renderScreen() {
  // gcTime Infinity: no cache clean-up timers left running after the test.
  const client = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: Infinity } } });

  return render(
    <QueryClientProvider client={client}>
      <HikesScreen />
    </QueryClientProvider>,
  );
}

it('lists the upcoming hikes from the API', async () => {
  jest.mocked(apiFetch).mockResolvedValue({
    data: [hike(), hike({ id: 2, title: 'Chouf Cedars Ridge', seats: { capacity: 20, left: 0, sold_out: true } })],
  });

  await renderScreen();

  expect(await screen.findByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
  expect(screen.getByText('4 seats left')).toBeOnTheScreen();
  expect(screen.getAllByText('Level 1 · Foundation · $30')).toHaveLength(2);
  expect(screen.getByText('Fully booked')).toBeOnTheScreen();
  expect(apiFetch).toHaveBeenCalledWith('/events');
});

it('shows the error message with a retry button', async () => {
  const { ApiError } = jest.requireActual('@/api/client');
  jest.mocked(apiFetch).mockRejectedValue(new ApiError("Can't reach the server.", 0));

  await renderScreen();

  expect(await screen.findByText("Can't reach the server.")).toBeOnTheScreen();
  expect(screen.getByRole('button', { name: 'Try again' })).toBeOnTheScreen();
});
