import { act, fireEvent, renderRouter, screen } from 'expo-router/testing-library';
import { Linking } from 'react-native';

import type { Article, ArticleCard } from '@/api/types';
import { appConfigRoute, fakeApi, quietTabs } from '@/test-utils/fake-api';

jest.mock('@/auth/token', () => ({ getToken: jest.fn(async () => null), saveToken: jest.fn(), clearToken: jest.fn() }));

// A stand-in web view: shows the HTML as text, and lets the test "tap" a link through onShouldStartLoadWithRequest.
let navigate: ((event: { url: string }) => boolean) | undefined;
jest.mock('react-native-webview', () => {
  const { Text } = jest.requireActual('react-native');
  return {
    WebView: ({ source, onShouldStartLoadWithRequest }: { source: { html: string }; onShouldStartLoadWithRequest: typeof navigate }) => {
      navigate = onShouldStartLoadWithRequest;
      return <Text testID="article-html">{source.html}</Text>;
    },
  };
});

const ok = (data: unknown) => ({ status: 200, body: { data } });
const card = (overrides: Partial<ArticleCard> = {}): ArticleCard => ({
  slug: 'how-much-water',
  title: 'How much water to carry on a day hike',
  summary: 'Half a litre per hour is a good start.',
  author: 'Rami',
  cover_url: null,
  reading_minutes: 3,
  published_at: '2026-10-12T09:00:00+00:00',
  web_url: 'http://localhost:8080/guides/how-much-water',
  ...overrides,
});
const article: Article = {
  ...card(),
  body_html: '<h2>Before you go</h2><p>Drink <a href="https://example.com/hydration">before</a> you start.</p>',
  more: [card({ slug: 'layers', title: 'Dressing in layers', web_url: 'http://localhost:8080/guides/layers' })],
};

it('lists the published guides and opens one', async () => {
  fakeApi({
    ...appConfigRoute,
    ...quietTabs,
    'GET /guides': { status: 200, body: { data: [card()], links: {}, meta: { current_page: 1, last_page: 1, total: 1 } } },
    'GET /guides/how-much-water': ok(article),
  });
  await renderRouter('./src/app', { initialUrl: '/guides' });

  expect(await screen.findByText('Rami · 12 Oct 2026 · 3 min read')).toBeOnTheScreen();
  await fireEvent.press(screen.getByRole('link', { name: 'How much water to carry on a day hike' }));

  expect(screen).toHavePathname('/guides/how-much-water');
  const html = await screen.findByTestId('article-html');
  expect(html).toHaveTextContent('<h1>How much water to carry on a day hike</h1>', { exact: false });
  expect(html).toHaveTextContent('<h2>Before you go</h2>', { exact: false });
  expect(html).toHaveTextContent('<a href="http://localhost:8080/guides/layers">Dressing in layers</a>', { exact: false });
});

it('opens guide links in the app and other links in the browser', async () => {
  const openURL = jest.spyOn(Linking, 'openURL').mockResolvedValue(undefined);
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /guides/how-much-water': ok(article), 'GET /guides/layers': ok({ ...article, ...card({ slug: 'layers', title: 'Dressing in layers' }) }) });
  await renderRouter('./src/app', { initialUrl: '/guides/how-much-water' });
  await screen.findByTestId('article-html');

  // The page itself (and "#heading" links) load in the web view.
  expect(navigate?.({ url: 'http://localhost:8080/guides/how-much-water' })).toBe(true);
  expect(navigate?.({ url: 'about:blank' })).toBe(true);

  let allowed: boolean | undefined;
  await act(async () => {
    allowed = navigate?.({ url: 'https://example.com/hydration' });
  });
  expect(allowed).toBe(false);
  expect(openURL).toHaveBeenCalledWith('https://example.com/hydration');

  await act(async () => {
    navigate?.({ url: 'http://localhost:8080/guides/layers' });
  });
  expect(screen).toHavePathname('/guides/layers');
});

it("says when a guide isn't published", async () => {
  fakeApi({ ...appConfigRoute, ...quietTabs, 'GET /guides/draft': { status: 404, body: { message: 'Not found' } } });
  await renderRouter('./src/app', { initialUrl: '/guides/draft' });

  expect(await screen.findByText("This guide doesn't exist or isn't published any more.")).toBeOnTheScreen();
});
