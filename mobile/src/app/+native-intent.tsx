import { appRouteFor } from '@/links';

/**
 * Every link that opens the app goes through here first. Website links (App Links / Universal Links, e.g.
 * https://site/events/horch-ehden or https://site/account/profile) become the matching app screen with
 * `appRouteFor()`; app links (`hikingclub://my-points`, Expo Go's `exp://…/--/…`) are kept as they are unless their
 * path is a website path the app knows. Must never throw: anything odd opens Home.
 */
export function redirectSystemPath({ path }: { path: string; initial: boolean }): string {
  try {
    const website = /^https?:\/\//i.test(path);
    // Expo Go dev links put the app path after "/--/"; the app's own scheme puts it after "hikingclub://".
    const appPath = website ? new URL(path).pathname : path.includes('/--/') ? path.slice(path.indexOf('/--/') + 3) : path.replace(/^[a-z][a-z0-9+.-]*:\/\//i, '/');
    const route = appRouteFor(appPath);

    if (route) return typeof route === 'string' ? route : path;
    // A website link the app claims but has no screen for (e.g. the past hikes archive): open Home.
    return website ? '/' : path;
  } catch {
    return '/';
  }
}
