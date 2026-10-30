import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { DarkTheme, DefaultTheme, Stack, ThemeProvider } from 'expo-router';
import { useState } from 'react';
import { useColorScheme } from 'react-native';

import { ApiError } from '@/api/client';
import { AuthProvider } from '@/auth/auth-context';
import { VersionGate } from '@/components/version-gate';
import { CheckInProvider } from '@/organizer/check-in-store';
import { CartProvider } from '@/shop/cart';
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
        <CheckInProvider>
        <CartProvider>
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
                <Stack.Screen name="edit-profile" options={{ title: strings.profile.title }} />
                <Stack.Screen name="shop/[slug]" options={{ title: '' }} />
                <Stack.Screen name="cart" options={{ title: strings.cart.title }} />
                <Stack.Screen name="checkout" options={{ title: strings.checkout.title }} />
                <Stack.Screen name="orders/[token]" options={{ title: strings.order.title }} />
                <Stack.Screen name="my-orders" options={{ title: strings.order.myOrders }} />
                <Stack.Screen name="x-events/index" options={{ title: strings.xEvents.title }} />
                <Stack.Screen name="x-events/apply" options={{ title: strings.xApply.title }} />
                <Stack.Screen name="check-in/[slug]" options={{ title: '' }} />
                <Stack.Screen name="check-in/scan" options={{ title: strings.organizer.scanTitle }} />
                <Stack.Screen name="difficulty-calculator" options={{ title: strings.difficulty.title }} />
                <Stack.Screen name="perks/scan" options={{ title: strings.perks.title }} />
                <Stack.Screen name="rank-system" options={{ title: strings.rankSystem.title }} />
                <Stack.Screen name="leaderboard" options={{ title: strings.leaderboard.title }} />
                <Stack.Screen name="guides/index" options={{ title: strings.guides.title }} />
                <Stack.Screen name="guides/[slug]" options={{ title: '' }} />
                <Stack.Screen name="equipment" options={{ title: strings.equipment.title }} />
                <Stack.Screen name="contact" options={{ title: strings.contact.title }} />
              </Stack>
            </VersionGate>
          </ThemeProvider>
        </CartProvider>
        </CheckInProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}
