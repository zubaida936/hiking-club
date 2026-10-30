import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, quietTabs, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

it('signs up for next destination alerts from Home', async () => {
  let attempts = 0;
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'POST /newsletter': () => {
      attempts += 1;
      return attempts === 1
        ? { status: 422, body: { message: 'Enter your email or your phone number.', errors: { email: ['Enter your email or your phone number.'] } } }
        : { status: 202, body: { message: 'Almost there! Check your inbox and click the link to confirm your email.' } };
    },
  });
  await renderRouter('./src/app', { initialUrl: '/' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Notify me' }));
  expect(await screen.findByText('Enter your email or your phone number.')).toBeOnTheScreen();

  await fireEvent.changeText(screen.getByLabelText('Email'), ' maya@example.com ');
  await fireEvent.press(screen.getByRole('button', { name: 'Notify me' }));

  expect(await screen.findByText('Almost there! Check your inbox and click the link to confirm your email.')).toBeOnTheScreen();
  expect(calls.filter((call) => call.method === 'POST').at(-1)?.body).toEqual({ email: 'maya@example.com', phone: null });
  expect(screen.queryByRole('button', { name: 'Notify me' })).toBeNull();
});

it("fills in a member's email and phone once the account has loaded", async () => {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /me': { status: 200, body: { data: sampleMember } }, 'GET /me/bookings': { status: 200, body: { data: [] } } });
  await renderRouter('./src/app', { initialUrl: '/' });

  expect(await screen.findByDisplayValue('rima@example.com')).toBeOnTheScreen();
  expect(screen.getByDisplayValue('+96170123456')).toBeOnTheScreen();
});
