import AsyncStorage from '@react-native-async-storage/async-storage';
import { fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';

import type { Equipment } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
beforeEach(async () => {
  await AsyncStorage.clear();
});

const ok = (data: unknown) => ({ status: 200, body: { data } });
const equipment: Equipment = {
  levels: [
    { level: 1, label: 'Level 1 · Foundation' },
    { level: 2, label: 'Level 2 · Endurance' },
    { level: 3, label: 'Level 3 · Advanced' },
  ],
  adventures: [
    {
      key: 'hiking',
      label: 'Hiking',
      icon: '🥾',
      intro: 'Day hikes on trails and mountain paths.',
      items: [
        { name: 'Water', tip: 'Half a litre per hour.', from_level: null, shop_category: null },
        { name: 'Hiking shoes', tip: 'Broken in, with a good grip.', from_level: null, shop_category: 'footwear' },
        { name: 'Headlamp', tip: 'Long days can end after sunset.', from_level: 3, shop_category: null },
      ],
    },
    { key: 'rafting', label: 'Rafting', icon: '🚣', intro: 'Rafting on the Assi river.', items: [{ name: 'Swimsuit', tip: 'You will get wet.', from_level: null, shop_category: null }] },
  ],
};
const routes = { ...appConfigRoute, ...quietTabs, 'GET /equipment': ok(equipment) };

it('shows the checklist for the chosen adventure and level', async () => {
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/equipment' });

  expect(await screen.findByText('Day hikes on trails and mountain paths.')).toBeOnTheScreen();
  expect(screen.getByText('0 / 2 packed')).toBeOnTheScreen();
  expect(screen.queryByText('Headlamp')).toBeNull();

  await fireEvent.press(screen.getByRole('button', { name: 'Level 3 · Advanced' }));
  expect(screen.getByText('Headlamp')).toBeOnTheScreen();
  expect(screen.getByText('From Level 3')).toBeOnTheScreen();
  expect(screen.getByText('0 / 3 packed')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Rafting' }));
  expect(screen.getByText('Swimsuit')).toBeOnTheScreen();
  expect(screen.queryByText('Water')).toBeNull();
});

it('remembers ticked items on the phone', async () => {
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/equipment' });

  await fireEvent.press(await screen.findByRole('checkbox', { name: 'Water' }));

  expect(screen.getByText('1 / 2 packed')).toBeOnTheScreen();
  await waitFor(async () => expect(JSON.parse((await AsyncStorage.getItem('hiking.equipment.packed')) ?? '{}')).toEqual({ hiking: ['Water'] }));

  await fireEvent.press(screen.getByRole('button', { name: 'Untick everything' }));
  expect(screen.getByText('0 / 2 packed')).toBeOnTheScreen();
});

it('shows the ticks saved last time', async () => {
  await AsyncStorage.setItem('hiking.equipment.packed', JSON.stringify({ hiking: ['Water', 'Hiking shoes'] }));
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/equipment' });

  expect(await screen.findByText('2 / 2 packed')).toBeOnTheScreen();
  expect(screen.getByRole('checkbox', { name: 'Water' })).toBeChecked();
});

it('opens the matching shop category', async () => {
  const calls = fakeApi({ ...routes, 'GET /shop/products': ok([]) });
  await renderRouter('./src/app', { initialUrl: '/equipment' });

  await fireEvent.press(await screen.findByRole('link', { name: 'See it in the shop: Hiking shoes' }));

  expect(screen).toHavePathname('/shop');
  await waitFor(() => expect(calls.some((call) => call.path === '/shop/products' && call.query.category === 'footwear')).toBe(true));
});
