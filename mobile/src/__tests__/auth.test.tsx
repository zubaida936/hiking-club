import { fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';

import { clearToken, getToken, saveToken } from '@/auth/token';
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

it('logs in with phone and password and shows the member', async () => {
  const calls = fakeApi({ ...appConfig, 'POST /auth/login': { status: 201, body: { token: '1|secret', member: rima } } });
  await openAccount();

  await fireEvent.changeText(await screen.findByLabelText('Phone number'), '+961 70 123 456');
  await fireEvent.changeText(screen.getByLabelText('Password'), 'secret-pass');
  await fireEvent.press(screen.getByRole('button', { name: 'Log in' }));

  expect(await screen.findByText('Rima Haddad')).toBeOnTheScreen();
  expect(screen.getByText('Trekker')).toBeOnTheScreen();
  expect(screen.getByText('140 points')).toBeOnTheScreen();
  expect(screen.getByText('60 points to Mountaineer')).toBeOnTheScreen();
  expect(screen.getByText('#3 on the leaderboard')).toBeOnTheScreen();
  expect(calls.find((c) => c.path === '/auth/login')?.body).toEqual({ phone: '+961 70 123 456', password: 'secret-pass', device_name: 'Pixel 8' });
  expect(saveToken).toHaveBeenCalledWith('1|secret');
});

it('shows the API message under the field', async () => {
  fakeApi({
    ...appConfig,
    'POST /auth/login': { status: 422, body: { message: "This phone number and password don't match an account.", errors: { phone: ["This phone number and password don't match an account."] } } },
  });
  await openAccount();

  await fireEvent.press(await screen.findByRole('button', { name: 'Log in' }));

  expect(await screen.findByText("This phone number and password don't match an account.")).toBeOnTheScreen();
  expect(saveToken).not.toHaveBeenCalled();
});

it('stays logged in, and logs out', async () => {
  await saveToken('1|secret');
  const calls = fakeApi({ ...appConfig, 'GET /me': { status: 200, body: { data: rima } }, 'POST /auth/logout': { status: 204 } });
  await openAccount();

  expect(await screen.findByText('Rima Haddad')).toBeOnTheScreen();
  expect(calls.find((c) => c.path === '/me')?.auth).toBe('Bearer 1|secret');

  await fireEvent.press(screen.getByRole('button', { name: 'Log out' }));

  expect(await screen.findByLabelText('Phone number')).toBeOnTheScreen();
  expect(calls.some((c) => c.method === 'POST' && c.path === '/auth/logout')).toBe(true);
  expect(await getToken()).toBeNull();
});

it('forgets a token the server no longer accepts (deactivated, new password elsewhere)', async () => {
  await saveToken('1|revoked');
  fakeApi({ ...appConfig, 'GET /me': { status: 401, body: { message: 'This account has been deactivated.' } } });
  await openAccount();

  expect(await screen.findByLabelText('Phone number')).toBeOnTheScreen();
  await waitFor(async () => expect(await getToken()).toBeNull());
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
