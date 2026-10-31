import AsyncStorage from '@react-native-async-storage/async-storage';
import { act, renderRouter, screen, waitFor } from 'expo-router/testing-library';

import type { CheckIn } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleBooking, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

// A stand-in camera: the test "shows" it a code by calling the screen's onBarcodeScanned.
let scan: ((result: { type: string; data: string }) => void) | undefined;
jest.mock('expo-camera', () => ({
  CameraView: ({ onBarcodeScanned }: { onBarcodeScanned: typeof scan }) => {
    scan = onBarcodeScanned;
    return null;
  },
  useCameraPermissions: () => [{ granted: true, canAskAgain: true }, jest.fn()],
}));

beforeEach(async () => {
  await AsyncStorage.clear();
  scan = undefined;
});

const ok = (data: unknown) => ({ status: 200, body: { data } });
const maya: CheckIn = {
  reference: 'MAYA0001',
  name: 'Maya Saad',
  phone: '+96171222333',
  status: { key: 'confirmed', label: 'Confirmed' },
  pickup: null,
  emergency_contact: { name: 'Sami Saad', phone: '+9613444555' },
  notes: null,
  payment: { state: 'paid', amount: '$30', method: null },
  attendance: null,
  can_check_in: true,
};
const karim: CheckIn = { ...maya, reference: 'KARI0002', name: 'Karim Haddad', status: { key: 'pending', label: 'Awaiting approval' }, can_check_in: false };

it('checks in the hiker whose QR code is scanned, from the list saved on the phone', async () => {
  await AsyncStorage.setItem('hiking.organizer', JSON.stringify({ owner: 7, lists: { 'horch-ehden': { items: [maya, karim], savedAt: '2026-11-01T05:00:00+00:00' } }, pending: [] }));
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /me': ok({ ...sampleMember, organizer: { can_mark_attendance: true, can_hand_over_perks: true, can_post_updates: false } }),
    'PUT /organizer/registrations/MAYA0001/attendance': ok({ ...maya, attendance: 'attended' }),
  });
  await renderRouter('./src/app', { initialUrl: '/check-in/scan?slug=horch-ehden' });
  // The organizer's saved list is loaded (Maya confirmed, Karim awaiting approval).
  expect(await screen.findByText('0 / 1 checked in')).toBeOnTheScreen();

  await act(async () => scan?.({ type: 'qr', data: 'https://example.com' }));
  expect(screen.getByText("That's not a booking QR code.")).toBeOnTheScreen();
  await act(async () => scan?.({ type: 'qr', data: 'PERK-12-highlander_gift_card-0123456789abcdef' }));
  expect(screen.getByText('That\'s a reward code: use "Scan a reward code" on the Organizer screen (More tab).')).toBeOnTheScreen();

  await act(async () => scan?.({ type: 'qr', data: 'hikingclub-booking:NOPE9999' }));
  expect(screen.getByText("This booking isn't on this hike's list.")).toBeOnTheScreen();

  await act(async () => scan?.({ type: 'qr', data: 'hikingclub-booking:KARI0002' }));
  expect(screen.getByText("Karim Haddad's booking isn't confirmed: check with the organizers.")).toBeOnTheScreen();

  await act(async () => scan?.({ type: 'qr', data: 'hikingclub-booking:MAYA0001' }));
  expect(screen.getByText('✓ Maya Saad checked in')).toBeOnTheScreen();
  expect(screen.getByText('1 / 1 checked in')).toBeOnTheScreen();
  await waitFor(() => expect(calls.find((c) => c.method === 'PUT')?.body).toEqual({ attendance: 'attended' }));
});

it('shows a confirmed booking its check-in QR code', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /me': ok(sampleMember), 'GET /bookings/secret-booking-token': ok(sampleBooking()) });
  await renderRouter('./src/app', { initialUrl: '/bookings/secret-booking-token' });

  expect(await screen.findByText('Your check-in code')).toBeOnTheScreen();
  expect(screen.getByTestId('qr-code')).toHaveTextContent('hikingclub-booking:K7F3Q2MZ');
});
