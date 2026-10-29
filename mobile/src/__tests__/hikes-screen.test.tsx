import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Linking, Share } from 'react-native';

import { appConfigRoute, fakeApi, sampleEvent, sampleEventDetail } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

const list = (events: ReturnType<typeof sampleEvent>[]) => ({ status: 200, body: { data: events, links: {}, meta: {} } });

describe('hikes list', () => {
  it('lists the upcoming hikes from the API', async () => {
    fakeApi({
      ...appConfigRoute,
      'GET /events': list([sampleEvent(), sampleEvent({ id: 2, slug: 'chouf', title: 'Chouf Cedars Ridge', seats: { capacity: 20, left: 0, sold_out: true } })]),
    });
    await renderRouter('./src/app', { initialUrl: '/' });

    expect(await screen.findByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
    expect(screen.getByText('4 seats left')).toBeOnTheScreen();
    expect(screen.getAllByText('Level 1 · Foundation · $30')).toHaveLength(2);
    expect(screen.getByText('Fully booked')).toBeOnTheScreen();
  });

  it('shows the error message with a retry button', async () => {
    global.fetch = jest.fn(async () => {
      throw new TypeError('Network request failed');
    }) as typeof fetch;
    await renderRouter('./src/app', { initialUrl: '/' });

    // The app retries network trouble twice before showing the error.
    expect(await screen.findByText("Can't reach the server. Check your internet connection and try again.", {}, { timeout: 10000 })).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Try again' })).toBeOnTheScreen();
  });
});

describe('hikes filters', () => {
  it('filters by level and X events, and shows the months that have hikes', async () => {
    const calls = fakeApi({
      ...appConfigRoute,
      'GET /events': list([sampleEvent()]),
      'GET /events/months': { status: 200, body: { data: [
        { month: '2026-12', label: 'December 2026', upcoming: 2, past: 0 },
        { month: '2026-11', label: 'November 2026', upcoming: 3, past: 1 },
        { month: '2026-09', label: 'September 2026', upcoming: 0, past: 4 },
      ] } },
    });
    await renderRouter('./src/app', { initialUrl: '/' });

    // Upcoming months only, soonest first.
    expect(await screen.findByRole('button', { name: 'November 2026' })).toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'December 2026' })).toBeOnTheScreen();
    expect(screen.queryByRole('button', { name: 'September 2026' })).not.toBeOnTheScreen();

    await fireEvent.press(screen.getByRole('button', { name: 'Level 2' }));
    await fireEvent.press(screen.getByRole('button', { name: 'X Events only' }));
    await fireEvent.press(screen.getByRole('button', { name: 'November 2026' }));

    await screen.findByText('Horch Ehden Nature Reserve');
    expect(calls.filter((c) => c.path === '/events').at(-1)?.query).toEqual({ level: '2', xtreme: '1', month: '2026-11' });
    expect(screen.getByRole('button', { name: 'Level 2' })).toBeSelected();

    // Pressing a selected chip again removes that filter.
    await fireEvent.press(screen.getByRole('button', { name: 'Level 2' }));
    await screen.findByText('Horch Ehden Nature Reserve');
    expect(calls.filter((c) => c.path === '/events').at(-1)?.query).toEqual({ xtreme: '1', month: '2026-11' });
  });

  it('searches once typing stops, and offers to clear filters when nothing matches', async () => {
    let events = [sampleEvent()];
    const calls = fakeApi({ ...appConfigRoute, 'GET /events': () => list(events) });
    await renderRouter('./src/app', { initialUrl: '/' });
    await screen.findByText('Horch Ehden Nature Reserve');

    events = [];
    await fireEvent.changeText(screen.getByLabelText('Search hikes'), 'qadisha');

    expect(await screen.findByText('No hikes match these filters.')).toBeOnTheScreen();
    expect(calls.filter((c) => c.path === '/events').at(-1)?.query).toEqual({ search: 'qadisha' });

    events = [sampleEvent()];
    await fireEvent.press(screen.getByRole('button', { name: 'Clear filters' }));

    expect(await screen.findByText('Horch Ehden Nature Reserve')).toBeOnTheScreen();
    expect(screen.getByLabelText('Search hikes')).toHaveDisplayValue('');
  });
});

describe('hike detail', () => {
  it('opens from the list and shows everything about the hike', async () => {
    fakeApi({
      ...appConfigRoute,
      'GET /events': list([sampleEvent()]),
      'GET /events/horch-ehden': { status: 200, body: { data: sampleEventDetail({ updates: [{ message: 'Bring a rain jacket.', posted_at: '2026-10-30T09:00:00+00:00' }] }) } },
    });
    await renderRouter('./src/app', { initialUrl: '/' });

    await fireEvent.press(await screen.findByRole('link', { name: /Horch Ehden Nature Reserve/ }));

    expect(screen).toHavePathname('/events/horch-ehden');
    expect(await screen.findByText('Monday, 2 November 2026 · Ehden')).toBeOnTheScreen();
    expect(screen.getByText('Level 1 · Foundation')).toBeOnTheScreen();
    expect(screen.getByText('Effort score 90')).toBeOnTheScreen();
    expect(screen.getByText('8 km')).toBeOnTheScreen();
    expect(screen.getByText('+220 / -320 m')).toBeOnTheScreen();
    expect(screen.getByText('4 seats left')).toBeOnTheScreen();
    expect(screen.getByText('Bring a rain jacket.')).toBeOnTheScreen();
    expect(screen.getByText('Second paragraph.')).toBeOnTheScreen();
    expect(screen.getByText('Departure from Beirut')).toBeOnTheScreen();
    expect(screen.getByText('• Transportation')).toBeOnTheScreen();
    expect(screen.getByText('• 2L water')).toBeOnTheScreen();
  });

  it('opens a pickup point in Google Maps and shares the website link', async () => {
    const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
    const share = jest.spyOn(Share, 'share').mockResolvedValue({ action: 'sharedAction', activityType: undefined });
    fakeApi({ ...appConfigRoute, 'GET /events/horch-ehden': { status: 200, body: { data: sampleEventDetail() } } });
    await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

    await fireEvent.press(await screen.findByText('Beirut - Jnah'));
    expect(openURL).toHaveBeenCalledWith('https://www.google.com/maps/search/?api=1&query=Beirut%20-%20Jnah%2C%20Lebanon');

    await fireEvent.press(screen.getByRole('button', { name: 'Share' }));
    expect(share).toHaveBeenCalledWith({ message: 'Horch Ehden Nature Reserve, Monday, 2 November 2026\nhttp://localhost:8080/events/horch-ehden' });
  });

  it('explains a cancelled hike', async () => {
    fakeApi({
      ...appConfigRoute,
      'GET /events/horch-ehden': { status: 200, body: { data: sampleEventDetail({ status: 'cancelled', cancellation_reason: 'Bad weather forecast', registration: { open: false, closes_at: '2026-11-01T22:00:00+00:00', waitlist_open: false, requires_level_acknowledgement: false } }) } },
    });
    await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

    expect(await screen.findByText('This hike is cancelled')).toBeOnTheScreen();
    expect(screen.getByText('Bad weather forecast')).toBeOnTheScreen();
    expect(screen.queryByText('4 seats left')).not.toBeOnTheScreen();
  });

  it('says when a hike does not exist', async () => {
    fakeApi({ ...appConfigRoute });
    await renderRouter('./src/app', { initialUrl: '/events/no-such-hike' });

    expect(await screen.findByText("This hike doesn't exist or isn't public any more.")).toBeOnTheScreen();
    expect(screen.queryByRole('button', { name: 'Try again' })).not.toBeOnTheScreen();
  });
});
