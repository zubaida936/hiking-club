import { fireEvent, renderRouter, screen, waitFor } from 'expo-router/testing-library';

import type { Album, AlbumCard } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

// Pinch-zoom is native gesture code: in tests a photo is its label. Sharing and the download are stand-ins too.
jest.mock('@/components/zoomable-image', () => {
  const { Text } = jest.requireActual('react-native');
  return { ZoomableImage: ({ accessibilityLabel, uri }: { accessibilityLabel: string; uri: string }) => <Text>{`${accessibilityLabel}: ${uri}`}</Text> };
});
const mockShareAsync = jest.fn(async () => undefined);
jest.mock('expo-sharing', () => ({ shareAsync: (...args: unknown[]) => mockShareAsync(...(args as [])) }));
const mockDownloadFileAsync = jest.fn(async () => undefined);
jest.mock('expo-file-system', () => {
  class File {
    uri: string;
    exists = false;
    constructor(_dir: unknown, name: string) {
      this.uri = `file:///cache/${name}`;
    }
    static downloadFileAsync = (...args: unknown[]) => mockDownloadFileAsync(...(args as []));
  }
  return { File, Paths: { cache: 'cache' } };
});

beforeEach(() => jest.clearAllMocks());

const ok = (data: unknown) => ({ status: 200, body: { data } });
const card: AlbumCard = { slug: 'horsh-ehden', title: 'Horsh Ehden', taken_on: '2026-10-18', cover_url: 'http://localhost:8080/storage/albums/a.jpg', photos_count: 2 };
const album: Album = {
  slug: 'horsh-ehden',
  title: 'Horsh Ehden',
  description: 'A sunny day in the cedars.',
  taken_on: '2026-10-18',
  event_slug: 'horch-ehden',
  photos: [
    { id: 1, url: 'http://localhost:8080/storage/albums/a.jpg' },
    { id: 2, url: 'http://localhost:8080/storage/albums/b.jpg' },
  ],
  web_url: 'http://localhost:8080/gallery/horsh-ehden',
};
const routes = {
  ...appConfigRoute,
  ...quietTabs,
  'GET /gallery': { status: 200, body: { data: [card], links: {}, meta: { current_page: 1, last_page: 1, total: 1 } } },
  'GET /gallery/horsh-ehden': ok(album),
};

it('lists the albums and opens one with its photos and hike', async () => {
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/gallery' });

  expect(await screen.findByText('Sun, 18 Oct · 2 photos')).toBeOnTheScreen();
  await fireEvent.press(screen.getByRole('link', { name: 'Horsh Ehden, 2 photos' }));

  expect(screen).toHavePathname('/gallery/horsh-ehden');
  expect(await screen.findByText('A sunny day in the cedars.')).toBeOnTheScreen();
  expect(screen.getByText('See the hike')).toBeOnTheScreen();
  expect(screen.getAllByRole('imagebutton')).toHaveLength(2);
});

it('opens a photo full screen and shares it', async () => {
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/gallery/horsh-ehden' });

  await fireEvent.press(await screen.findByRole('imagebutton', { name: 'Photo 2 of 2' }));

  expect(screen).toHavePathname('/photo-viewer');
  expect(await screen.findByText('Photo 2 of 2: http://localhost:8080/storage/albums/b.jpg')).toBeOnTheScreen();

  await fireEvent.press(screen.getByRole('button', { name: 'Share / save' }));

  await waitFor(() => expect(mockShareAsync).toHaveBeenCalledWith('file:///cache/club-photo-2.jpg', { mimeType: 'image/jpeg', dialogTitle: 'Horsh Ehden' }));
  expect(mockDownloadFileAsync).toHaveBeenCalledWith('http://localhost:8080/storage/albums/b.jpg', expect.objectContaining({ uri: 'file:///cache/club-photo-2.jpg' }));
});

it('says when sharing fails', async () => {
  mockDownloadFileAsync.mockRejectedValueOnce(new Error('offline'));
  fakeApi(routes);
  await renderRouter('./src/app', { initialUrl: '/photo-viewer?slug=horsh-ehden&index=0' });

  await fireEvent.press(await screen.findByRole('button', { name: 'Share / save' }));

  expect(await screen.findByText("Couldn't share this photo. Check your connection and try again.")).toBeOnTheScreen();
  expect(mockShareAsync).not.toHaveBeenCalled();
});

it('says when an album is hidden', async () => {
  fakeApi({ ...routes, 'GET /gallery/hidden': { status: 404, body: { message: 'Not found' } } });
  await renderRouter('./src/app', { initialUrl: '/gallery/hidden' });

  expect(await screen.findByText("This album doesn't exist or isn't public any more.")).toBeOnTheScreen();
});
