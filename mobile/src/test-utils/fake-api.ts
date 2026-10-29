/**
 * Test helpers: a fake API behind `fetch`, and sample API data.
 * (Outside __tests__ so Jest doesn't run it as a test file.)
 */
import type { Event, EventDetail, Member } from '@/api/types';

type Route = { status: number; body?: unknown };
type Call = { method: string; path: string; body: unknown; auth: string | undefined };

/** Replaces `fetch` with a fake API: "METHOD /path" => answer. Every request is recorded in the returned list. */
export function fakeApi(routes: Record<string, Route>): Call[] {
  const calls: Call[] = [];

  global.fetch = jest.fn(async (url: string | URL | Request, init?: RequestInit) => {
    const method = init?.method ?? 'GET';
    const path = new URL(String(url)).pathname.replace(/^.*\/api\/v1/, '');
    const headers = (init?.headers ?? {}) as Record<string, string>;
    calls.push({ method, path, body: init?.body ? JSON.parse(String(init.body)) : undefined, auth: headers.Authorization });

    const route = routes[`${method} ${path}`] ?? { status: 404, body: { message: 'Not found' } };
    return new Response(route.body === undefined ? null : JSON.stringify(route.body), { status: route.status });
  }) as typeof fetch;

  return calls;
}

/** GET /app-config allowing every version, so the update gate stays open. */
export const appConfigRoute = {
  'GET /app-config': { status: 200, body: { data: { min_version: '0.0.0', latest_version: '1.0.0', store_url: { android: null, ios: null } } } },
};

export const sampleMember: Member = {
  id: 7,
  name: 'Rima Haddad',
  phone: '+96170123456',
  email: 'rima@example.com',
  hiker: {
    points: 140,
    rank: { key: 'trekker', label: 'Trekker' },
    next_rank: { key: 'mountaineer', label: 'Mountaineer', points_needed: 60 },
    rank_progress_percent: 50,
    leaderboard_position: 3,
    hikes_completed: 4,
    distance_km: 52.5,
    show_on_leaderboard: true,
    x_events_approved: false,
  },
};

export function sampleEvent(overrides: Partial<Event> = {}): Event {
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

export function sampleEventDetail(overrides: Partial<EventDetail> = {}): EventDetail {
  return {
    ...sampleEvent(),
    summary: 'A guided day hike in the cedars.',
    description: 'First paragraph.\n\nSecond paragraph.',
    included: ['Transportation', 'Guiding'],
    what_to_bring: ['2L water'],
    itinerary: [
      { time: '07:30', text: 'Departure from Beirut' },
      { time: null, text: 'Lunch by the river' },
    ],
    meeting_points: [{ name: 'Beirut - Jnah', pickup_time: '07:30' }],
    updates: [],
    photo_credit: null,
    gpx_url: null,
    route_points: [],
    album_slug: null,
    ...overrides,
  } as EventDetail;
}
