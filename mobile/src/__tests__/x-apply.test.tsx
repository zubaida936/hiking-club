import { fireEvent, renderRouter, screen } from 'expo-router/testing-library';

import type { XApplicationForm } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
beforeEach(() => jest.clearAllMocks());

const ok = (data: unknown, status = 200) => ({ status, body: { data } });
const form: XApplicationForm = {
  frequency: [{ key: 'weekly', label: 'Every week' }],
  pace: [{ key: 'steady', label: 'Steady (3–4 km/h)' }],
  experience: [{ key: 'night', label: 'Night hikes' }],
  gear: [{ key: 'boots', label: 'Broken-in hiking boots' }],
  guardian_under_age: 18,
  consent: 'My answers are true.',
  privacy_url: 'http://localhost:8080/privacy',
  member: null,
};

async function fillRequired() {
  await fireEvent.changeText(screen.getByLabelText('Full name'), 'Rima Haddad');
  await fireEvent.changeText(screen.getByLabelText('WhatsApp number'), '+961 70 123 456');
  await fireEvent.changeText(screen.getByLabelText('Age'), '29');
  await fireEvent.changeText(screen.getByLabelText('City'), 'Beirut');
  await fireEvent.press(screen.getByRole('radio', { name: 'Every week' }));
  await fireEvent.changeText(screen.getByLabelText('Route'), 'Qornet es Sawda');
  await fireEvent.changeText(screen.getByLabelText('Distance (km)'), '24');
  await fireEvent.changeText(screen.getByLabelText('Elevation gain (m)'), '1400');
  await fireEvent.changeText(screen.getByLabelText('Duration (hours)'), '9.5');
  await fireEvent.changeText(screen.getByLabelText('Longest distance in one day (km)'), '30');
  await fireEvent.changeText(screen.getByLabelText('Biggest ascent in one day (m)'), '1600');
  await fireEvent.press(screen.getByRole('radio', { name: 'Steady (3–4 km/h)' }));
  await fireEvent.press(screen.getByRole('radio', { name: 'Yes' }));
  await fireEvent.changeText(screen.getByLabelText("What's your main challenge on long hikes?"), 'Knees on descents.');
  await fireEvent.press(screen.getByRole('checkbox', { name: 'Night hikes' }));
  await fireEvent.press(screen.getByRole('checkbox', { name: 'My answers are true.' }));
}

it('sends the application with the API field names and shows the confirmation', async () => {
  const calls = fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /x-events/application-form': ok(form),
    'POST /x-events/applications': ok({ status: { key: 'pending', label: 'Under review' }, submitted_at: '2026-10-30T10:00:00+00:00', message: 'Thanks! Our guides will review your application and contact you on WhatsApp.' }, 201),
  });
  await renderRouter('./src/app', { initialUrl: '/x-events/apply' });

  // The guardian fields show until an adult age is entered.
  expect(await screen.findByText('Under 18? A parent or guardian must agree.')).toBeOnTheScreen();
  await fillRequired();
  expect(screen.queryByText('Under 18? A parent or guardian must agree.')).toBeNull();

  await fireEvent.press(screen.getByRole('button', { name: 'Send my application' }));

  expect(await screen.findByText('Application sent')).toBeOnTheScreen();
  const sent = calls.find((call) => call.method === 'POST')?.body;
  expect(sent).toMatchObject({
    name: 'Rima Haddad',
    age: '29',
    email: null,
    guardian_name: null,
    hike_frequency: 'weekly',
    hardest_date: null,
    pace: 'steady',
    steady_pace: true,
    experience: ['night'],
    gear: [],
    consent: true,
  });
});

it("shows the API's messages under the fields and its rule messages", async () => {
  let answer: { status: number; body: unknown } = { status: 422, body: { message: 'The age field is required.', errors: { age: ['The age field is required.'] } } };
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /x-events/application-form': ok(form), 'POST /x-events/applications': () => answer });
  await renderRouter('./src/app', { initialUrl: '/x-events/apply' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Send my application' }));
  expect(await screen.findByText('The age field is required.')).toBeOnTheScreen();
  expect(screen.getByText('Some answers need a fix: see the messages in red above.')).toBeOnTheScreen();

  // A club rule (apply once) has no field: its message shows above the button.
  answer = { status: 422, body: { message: 'You already have an application under review. We will contact you on WhatsApp.' } };
  await fireEvent.press(screen.getByRole('button', { name: 'Send my application' }));
  expect(await screen.findByText('You already have an application under review. We will contact you on WhatsApp.')).toBeOnTheScreen();
});
