/**
 * Test helpers: a fake API behind `fetch`, and sample API data.
 * (Outside __tests__ so Jest doesn't run it as a test file.)
 */
import type { Booking, BookingForm, Event, EventDetail, Member, Order, Product } from '@/api/types';

type Route = { status: number; body?: unknown };
type Call = { method: string; path: string; query: Record<string, string>; body: unknown; auth: string | undefined };

/**
 * Replaces `fetch` with a fake API: "METHOD /path" => answer (or a function giving the answer, for answers that
 * change, e.g. a booking that becomes paid). Every request is recorded in the returned list.
 */
export function fakeApi(routes: Record<string, Route | (() => Route) | 'offline'>): Call[] {
  const calls: Call[] = [];

  global.fetch = jest.fn(async (url: string | URL | Request, init?: RequestInit) => {
    const method = init?.method ?? 'GET';
    const parsed = new URL(String(url));
    const path = parsed.pathname.replace(/^.*\/api\/v1/, '');
    const headers = (init?.headers ?? {}) as Record<string, string>;
    calls.push({ method, path, query: Object.fromEntries(parsed.searchParams), body: init?.body ? JSON.parse(String(init.body)) : undefined, auth: headers.Authorization });

    const answer = routes[`${method} ${path}`] ?? { status: 404, body: { message: 'Not found' } };
    // 'offline': the request never reaches the server (like a phone without signal).
    if (answer === 'offline') throw new TypeError('Network request failed');
    const route = typeof answer === 'function' ? answer() : answer;
    return new Response(route.body === undefined ? null : JSON.stringify(route.body), { status: route.status });
  }) as typeof fetch;

  return calls;
}

/** GET /app-config allowing every version, so the update gate stays open. */
export const appConfigRoute = {
  'GET /app-config': { status: 200, body: { data: { min_version: '0.0.0', latest_version: '1.0.0', store_url: { android: null, ios: null } } } },
};

/**
 * Empty answers for the tabs' own data: native tabs render every tab at once, so a test about one screen
 * also loads Home and Shop. Spread it in when another tab's error (e.g. a second "Try again") gets in the way.
 */
export const quietTabs = {
  'GET /home': { status: 200, body: { data: { slides: [], next_event: null, top_hikers: [] } } },
  'GET /shop/categories': { status: 200, body: { data: [] } },
  'GET /shop/products': { status: 200, body: { data: [] } },
};

export const sampleMember: Member = {
  id: 7,
  name: 'Rima Haddad',
  phone: '+96170123456',
  email: 'rima@example.com',
  emergency_contact: null,
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

export function sampleBookingForm(overrides: Partial<BookingForm> = {}): BookingForm {
  return {
    event_slug: 'horch-ehden',
    open: true,
    waitlist: false,
    x_locked: false,
    requires_level_acknowledgement: false,
    level_label: 'Level 1 · Foundation',
    fee: { cents: 3000, formatted: '$30' },
    meeting_points: [
      { id: 11, name: 'Beirut - Jnah', pickup_time: '07:30' },
      { id: 12, name: 'Jounieh', pickup_time: '08:00' },
    ],
    payment_methods: [
      { key: 'whish', label: 'Whish Money transfer' },
      { key: 'cash', label: 'Cash on the day' },
    ],
    rewards: [],
    terms: 'I take part at my own risk.',
    points_to_earn: 8,
    member: null,
    ...overrides,
  } as BookingForm;
}

export function sampleBooking(overrides: Partial<Booking> = {}): Booking {
  return {
    reference: 'K7F3Q2MZ',
    token: 'secret-booking-token',
    status: { key: 'confirmed', label: 'Confirmed' },
    waitlist_position: null,
    event: sampleEvent(),
    name: 'Karim Saad',
    phone: '+96171222333',
    email: null,
    pickup: { point: 'Beirut - Jnah', time: '07:30' },
    emergency_contact: { name: 'Maya Saad', phone: '+9613444555' },
    notes: null,
    amount_due: { cents: 3000, formatted: '$30' },
    reward_used: null,
    payment: {
      state: 'due',
      method: { key: 'whish', label: 'Whish Money transfer' },
      paid_at: null,
      refund_due: false,
      instructions: { type: 'transfer', provider: 'Whish Money', account_name: 'Hiking Club', account_number: '+961 70 000 000', note: 'K7F3Q2MZ', reported_transaction_number: null },
    },
    cancellation: { cancelled_at: null, reason: null, can_cancel: false, until: null },
    points_to_earn: 8,
    booked_at: '2026-10-29T10:00:00+00:00',
    web_url: 'http://localhost:8080/bookings/secret-booking-token',
    whatsapp_url: null,
    ...overrides,
  } as Booking;
}

export function sampleProduct(overrides: Partial<Product> = {}): Product {
  return {
    id: 5,
    slug: 'club-t-shirt',
    name: 'Club T-shirt',
    category: { slug: 'clothing', name: 'Clothing' },
    description: 'Soft cotton, club logo on the back.',
    tags: ['Unisex'],
    price: { cents: 1500, formatted: '$15' },
    regular_price: { cents: 2000, formatted: '$20' },
    stock: { total: 3, sold_out: false, low: true },
    images: [],
    variants: [
      { id: 51, name: 'S', stock: 0 },
      { id: 52, name: 'M', stock: 3 },
    ],
    web_url: 'http://localhost:8080/shop/club-t-shirt',
    ...overrides,
  } as Product;
}

export function sampleOrder(overrides: Partial<Order> = {}): Order {
  return {
    reference: 'ORD12345',
    token: 'secret-order-token',
    status: { key: 'new', label: 'Received' },
    items: [
      {
        product_slug: 'club-t-shirt',
        product_name: 'Club T-shirt',
        variant_name: 'M',
        quantity: 2,
        unit_price: { cents: 1500, formatted: '$15' },
        line_total: { cents: 3000, formatted: '$30' },
      },
    ],
    subtotal: { cents: 3000, formatted: '$30' },
    delivery_fee: { cents: 400, formatted: '$4' },
    total: { cents: 3400, formatted: '$34' },
    fulfilment: { key: 'delivery', label: 'Delivery', city: 'Beirut', address: 'Hamra Street 12', note: 'Delivery in 3–5 working days.' },
    name: 'Karim Saad',
    phone: '+96171222333',
    email: null,
    notes: null,
    payment: {
      state: 'due',
      method: { key: 'omt', label: 'OMT Pay transfer' },
      paid_at: null,
      instructions: { type: 'transfer', provider: 'OMT Pay', account_name: 'Hiking Club', account_number: '+961 70 000 000', note: 'ORD12345', reported_transaction_number: null },
    },
    cancellation: null,
    ordered_at: '2026-10-29T10:00:00+00:00',
    web_url: 'http://localhost:8080/orders/secret-order-token',
    ...overrides,
  } as Order;
}
