import AsyncStorage from '@react-native-async-storage/async-storage';
import { fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';
import { Linking } from 'react-native';

import type { AppConfig } from '@/api/types';
import { fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));
// The installed app is version 1.0.0 in these tests.
jest.mock('@/config', () => ({ ...jest.requireActual('@/config'), APP_VERSION: '1.0.0' }));

const STORE = 'https://play.google.com/store/apps/details?id=club.hiking.app';
const config = (overrides: Partial<AppConfig>): AppConfig => ({
  min_version: '0.0.0',
  latest_version: '1.0.0',
  latest_notes: null,
  store_url: { android: STORE, ios: STORE },
  ...overrides,
});
const start = async (appConfig: AppConfig) => {
  fakeApi({ ...quietTabs, 'GET /app-config': { status: 200, body: { data: appConfig } } });
  await renderRouter('./src/app', { initialUrl: '/' });
};

it('blocks an app below the minimum version', async () => {
  const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
  await start(config({ min_version: '1.2.0', latest_version: '1.2.0' }));

  expect(await screen.findByText('Please update the app')).toBeOnTheScreen();
  expect(screen.queryByText('Next hike')).toBeNull();
  await fireEvent.press(screen.getByRole('button', { name: 'Update' }));
  expect(openURL).toHaveBeenCalledWith(STORE);
});

it("suggests a newer version with what's new, until Later", async () => {
  await start(config({ latest_version: '1.1.0', latest_notes: 'Photo gallery and offline hikes.' }));

  expect(await screen.findByText('Version 1.1.0 is available')).toBeOnTheScreen();
  expect(screen.getByText('Photo gallery and offline hikes.')).toBeOnTheScreen();
  // The app works underneath.
  expect(await screen.findByText('Next hike')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Later' }));

  expect(screen.queryByText('Version 1.1.0 is available')).toBeNull();
  await waitFor(async () => expect(await AsyncStorage.getItem('hiking.update-dismissed')).toBe('1.1.0'));
});

it('suggests again only for an even newer version', async () => {
  await AsyncStorage.setItem('hiking.update-dismissed', '1.1.0');
  await start(config({ latest_version: '1.1.0' }));
  expect(await screen.findByText('Next hike')).toBeOnTheScreen();
  expect(screen.queryByText('Version 1.1.0 is available')).toBeNull();
});

it('suggests nothing without a store page', async () => {
  await start(config({ latest_version: '1.1.0', store_url: { android: null, ios: null } }));

  expect(await screen.findByText('Next hike')).toBeOnTheScreen();
  expect(screen.queryByText('Version 1.1.0 is available')).toBeNull();
});
