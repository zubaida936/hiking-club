import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => '1|secret'), saveToken: jest.fn(), clearToken: jest.fn() }));

const page = { status: 200, body: { data: [], links: {}, meta: { current_page: 1, last_page: 1, total: 0 } } };
const base = { ...appConfigRoute, 'GET /me': { status: 200, body: { data: sampleMember } }, 'GET /me/bookings': page };

async function openProfile() {
  await renderRouter('./src/app', { initialUrl: '/account' });
  await fireEvent.press(await screen.findByText('Edit my profile'));
  await screen.findByLabelText('Full name');
}

it('saves name and emergency contact, and shows them on the account tab', async () => {
  const saved = { ...sampleMember, name: 'Rima Khoury', emergency_contact: { name: 'Karim Haddad', phone: '+9613111222' } };
  const calls = fakeApi({ ...base, 'PUT /me/profile': { status: 200, body: { data: saved } } });
  await openProfile();

  expect(screen.getByText('+96170123456')).toBeOnTheScreen();
  expect(screen.getByText('Your phone number is your login. To change it, message us on WhatsApp.')).toBeOnTheScreen();
  // No password asked while the email stays the same.
  expect(screen.queryByLabelText('Current password (to change your email)')).not.toBeOnTheScreen();

  await fireEvent.changeText(screen.getByLabelText('Full name'), 'Rima Khoury');
  await fireEvent.changeText(screen.getByLabelText('Name'), 'Karim Haddad');
  await fireEvent.changeText(screen.getByLabelText('Phone'), '+961 3 111 222');
  await fireEvent.press(screen.getByRole('button', { name: 'Save' }));

  expect(await screen.findByText('Rima Khoury')).toBeOnTheScreen();
  expect(screen).toHavePathname('/account');
  expect(calls.find((c) => c.method === 'PUT')?.body).toEqual({
    name: 'Rima Khoury',
    email: 'rima@example.com',
    current_password: null,
    emergency_contact_name: 'Karim Haddad',
    emergency_contact_phone: '+961 3 111 222',
  });
});

it('asks for the password when the email changes and shows the API message', async () => {
  const calls = fakeApi({
    ...base,
    'PUT /me/profile': { status: 422, body: { message: 'This password is not right.', errors: { current_password: ['This password is not right.'] } } },
  });
  await openProfile();

  await fireEvent.changeText(screen.getByLabelText('Email (password-reset codes are sent here)'), 'new@example.com');
  await fireEvent.changeText(await screen.findByLabelText('Current password (to change your email)'), 'wrong');
  await fireEvent.press(screen.getByRole('button', { name: 'Save' }));

  expect(await screen.findByText('This password is not right.')).toBeOnTheScreen();
  expect(screen).toHavePathname('/edit-profile');
  expect(calls.find((c) => c.method === 'PUT')?.body).toMatchObject({ email: 'new@example.com', current_password: 'wrong' });
});
