import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, quietTabs, sampleEventDetail, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

const ok = (data: unknown) => ({ status: 200, body: { data } });
const admin = { ...sampleMember, organizer: { can_mark_attendance: true, can_hand_over_perks: true, can_post_updates: true } };

it('sends an update to everyone booked and lists the ones sent before', async () => {
  let posted = false;
  const earlier = { message: 'Bring a rain jacket.', posted_at: '2026-10-30T09:00:00+00:00' };
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /me': ok(admin),
    'GET /me/bookings': ok([]),
    'GET /events/horch-ehden': () =>
      ok(sampleEventDetail({ updates: posted ? [{ message: 'The bus leaves at 07:30.', posted_at: '2026-10-31T06:00:00+00:00' }, earlier] : [earlier] })),
    'POST /organizer/events/horch-ehden/updates': () => {
      posted = true;
      return { status: 201, body: { data: { message: 'The bus leaves at 07:30.', posted_at: '2026-10-31T06:00:00+00:00', emailed_count: 12 } } };
    },
  });
  await renderRouter('./src/app', { initialUrl: '/hike-update?slug=horch-ehden' });

  expect(await screen.findByText('Bring a rain jacket.')).toBeOnTheScreen();
  await fireEvent.changeText(screen.getByLabelText('Your update'), 'The bus leaves at 07:30.');
  await fireEvent.press(screen.getByRole('button', { name: 'Send to hikers' }));

  expect(await screen.findByText('Sent. 12 hikers were emailed.')).toBeOnTheScreen();
  expect(calls.find((call) => call.method === 'POST')?.body).toEqual({ message: 'The bus leaves at 07:30.' });
  expect(await screen.findByText('The bus leaves at 07:30.')).toBeOnTheScreen();
  expect(screen.getByLabelText('Your update')).toHaveDisplayValue('');
});

it("shows the API's message under the field", async () => {
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /me': ok(admin),
    'GET /me/bookings': ok([]),
    'GET /events/horch-ehden': ok(sampleEventDetail()),
    'POST /organizer/events/horch-ehden/updates': { status: 422, body: { message: 'The message field is required.', errors: { message: ['The message field is required.'] } } },
  });
  await renderRouter('./src/app', { initialUrl: '/hike-update?slug=horch-ehden' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Send to hikers' }));

  expect(await screen.findByText('The message field is required.')).toBeOnTheScreen();
  expect(screen.getByText('No updates sent for this hike yet.')).toBeOnTheScreen();
});
