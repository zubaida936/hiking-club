import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import * as WebBrowser from 'expo-web-browser';
import { Alert } from 'react-native';

import { appConfigRoute, fakeApi, sampleBooking, sampleBookingForm, sampleEventDetail, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
jest.mock('expo-web-browser', () => ({ openBrowserAsync: jest.fn(async () => ({ type: 'dismiss' })) }));

const ok = (data: unknown, status = 200) => ({ status, body: { data } });
const bookingPath = '/bookings/secret-booking-token';

async function fill(label: string, value: string) {
  await fireEvent.changeText(screen.getByLabelText(label), value);
}

describe('booking form', () => {
  it('books from the hike detail and shows the confirmation with transfer instructions', async () => {
    const calls = fakeApi({
      ...appConfigRoute,
      'GET /events/horch-ehden': ok(sampleEventDetail()),
      'GET /events/horch-ehden/booking-form': ok(sampleBookingForm()),
      'POST /events/horch-ehden/bookings': ok(sampleBooking(), 201),
      [`GET ${bookingPath}`]: ok(sampleBooking()),
    });
    await renderRouter('./src/app', { initialUrl: '/events/horch-ehden' });

    await fireEvent.press(await screen.findByRole('button', { name: 'Reserve your spot' }));
    await screen.findByLabelText('Full name');
    await fill('Full name', 'Karim Saad');
    await fill('Phone (WhatsApp)', '+961 71 222 333');
    await fireEvent.press(screen.getByRole('radio', { name: 'Jounieh' }));
    await fill('Name', 'Maya Saad');
    await fill('Phone', '+961 3 444 555');
    await fireEvent.press(screen.getByRole('radio', { name: 'Whish Money transfer' }));
    await fireEvent.press(screen.getByRole('checkbox', { name: 'I take part at my own risk.' }));
    await fireEvent.press(screen.getByRole('button', { name: 'Book · $30' }));

    expect(await screen.findByText("You're booked!")).toBeOnTheScreen();
    expect(screen).toHavePathname(bookingPath);
    expect(screen.getByText('K7F3Q2MZ')).toBeOnTheScreen();
    expect(screen.getByText('Open Whish Money and send $30 to:')).toBeOnTheScreen();
    expect(screen.getByText('Write K7F3Q2MZ in the transfer note.')).toBeOnTheScreen();
    expect(calls.find((c) => c.method === 'POST' && c.path === '/events/horch-ehden/bookings')?.body).toEqual({
      name: 'Karim Saad',
      phone: '+961 71 222 333',
      email: null,
      meeting_point_id: 12,
      emergency_contact_name: 'Maya Saad',
      emergency_contact_phone: '+961 3 444 555',
      notes: null,
      referrer_phone: null,
      reward: null,
      payment_method: 'whish',
      level_acknowledged: false,
      terms: true,
    });
  });

  it('shows the API messages under the fields and for club rules', async () => {
    let answer = {
      status: 422,
      body: { message: 'Please accept the terms to book your seat. (and 1 more error)', errors: { terms: ['Please accept the terms to book your seat.'], meeting_point_id: ['Choose where we pick you up.'] } },
    };
    fakeApi({
      ...appConfigRoute,
      'GET /events/horch-ehden/booking-form': ok(sampleBookingForm()),
      'POST /events/horch-ehden/bookings': () => answer,
    });
    await renderRouter('./src/app', { initialUrl: '/book/horch-ehden' });

    await fireEvent.press(await screen.findByRole('button', { name: 'Book · $30' }));
    expect(await screen.findByText('Please accept the terms to book your seat.')).toBeOnTheScreen();
    expect(screen.getByText('Choose where we pick you up.')).toBeOnTheScreen();

    answer = { status: 422, body: { message: 'This phone number is already booked on this hike.' } } as typeof answer;
    await fireEvent.press(screen.getByRole('button', { name: 'Book · $30' }));
    expect(await screen.findByText('This phone number is already booked on this hike.')).toBeOnTheScreen();
  });

  it('fills in the member and drops the payment choice for a free reward', async () => {
    fakeApi({
      ...appConfigRoute,
      'GET /events/horch-ehden/booking-form': ok(
        sampleBookingForm({
          member: { name: sampleMember.name, phone: sampleMember.phone, email: sampleMember.email },
          rewards: [{ key: 'mountaineer_free_moderate', label: 'One free Moderate (Level 2) hike', percent: 100, fee_after: 'Free' }],
          requires_level_acknowledgement: true,
          level_label: 'Level 3 · Advanced',
        }),
      ),
    });
    await renderRouter('./src/app', { initialUrl: '/book/horch-ehden' });

    expect(await screen.findByDisplayValue('Rima Haddad')).toBeOnTheScreen();
    expect(screen.getByDisplayValue('+96170123456')).toBeOnTheScreen();
    expect(screen.getByRole('checkbox', { name: 'I confirm I meet the requirements for Level 3 · Advanced.' })).toBeOnTheScreen();
    expect(screen.getByText('How will you pay?')).toBeOnTheScreen();

    await fireEvent.press(screen.getByRole('radio', { name: 'One free Moderate (Level 2) hike' }));

    expect(screen.queryByText('How will you pay?')).not.toBeOnTheScreen();
    expect(screen.getByRole('button', { name: 'Book · Free' })).toBeOnTheScreen();
  });

  it('explains locked X events', async () => {
    fakeApi({ ...appConfigRoute, 'GET /events/x/booking-form': ok(sampleBookingForm({ x_locked: true })) });
    await renderRouter('./src/app', { initialUrl: '/book/x' });

    expect(await screen.findByText(/X Events are for hikers approved for X Events/)).toBeOnTheScreen();
  });

  it('explains closed bookings', async () => {
    fakeApi({ ...appConfigRoute, 'GET /events/closed/booking-form': ok(sampleBookingForm({ open: false })) });
    await renderRouter('./src/app', { initialUrl: '/book/closed' });

    expect(await screen.findByText('Bookings for this hike are closed.')).toBeOnTheScreen();
  });
});

describe('booking confirmation', () => {
  it('sends the Whish / OMT transaction number', async () => {
    const reported = sampleBooking();
    if (reported.payment.instructions?.type === 'transfer') reported.payment.instructions.reported_transaction_number = '1234567890';
    const calls = fakeApi({ ...appConfigRoute, [`GET ${bookingPath}`]: ok(sampleBooking()), [`POST ${bookingPath}/transfer`]: ok(reported) });
    await renderRouter('./src/app', { initialUrl: bookingPath });

    await fireEvent.changeText(await screen.findByLabelText('Transaction number'), '1234567890');
    await fireEvent.press(screen.getByRole('button', { name: 'Send' }));

    expect(await screen.findByText("We received transaction number 1234567890. We'll confirm your payment soon.")).toBeOnTheScreen();
    expect(calls.find((c) => c.path === `${bookingPath}/transfer`)?.body).toEqual({ payment_reference: '1234567890' });
  });

  it('pays online in the browser and shows the booking paid', async () => {
    let paid = false;
    fakeApi({
      ...appConfigRoute,
      [`GET ${bookingPath}`]: () =>
        ok(sampleBooking({ payment: paid
          ? { state: 'paid', method: { key: 'online', label: 'Online payment' }, paid_at: '2026-10-29T11:00:00+00:00', refund_due: false, instructions: null }
          : { state: 'due', method: { key: 'online', label: 'Online payment' }, paid_at: null, refund_due: false, instructions: { type: 'online' } } })),
      [`POST ${bookingPath}/pay`]: { status: 200, body: { checkout_url: 'https://pay.example/checkout/1' } },
    });
    jest.mocked(WebBrowser.openBrowserAsync).mockImplementation(async () => {
      paid = true; // the hiker paid on the provider page
      return { type: WebBrowser.WebBrowserResultType?.DISMISS ?? 'dismiss' } as WebBrowser.WebBrowserResult;
    });
    await renderRouter('./src/app', { initialUrl: bookingPath });

    await fireEvent.press(await screen.findByRole('button', { name: 'Pay $30 now' }));

    expect(await screen.findByText('Paid. Thank you!')).toBeOnTheScreen();
    expect(WebBrowser.openBrowserAsync).toHaveBeenCalledWith('https://pay.example/checkout/1');
  });

  it('lets the owner cancel after confirming', async () => {
    const alert = jest.spyOn(Alert, 'alert');
    const cancelled = sampleBooking({
      status: { key: 'cancelled', label: 'Cancelled' },
      payment: { state: 'none', method: null, paid_at: null, refund_due: false, instructions: null },
      cancellation: { cancelled_at: '2026-10-29T12:00:00+00:00', reason: 'Cancelled by the hiker', can_cancel: false, until: null },
    });
    const calls = fakeApi({
      ...appConfigRoute,
      [`GET ${bookingPath}`]: ok(sampleBooking({ cancellation: { cancelled_at: null, reason: null, can_cancel: true, until: '2026-11-01T22:00:00+00:00' } })),
      [`POST ${bookingPath}/cancel`]: ok(cancelled),
    });
    await renderRouter('./src/app', { initialUrl: bookingPath });

    await fireEvent.press(await screen.findByRole('button', { name: 'Cancel my booking' }));
    expect(alert).toHaveBeenCalledWith('Cancel this booking?', 'Your seat goes to the next hiker.', expect.any(Array));
    // The hiker taps "Cancel booking" in the confirmation dialog.
    const buttons = alert.mock.calls[0][2] ?? [];
    await act(async () => buttons.find((button) => button.style === 'destructive')?.onPress?.());

    expect(await screen.findByText('Booking cancelled')).toBeOnTheScreen();
    expect(screen.getByText('Cancelled by the hiker')).toBeOnTheScreen();
    expect(calls.some((c) => c.method === 'POST' && c.path === `${bookingPath}/cancel`)).toBe(true);
  });

  it('says when the booking link is wrong', async () => {
    fakeApi({ ...appConfigRoute });
    await renderRouter('./src/app', { initialUrl: '/bookings/nope' });

    expect(await screen.findByText("We couldn't find this booking.")).toBeOnTheScreen();
  });
});
