import { useQuery } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { Linking, Platform, Pressable, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { AppConfig } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { APP_VERSION } from '@/config';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';
import { mustUpdate } from '@/version';

/**
 * Asks the API which app versions it still supports (GET /app-config). Too old: only an "update" screen.
 * If the check itself fails (offline), the app opens normally: screens show their own errors.
 */
export function VersionGate({ children }: { children: ReactNode }) {
  const { data } = useQuery({
    queryKey: ['app-config'],
    queryFn: () => apiFetch<{ data: AppConfig }>('/app-config').then((answer) => answer.data),
    staleTime: 60 * 60 * 1000,
  });

  if (data && mustUpdate(APP_VERSION, data.min_version)) {
    const storeUrl = Platform.OS === 'ios' ? data.store_url.ios : data.store_url.android;

    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.content}>
          <ThemedText type="title" style={styles.center}>{strings.update.title}</ThemedText>
          <ThemedText style={styles.center}>{strings.update.text}</ThemedText>
          {storeUrl ? (
            <Pressable accessibilityRole="button" onPress={() => Linking.openURL(storeUrl)} style={styles.button}>
              <ThemedText type="smallBold" style={styles.buttonText}>{strings.update.button}</ThemedText>
            </Pressable>
          ) : (
            <ThemedText type="small" style={styles.center}>{strings.update.noStore}</ThemedText>
          )}
        </SafeAreaView>
      </ThemedView>
    );
  }

  return children;
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: Spacing.three, padding: Spacing.four },
  center: { textAlign: 'center' },
  button: { backgroundColor: '#1f6f43', borderRadius: 999, paddingHorizontal: Spacing.five, paddingVertical: Spacing.three },
  buttonText: { color: '#ffffff' },
});
