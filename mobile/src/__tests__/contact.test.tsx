import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Linking } from 'react-native';

import type { ContactDetails } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
beforeEach(() => jest.clearAllMocks());

const ok = (data: unknown) => ({ status: 200, body: { data } });
const contact: ContactDetails = {
  address: 'Beirut, Lebanon',
  phone: '+961 70 123 456',
  email: 'hello@hiking.test',
  whatsapp_url: 'https://wa.me/96170123456?text=Hi',
  maps_url: 'https://maps.example/club',
  social: [{ network: 'instagram', label: 'Instagram', url: 'https://instagram.com/club' }],
};
const sent = { status: 202, body: { message: 'Thank you! Your message is on its way. We usually answer within a day.' } };

it('shows the contact details with call, WhatsApp, email, map and social links', async () => {
  const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /contact': ok(contact) });
  await renderRouter('./src/app', { initialUrl: '/contact' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Call +961 70 123 456' }));
  expect(openURL).toHaveBeenLastCalledWith('tel:+96170123456');
  await fireEvent.press(screen.getByRole('button', { name: 'Message us on WhatsApp' }));
  expect(openURL).toHaveBeenLastCalledWith('https://wa.me/96170123456?text=Hi');
  await fireEvent.press(screen.getByRole('button', { name: 'Email hello@hiking.test' }));
  expect(openURL).toHaveBeenLastCalledWith('mailto:hello@hiking.test');
  await fireEvent.press(screen.getByText('Beirut, Lebanon (open the map)'));
  expect(openURL).toHaveBeenLastCalledWith('https://maps.example/club');
  await fireEvent.press(screen.getByText('Instagram'));
  expect(openURL).toHaveBeenLastCalledWith('https://instagram.com/club');
});

it('hides WhatsApp when the club has no number', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /contact': ok({ ...contact, whatsapp_url: null }) });
  await renderRouter('./src/app', { initialUrl: '/contact' });

  expect(await screen.findByRole('button', { name: 'Call +961 70 123 456' })).toBeOnTheScreen();
  expect(screen.queryByRole('button', { name: 'Message us on WhatsApp' })).toBeNull();
});

it('sends the form and shows the messages under the fields', async () => {
  let attempts = 0;
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /contact': ok(contact),
    'POST /contact': () => {
      attempts += 1;
      return attempts === 1 ? { status: 422, body: { message: 'Please write a little more so we can help.', errors: { message: ['Please write a little more so we can help.'] } } } : sent;
    },
  });
  await renderRouter('./src/app', { initialUrl: '/contact' });

  await fireEvent.changeText(await screen.findByLabelText('Your name'), 'Maya Saad');
  await fireEvent.changeText(screen.getByLabelText('Your email (we answer here)'), ' maya@example.com ');
  await fireEvent.changeText(screen.getByLabelText('Your message'), 'Hi');
  await fireEvent.press(screen.getByRole('button', { name: 'Send' }));
  expect(await screen.findByText('Please write a little more so we can help.')).toBeOnTheScreen();

  await fireEvent.changeText(screen.getByLabelText('Your message'), 'Do you organise private group hikes?');
  await fireEvent.press(screen.getByRole('button', { name: 'Send' }));

  expect(await screen.findByText('Thank you! Your message is on its way. We usually answer within a day.')).toBeOnTheScreen();
  expect(calls.filter((call) => call.method === 'POST').at(-1)?.body).toEqual({
    name: 'Maya Saad',
    email: 'maya@example.com',
    phone: null,
    message: 'Do you organise private group hikes?',
  });
  expect(screen.getByLabelText('Your message')).toHaveDisplayValue('');
});

it("fills in a logged-in member's details", async () => {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /me': ok(sampleMember), 'GET /me/bookings': ok([]), 'GET /contact': ok(contact) });
  await renderRouter('./src/app', { initialUrl: '/contact' });

  expect(await screen.findByDisplayValue('Rima Haddad')).toBeOnTheScreen();
  expect(screen.getByDisplayValue('rima@example.com')).toBeOnTheScreen();
  expect(screen.getByDisplayValue('+96170123456')).toBeOnTheScreen();
});
