import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { DarkTheme, DefaultTheme, Stack, ThemeProvider } from 'expo-router';
import { useState } from 'react';
import { useColorScheme } from 'react-native';

import { ApiError } from '@/api/client';
import { AuthProvider } from '@/auth/auth-context';
import { VersionGate } from '@/components/version-gate';
import { strings } from '@/strings';

function createQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        // Don't retry answers the server gave on purpose (not found, validation, logged out); retry network trouble.
        retry: (failures, error) => !(error instanceof ApiError && error.status >= 400 && error.status < 500) && failures < 2,
      },
    },
  });
}

/**
 * The tabs, with screens that open over them (activation, forgot password).
 */
export default function RootLayout() {
  const colorScheme = useColorScheme();
  // One cache per app start (and per test), created once.
  const [queryClient] = useState(createQueryClient);

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <ThemeProvider value={colorScheme === 'dark' ? DarkTheme : DefaultTheme}>
          <VersionGate>
            <Stack>
              <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
              <Stack.Screen name="activate" options={{ title: strings.activate.title }} />
              <Stack.Screen name="forgot-password" options={{ title: strings.forgot.title }} />
              <Stack.Screen name="events/[slug]" options={{ title: '' }} />
              <Stack.Screen name="book/[slug]" options={{ title: strings.book.title }} />
              <Stack.Screen name="bookings/[token]" options={{ title: strings.booking.reference }} />
              <Stack.Screen name="my-hikes" options={{ title: strings.myBookings.completed }} />
              <Stack.Screen name="my-points" options={{ title: strings.points.title }} />
              <Stack.Screen name="my-rewards" options={{ title: strings.rewards.title }} />
              <Stack.Screen name="delete-account" options={{ title: strings.deleteAccount.title }} />
            </Stack>
          </VersionGate>
        </ThemeProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}
