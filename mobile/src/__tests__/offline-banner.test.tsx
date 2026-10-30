import NetInfo from '@react-native-community/netinfo';
import { renderRouter, screen } from 'expo-router/testing-library';

import { appConfigRoute, fakeApi, quietTabs, sampleEvent } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

it('shows the offline banner when the phone has no connection', async () => {
  jest.mocked(NetInfo.useNetInfo).mockReturnValue({ isConnected: false } as ReturnType<typeof NetInfo.useNetInfo>);
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /events': { status: 200, body: { data: [sampleEvent()], links: {}, meta: { current_page: 1, last_page: 1 } } } });
  await renderRouter('./src/app', { initialUrl: '/hikes' });

  expect(await screen.findByText('No signal: showing what was saved on this phone')).toBeOnTheScreen();
});
