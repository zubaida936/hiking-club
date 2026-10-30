import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister';
import { onlineManager, QueryClient, type Query } from '@tanstack/react-query';
import type { PersistQueryClientOptions } from '@tanstack/react-query-persist-client';

import { ApiError } from '@/api/client';
import { APP_VERSION } from '@/config';

/** Saved answers are used for a week without signal, then dropped. */
const MAX_AGE = 7 * 24 * 60 * 60 * 1000;

/**
 * What stays on the phone between app starts, so it works on the trail without signal: the hikes (list and pages),
 * Home, my profile, my bookings and each booking's page (pickup point and time, WhatsApp link). Member data is
 * removed on logout like before (`['me']`, `['my']`), and the saved copy follows.
 */
export function shouldSaveQuery(query: Pick<Query, 'queryKey' | 'state'>): boolean {
  if (query.state.status !== 'success') return false;
  const [scope, kind] = query.queryKey as [unknown, unknown];

  return scope === 'events' || scope === 'home' || scope === 'me' || (scope === 'my' && (kind === 'bookings' || kind === 'booking'));
}

/** React Query knows the phone's connection: offline, nothing is retried, and everything reloads when it's back. */
onlineManager.setEventListener((setOnline) => NetInfo.addEventListener((state) => setOnline(state.isConnected !== false)));

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        // Saved answers must outlive the in-memory cache: keep them as long as they're saved.
        gcTime: MAX_AGE,
        // Try the network even when offline (the request fails fast with the "can't reach the server" message
        // instead of waiting forever); a saved answer stays shown meanwhile.
        networkMode: 'offlineFirst',
        // Don't retry answers the server gave on purpose (not found, validation, logged out), nor while offline;
        // retry other network trouble twice.
        retry: (failures, error) =>
          onlineManager.isOnline() && !(error instanceof ApiError && error.status >= 400 && error.status < 500) && failures < 2,
      },
    },
  });
}

export const persistOptions: Omit<PersistQueryClientOptions, 'queryClient'> = {
  persister: createAsyncStoragePersister({ storage: AsyncStorage, key: 'hiking.query-cache' }),
  maxAge: MAX_AGE,
  // A new app version starts with a fresh cache (the API answers may have new fields).
  buster: APP_VERSION ?? '',
  dehydrateOptions: { shouldDehydrateQuery: shouldSaveQuery },
};
