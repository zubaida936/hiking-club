import { PersistQueryClientProvider } from '@tanstack/react-query-persist-client';
import { DarkTheme, DefaultTheme, Stack, ThemeProvider } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, useColorScheme } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';

import { createQueryClient, persistOptions } from '@/api/query-cache';
import { AuthProvider } from '@/auth/auth-context';
import { OfflineBanner } from '@/components/offline-banner';
import { VersionGate } from '@/components/version-gate';
import { CheckInProvider } from '@/organizer/check-in-store';
import { CartProvider } from '@/shop/cart';
import { strings } from '@/strings';

/**
 * The tabs, with screens that open over them (activation, forgot password).
 */
export default function RootLayout() {
  const colorScheme = useColorScheme();
  // One cache per app start (and per test), created once.
  const [queryClient] = useState(createQueryClient);

  return (
    // Gesture handler root: the photo viewer's pinch-zoom needs it.
    <GestureHandlerRootView style={styles.root}>
    <PersistQueryClientProvider client={queryClient} persistOptions={persistOptions}>
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
                <Stack.Screen name="about" options={{ title: strings.about.title }} />
                <Stack.Screen name="past-hikes" options={{ title: strings.pastHikes.title }} />
                <Stack.Screen name="gallery/index" options={{ title: strings.gallery.title }} />
                <Stack.Screen name="gallery/[slug]" options={{ title: '' }} />
                <Stack.Screen name="photo-viewer" options={{ headerShown: false, presentation: 'fullScreenModal', animation: 'fade' }} />
              </Stack>
              <OfflineBanner />
            </VersionGate>
          </ThemeProvider>
        </CartProvider>
        </CheckInProvider>
      </AuthProvider>
    </PersistQueryClientProvider>
    </GestureHandlerRootView>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1 },
});
