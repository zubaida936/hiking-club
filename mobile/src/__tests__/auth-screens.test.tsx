// Activation and password-reset screens: in their own file because, after the login tests in auth.test.tsx,
// leftover async work from earlier renders left the test router stuck (navigation never happened).
import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { clearToken } from '@/auth/token';
import { appConfigRoute, fakeApi, sampleMember } from '@/test-utils/fake-api';

// The token lives in memory instead of the phone's secure storage.
jest.mock('@/auth/token', () => {
  let stored: string | null = null;
  return {
    getToken: jest.fn(async () => stored),
    saveToken: jest.fn(async (token: string) => {
      stored = token;
    }),
    clearToken: jest.fn(async () => {
      stored = null;
    }),
  };
});

jest.mock('expo-device', () => ({ modelName: 'Pixel 8' }));

const rima = sampleMember;
const appConfig = appConfigRoute;

async function openAccount() {
  await renderRouter('./src/app', { initialUrl: '/account' });
}

beforeEach(async () => {
  await clearToken();
  jest.clearAllMocks();
});

it('activates an account and comes back logged in', async () => {
  const calls = fakeApi({ ...appConfig, 'POST /auth/activate': { status: 201, body: { token: '2|new', member: rima } } });
  await openAccount();

  await fireEvent.press(await screen.findByText('Booked a hike before? Activate your account'));
  await fireEvent.changeText(await screen.findByLabelText('Phone number'), '+96170123456');
  await fireEvent.changeText(screen.getByLabelText('Booking reference'), 'K7F3Q2MZ');
  await fireEvent.changeText(screen.getByLabelText('New password'), 'trail-mix-42');
  await fireEvent.changeText(screen.getByLabelText('Repeat the password'), 'trail-mix-42');
  await fireEvent.press(screen.getByRole('button', { name: 'Activate' }));

  expect(await screen.findByText('Rima Haddad')).toBeOnTheScreen();
  expect(screen).toHavePathname('/account');
  expect(calls.find((c) => c.path === '/auth/activate')?.body).toEqual({
    phone: '+96170123456', reference: 'K7F3Q2MZ', password: 'trail-mix-42', password_confirmation: 'trail-mix-42', device_name: 'Pixel 8',
  });
});

it('resets the password with the emailed code', async () => {
  const calls = fakeApi({
    ...appConfig,
    'POST /auth/password/forgot': { status: 202, body: { message: 'If this number has an account, we sent it a code. It is valid for 10 minutes.' } },
    'POST /auth/password/reset': { status: 422, body: { message: 'This code is wrong or has expired. Ask for a new one.' } },
  });
  await openAccount();

  await fireEvent.press(await screen.findByText('Forgot your password?'));
  await fireEvent.changeText(await screen.findByLabelText('Phone number'), '+96170123456');
  await fireEvent.press(screen.getByRole('button', { name: 'Send the code' }));

  expect(await screen.findByText('If this number has an account, we sent it a code. It is valid for 10 minutes.')).toBeOnTheScreen();
  await fireEvent.changeText(screen.getByLabelText('Code'), '123456');
  await fireEvent.changeText(screen.getByLabelText('New password'), 'new-pass-123');
  await fireEvent.changeText(screen.getByLabelText('Repeat the password'), 'new-pass-123');
  await fireEvent.press(screen.getByRole('button', { name: 'Set the new password' }));

  expect(await screen.findByText('This code is wrong or has expired. Ask for a new one.')).toBeOnTheScreen();
  expect(calls.find((c) => c.path === '/auth/password/reset')?.body).toMatchObject({ phone: '+96170123456', code: '123456', device_name: 'Pixel 8' });
});
