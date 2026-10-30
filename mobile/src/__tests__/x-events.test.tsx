import { renderRouter, screen } from 'expo-router/testing-library';

import type { XEvents } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs, sampleEvent, sampleMember } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
beforeEach(() => jest.clearAllMocks());

const ok = (data: unknown) => ({ status: 200, body: { data } });
const xEvents = (overrides: Partial<XEvents> = {}): XEvents => ({
  page: {
    heading: 'Longer routes. Sustained effort. Prepared hikers.',
    intro: 'Our most demanding days on the trail.',
    requirements: [{ title: 'Endurance', text: 'You regularly complete long hikes.' }],
    steps: [{ title: 'Apply once', text: 'Tell us about your experience.' }],
    no_events: 'The next X event is being planned.',
  },
  events: [],
  access: null,
  ...overrides,
});

async function asMember(answer: XEvents) {
  const { getToken } = jest.requireMock('@/auth/token') as { getToken: jest.Mock };
  getToken.mockResolvedValue('1|secret');
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /me': ok(sampleMember), 'GET /x-events': ok(answer) });
  await renderRouter('./src/app', { initialUrl: '/x-events' });
}

it('shows the website texts and lets guests apply or log in', async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /x-events': ok(xEvents()) });
  await renderRouter('./src/app', { initialUrl: '/x-events' });

  expect(await screen.findByText('Longer routes. Sustained effort. Prepared hikers.')).toBeOnTheScreen();
  expect(screen.getByText('Endurance')).toBeOnTheScreen();
  expect(screen.getByText('1. Apply once')).toBeOnTheScreen();
  expect(screen.getByText('The next X event is being planned.')).toBeOnTheScreen();
  expect(screen.getByRole('button', { name: 'Apply for X Events' })).toBeOnTheScreen();
  expect(screen.getByRole('button', { name: 'Already approved? Log in to book.' })).toBeOnTheScreen();
});

it('shows a pending application without an Apply button', async () => {
  await asMember(xEvents({ access: { can_book: false, application: { status: { key: 'pending', label: 'Under review' }, submitted_at: '2026-10-20T09:00:00+00:00' }, can_apply: false } }));

  expect(await screen.findByText('Under review')).toBeOnTheScreen();
  expect(screen.getByText("Your application is under review. We'll contact you on WhatsApp.")).toBeOnTheScreen();
  expect(screen.queryByRole('button', { name: 'Apply for X Events' })).toBeNull();
});

it('lets a member apply again after a rejection', async () => {
  await asMember(xEvents({ access: { can_book: false, application: { status: { key: 'rejected', label: 'Not approved' }, submitted_at: '2026-09-01T09:00:00+00:00' }, can_apply: true } }));

  expect(await screen.findByText('Not approved')).toBeOnTheScreen();
  expect(screen.getByRole('button', { name: 'Apply again' })).toBeOnTheScreen();
});

it('lists the X hikes for approved members', async () => {
  await asMember(xEvents({ events: [sampleEvent({ title: 'Qornet es Sawda traverse', is_xtreme: true })], access: { can_book: true, application: null, can_apply: false } }));

  expect(await screen.findByText('You have access to X Events. Pick one below.')).toBeOnTheScreen();
  expect(screen.getByText('Qornet es Sawda traverse')).toBeOnTheScreen();
  expect(screen.queryByRole('button', { name: 'Apply for X Events' })).toBeNull();
});
