import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { DarkTheme, DefaultTheme, ThemeProvider } from 'expo-router';
import { useColorScheme } from 'react-native';

import { ApiError } from '@/api/client';
import AppTabs from '@/components/app-tabs';
import { VersionGate } from '@/components/version-gate';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Don't retry answers the server gave on purpose (not found, validation, logged out); retry network trouble.
      retry: (failures, error) => !(error instanceof ApiError && error.status >= 400 && error.status < 500) && failures < 2,
    },
  },
});

export default function RootLayout() {
  const colorScheme = useColorScheme();

  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
        <VersionGate>
          <AppTabs />
        </VersionGate>
      </ThemeProvider>
    </QueryClientProvider>
  );
}
