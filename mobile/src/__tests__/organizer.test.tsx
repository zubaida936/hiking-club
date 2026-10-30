import AsyncStorage from '@react-native-async-storage/async-storage';
import { router } from 'expo-router';
import { act, fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';

import type { CheckIn, OrganizerHike } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleEvent, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

beforeEach(async () => {
  await AsyncStorage.clear();
  jest.clearAllMocks();
});

const ok = (data: unknown) => ({ status: 200, body: { data } });
const guide = { ...sampleMember, organizer: { can_mark_attendance: true } };
const hike: OrganizerHike = { event: sampleEvent(), confirmed: 2, checked_in: 0, check_in_open: true };
const person = (overrides: Partial<CheckIn> = {}): CheckIn => ({
  reference: 'MAYA0001',
  name: 'Maya Saad',
  phone: '+96171222333',
  status: { key: 'confirmed', label: 'Confirmed' },
  pickup: { point: 'Beirut - Jnah', time: '07:30' },
  emergency_contact: { name: 'Sami Saad', phone: '+9613444555' },
  notes: null,
  payment: { state: 'due', amount: '$30', method: 'Whish Money transfer' },
  attendance: null,
  can_check_in: true,
  ...overrides,
});
const list = [person(), person({ reference: 'ZIAD0002', name: 'Ziad Khoury', emergency_contact: { name: 'Lina Khoury', phone: '+9613555666' }, payment: { state: 'paid', amount: '$30', method: null } })];
const listPath = '/organizer/events/horch-ehden/registrations';
const routes = { ...appConfigRoute, ...quietTabs, 'GET /me': ok(guide), 'GET /organizer/events': ok([hike]) };

it('opens a hike from the Organizer tab and checks a hiker in', async () => {
  const calls = fakeApi({ ...routes, [`GET ${listPath}`]: ok(list), 'PUT /organizer/registrations/MAYA0001/attendance': ok(person({ attendance: 'attended' })) });
  // Like an organizer: the tab appears once their account has loaded, then they open it.
  await renderRouter('./src/app', { initialUrl: '/' });
  const hikeLink = await screen.findByRole('link', { name: /Horch Ehden Nature Reserve/ });
  await act(() => router.navigate('/organizer'));
  await fireEvent.press(hikeLink);
  expect(screen).toHavePathname('/check-in/horch-ehden');
  expect(await screen.findByText('Maya Saad')).toBeOnTheScreen();
  expect(screen.getByText('To pay: $30 (Whish Money transfer)')).toBeOnTheScreen();
  expect(screen.getByText('Emergency: Sami Saad, +9613444555')).toBeOnTheScreen();
  expect(screen.getByText('0 / 2 checked in')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Attended: Maya Saad' }));

  expect(await screen.findByText('1 / 2 checked in')).toBeOnTheScreen();
  await waitFor(() => expect(calls.some((c) => c.method === 'PUT')).toBe(true));
  expect(calls.find((c) => c.method === 'PUT')?.body).toEqual({ attendance: 'attended' });
});

it('keeps marks made without signal and sends them when the signal is back', async () => {
  let online = false;
  const calls = fakeApi({
    ...routes,
    [`GET ${listPath}`]: ok(list),
    'PUT /organizer/registrations/MAYA0001/attendance': () => (online ? ok(person({ attendance: 'attended' })) : 'offline'),
  });
  await renderRouter('./src/app', { initialUrl: '/check-in/horch-ehden' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Attended: Maya Saad' }));
  expect(await screen.findByText('Not sent yet')).toBeOnTheScreen();
  expect(screen.getByText('1 / 2 checked in')).toBeOnTheScreen();

  online = true;
  await fireEvent(screen.getByTestId('check-in-list'), 'refresh');
  await waitFor(() => expect(screen.queryByText('Not sent yet')).toBeNull());
  expect(calls.filter((c) => c.method === 'PUT')).toHaveLength(2);
});

it('undoes a mark the server refuses and shows why', async () => {
  fakeApi({
    ...routes,
    [`GET ${listPath}`]: ok(list),
    'PUT /organizer/registrations/MAYA0001/attendance': { status: 422, body: { message: 'Only confirmed bookings can be marked.' } },
  });
  await renderRouter('./src/app', { initialUrl: '/check-in/horch-ehden' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Attended: Maya Saad' }));

  expect(await screen.findByText('Only confirmed bookings can be marked.')).toBeOnTheScreen();
  expect(screen.getByText('0 / 2 checked in')).toBeOnTheScreen();
});

it('shows the list saved on the phone when there is no signal', async () => {
  await AsyncStorage.setItem('hiking.organizer', JSON.stringify({ owner: 7, lists: { 'horch-ehden': { items: list, savedAt: '2026-11-01T05:00:00+00:00' } }, pending: [] }));
  fakeApi({ ...routes, [`GET ${listPath}`]: 'offline' });
  await renderRouter('./src/app', { initialUrl: '/check-in/horch-ehden' });

  expect(await screen.findByText(/No signal: showing the list saved at/)).toBeOnTheScreen();
  expect(screen.getByText('Ziad Khoury')).toBeOnTheScreen();
  expect(screen.getByText('Paid')).toBeOnTheScreen();
});

it('deletes the saved lists when nobody is logged in', async () => {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue(null);
  await AsyncStorage.setItem('hiking.organizer', JSON.stringify({ owner: 7, lists: { 'horch-ehden': { items: list, savedAt: '2026-11-01T05:00:00+00:00' } }, pending: [] }));
  fakeApi({ ...appConfigRoute, ...quietTabs });
  await renderRouter('./src/app', { initialUrl: '/' });

  await waitFor(async () => expect(await AsyncStorage.getItem('hiking.organizer')).toBeNull());
});
