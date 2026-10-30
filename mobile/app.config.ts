import type { ConfigContext, ExpoConfig } from 'expo/config';

/**
 * app.json, plus App Links (Android) and Universal Links (iOS) when the website's domain is known: build with
 * APP_LINKS_HOST=hikingclub.lb (EAS env). The site must serve the matching /.well-known files (Laravel:
 * config/mobile.php → app_links, whose `paths` list must match this one). Without it, the app only opens its own
 * `hikingclub://` links.
 */
const host = process.env.APP_LINKS_HOST;

// Website paths the app has a screen for (see src/links.ts). Not the CMS's signed links or payment pages.
const pathPrefixes = ['/events', '/bookings/', '/shop', '/orders/', '/x-events', '/guides', '/gallery', '/account'];
const paths = ['/', '/cart', '/checkout', '/difficulty-calculator', '/rank-system', '/leaderboard', '/equipment', '/contact', '/login', '/activate', '/forgot-password'];

export default ({ config }: ConfigContext): ExpoConfig => {
  if (!host) return config as ExpoConfig;

  return {
    ...(config as ExpoConfig),
    android: {
      ...config.android,
      intentFilters: [
        {
          action: 'VIEW',
          autoVerify: true,
          data: [...pathPrefixes.map((pathPrefix) => ({ scheme: 'https', host, pathPrefix })), ...paths.map((path) => ({ scheme: 'https', host, path }))],
          category: ['BROWSABLE', 'DEFAULT'],
        },
      ],
    },
    ios: { ...config.ios, associatedDomains: [`applinks:${host}`] },
  };
};
