import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Alert } from 'react-native';

import { appConfigRoute, fakeApi, sampleBooking, sampleBookingForm, sampleEventDetail } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
afterEach(() => jest.restoreAllMocks());

const ok = (data: unknown, status = 200) => ({ status, body: { data } });
const bookingPath = '/bookings/secret-booking-token';
const waitlisted = (overrides = {}) =>
  sampleBooking({
    status: { key: 'waitlisted', label: 'On the waitlist' },
    waitlist_position: 2,
    payment: { state: 'after_confirmation', method: { key: 'whish', label: 'Whish Money transfer' }, paid_at: null, refund_due: false, instructions: null },
    ...overrides,
  });

it('joins the waitlist of a full hike and shows the place in line', async () => {
  fakeApi({
    ...appConfigRoute,
    'GET /events/horch-ehden': ok(sampleEventDetail({ seats: { capacity: 30, left: 0, sold_out: true }, registration: { open: true, closes_at: '2026-11-01T22:00:00+00:00', waitlist_open: true, requires_level_acknowledgement: false } })),
    'GET /events/horch-ehden/booking-form': ok(sampleBookingForm({ waitlist: true })),
    'POST /events/horch-ehden/bookings': ok(waitlisted(), 201),
    [`GET ${bookingPath}`]: ok(waitlisted()),
  });
  await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Join the waitlist' }));
  expect(await screen.findByText("This hike is full: you'll join the waitlist and we'll let you know if a seat frees up.")).toBeOnTheScreen();
  await fireEvent.changeText(screen.getByLabelText('Full name'), 'Karim Saad');
  await fireEvent.press(screen.getByRole('checkbox', { name: 'I take part at my own risk.' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Join the waitlist' }));

  expect(await screen.findByText("You're on the waitlist")).toBeOnTheScreen();
  expect(screen.getByText('Number 2 on the waitlist')).toBeOnTheScreen();
  expect(screen.getByText("You'll pay once your seat is confirmed. We'll let you know.")).toBeOnTheScreen();
});

it('lets a member leave the waitlist', async () => {
  const alert = jest.spyOn(Alert, 'alert');
  fakeApi({
    ...appConfigRoute,
    [`GET ${bookingPath}`]: ok(waitlisted({ cancellation: { cancelled_at: null, reason: null, can_cancel: true, until: '2026-11-01T22:00:00+00:00' } })),
    [`POST ${bookingPath}/cancel`]: ok(
      sampleBooking({
        status: { key: 'cancelled', label: 'Cancelled' },
        payment: { state: 'none', method: null, paid_at: null, refund_due: false, instructions: null },
        cancellation: { cancelled_at: '2026-10-30T12:00:00+00:00', reason: 'Cancelled by the hiker', can_cancel: false, until: null },
      }),
    ),
  });
  await renderRouter('./src/app', { initialUrl: bookingPath });

  await fireEvent.press(await screen.findByRole('button', { name: 'Leave the waitlist' }));
  expect(alert).toHaveBeenCalledWith('Leave the waitlist?', 'You lose your place on the waitlist. You can join again later, at the end of the list.', expect.any(Array));
  const buttons = alert.mock.calls[0][2] ?? [];
  await act(async () => buttons.find((button) => button.style === 'destructive')?.onPress?.());

  expect(await screen.findByText('Booking cancelled')).toBeOnTheScreen();
});
