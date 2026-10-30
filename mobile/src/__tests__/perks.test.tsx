import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { PerkState, RewardState } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleMember } from '@/test-utils/fake-api';

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

beforeEach(() => {
  scan = undefined;
});

const ok = (data: unknown) => ({ status: 200, body: { data } });
const CODE = 'PERK-12-highlander_gift_card-0123456789abcdef';
const guide = { ...sampleMember, organizer: { can_mark_attendance: true, can_hand_over_perks: true } };
const routes = { ...appConfigRoute, ...quietTabs, 'GET /me': ok(guide), 'GET /me/bookings': ok([]) };
const perk = (overrides: Partial<PerkState> = {}): PerkState => ({
  member: { name: 'Rima Haddad', phone: '+96170123456' },
  reward: { key: 'highlander_gift_card', label: 'Gift card', rank: 'Highlander' },
  available: true,
  unavailable_reason: null,
  handed_over_at: null,
  ...overrides,
});

it('shows the member the QR code of a perk they can receive', async () => {
  const giftCard: RewardState = {
    key: 'highlander_gift_card',
    label: 'Gift card',
    kind: 'perk',
    rank: { key: 'highlander', label: 'Highlander' },
    how_it_works: 'Handed over by the organizers.',
    unlocked: true,
    locked_reason: null,
    uses_left: 1,
    used: [],
    perk_code: CODE,
  };
  fakeApi({ ...routes, 'GET /me/rewards': ok([giftCard]) });
  await renderRouter('./src/app', { initialUrl: '/my-rewards' });

  expect(await screen.findByText('Show this to an organizer')).toBeOnTheScreen();
  expect(screen.getByTestId('qr-code')).toHaveTextContent(CODE);
});

it('lets an organizer scan a reward code and hand the perk over', async () => {
  const calls = fakeApi({
    ...routes,
    [`GET /organizer/perks/${CODE}`]: ok(perk()),
    [`POST /organizer/perks/${CODE}/hand-over`]: { status: 201, body: { data: perk({ available: false, unavailable_reason: 'Already handed over.', handed_over_at: '2026-11-01T08:00:00+00:00' }) } },
  });
  await renderRouter('./src/app', { initialUrl: '/perks/scan' });

  await act(async () => scan?.({ type: 'qr', data: 'hikingclub-booking:K7F3Q2MZ' }));
  expect(await screen.findByText("That's not a reward QR code.")).toBeOnTheScreen();

  await act(async () => scan?.({ type: 'qr', data: CODE }));
  expect(await screen.findByText('Rima Haddad')).toBeOnTheScreen();
  expect(screen.getByText('Gift card')).toBeOnTheScreen();
  expect(screen.getByText('Can be handed over now')).toBeOnTheScreen();

  await fireEvent.changeText(screen.getByLabelText('Note (optional)'), 'Given at the bus');
  await fireEvent.press(screen.getByRole('button', { name: 'Mark handed over' }));

  expect(await screen.findByText('✓ Gift card handed over to Rima Haddad')).toBeOnTheScreen();
  expect(calls.find((call) => call.method === 'POST')?.body).toEqual({ note: 'Given at the bus' });
});

it('says why a perk cannot be handed over', async () => {
  fakeApi({
    ...routes,
    [`GET /organizer/perks/${CODE}`]: ok(perk({ available: false, unavailable_reason: 'Already handed over.', handed_over_at: '2026-10-20T09:00:00+00:00' })),
  });
  await renderRouter('./src/app', { initialUrl: '/perks/scan' });

  await act(async () => scan?.({ type: 'qr', data: CODE }));

  expect(await screen.findByText('Already handed over.')).toBeOnTheScreen();
  expect(screen.queryByRole('button', { name: 'Mark handed over' })).toBeNull();

  await fireEvent.press(screen.getByRole('button', { name: 'Scan another code' }));
  expect(screen.getByText("Point the camera at the QR code on the member's My rewards screen.")).toBeOnTheScreen();
});

it("shows the API's message for an edited code", async () => {
  fakeApi({ ...routes, [`GET /organizer/perks/${CODE}`]: { status: 422, body: { message: "That's not a valid reward code." } } });
  await renderRouter('./src/app', { initialUrl: '/perks/scan' });

  await act(async () => scan?.({ type: 'qr', data: CODE }));

  expect(await screen.findByText("That's not a valid reward code.")).toBeOnTheScreen();
});
