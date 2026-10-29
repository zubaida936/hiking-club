import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Alert } from 'react-native';

import { clearToken } from '@/auth/token';
import { appConfigRoute, fakeApi, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn(async () => undefined) }));

// Each test starts with no recorded calls and the real Alert (the spy would keep the previous test's dialog).
beforeEach(() => jest.clearAllMocks());
afterEach(() => jest.restoreAllMocks());

const page = { status: 200, body: { data: [], links: {}, meta: { current_page: 1, last_page: 1, total: 0 } } };
const base = { ...appConfigRoute, 'GET /me': { status: 200, body: { data: sampleMember } }, 'GET /me/bookings': page };

/** Opens the screen from the account tab and confirms the deletion with this password. */
async function deleteWith(password: string) {
  const alert = jest.spyOn(Alert, 'alert');
  await renderRouter('./src/app', { initialUrl: '/account' });

  await fireEvent.press(await screen.findByText('Delete my account'));
  await fireEvent.changeText(await screen.findByLabelText('Your password, to confirm'), password);
  await fireEvent.press(screen.getByRole('button', { name: 'Delete my account' }));

  expect(alert).toHaveBeenCalledWith('Delete your account?', 'Your login, points and rewards are deleted for good.', expect.any(Array));
  const buttons = alert.mock.calls[0][2] ?? [];
  await act(async () => buttons.find((button) => button.style === 'destructive')?.onPress?.());

  return alert;
}

it('deletes the account after the password and a confirmation, then forgets the login', async () => {
  const calls = fakeApi({ ...base, 'DELETE /me': { status: 204 } });

  const alert = await deleteWith('secret-pass');

  expect(await screen.findByLabelText('Phone number')).toBeOnTheScreen(); // back on the login form
  expect(screen).toHavePathname('/account');
  expect(calls.find((c) => c.method === 'DELETE')?.body).toEqual({ password: 'secret-pass' });
  expect(clearToken).toHaveBeenCalled();
  expect(alert).toHaveBeenLastCalledWith('Your account is deleted. Thank you for hiking with us!');
});

it("shows the server's rule and keeps the login", async () => {
  fakeApi({
    ...base,
    'DELETE /me': { status: 422, body: { message: 'You still have upcoming bookings or open shop orders. Cancel them first (or message us on WhatsApp), then delete your account.' } },
  });

  await deleteWith('secret-pass');

  expect(await screen.findByText(/You still have upcoming bookings or open shop orders/)).toBeOnTheScreen();
  expect(screen).toHavePathname('/delete-account');
  expect(clearToken).not.toHaveBeenCalled();
});
