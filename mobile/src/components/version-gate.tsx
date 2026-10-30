import AsyncStorage from '@react-native-async-storage/async-storage';
import { useQuery } from '@tanstack/react-query';
import { useEffect, useState, type ReactNode } from 'react';
import { Linking, Platform, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { AppConfig } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { APP_VERSION } from '@/config';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';
import { canUpdate, mustUpdate } from '@/version';

/** The newer version the member said "Later" to: not suggested again until an even newer one is out. */
const DISMISSED_KEY = 'hiking.update-dismissed';

/**
 * Asks the API which app versions it supports (GET /app-config). Below `min_version`: only an "update" screen.
 * Below `latest_version`: a banner suggesting the update (with what's new), until "Later" for that version.
 * If the check itself fails (offline), the app opens normally: screens show their own errors.
 */
export function VersionGate({ children }: { children: ReactNode }) {
  const { data } = useQuery({
    queryKey: ['app-config'],
    queryFn: () => apiFetch<{ data: AppConfig }>('/app-config').then((answer) => answer.data),
    staleTime: 60 * 60 * 1000,
  });
  const [dismissed, setDismissed] = useState<string | null | undefined>(undefined);

  useEffect(() => {
    let active = true;
    AsyncStorage.getItem(DISMISSED_KEY).then(
      (version) => active && setDismissed(version),
      () => active && setDismissed(null),
    );
    return () => {
      active = false;
    };
  }, []);

  const storeUrl = data ? (Platform.OS === 'ios' ? data.store_url.ios : data.store_url.android) : null;
  const openStore = () => storeUrl && void Linking.openURL(storeUrl);

  if (data && APP_VERSION !== null && mustUpdate(APP_VERSION, data.min_version)) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.content}>
          <ThemedText type="title" style={styles.center}>{strings.update.title}</ThemedText>
          <ThemedText style={styles.center}>{strings.update.text}</ThemedText>
          {storeUrl ? <Button title={strings.update.button} onPress={openStore} /> : <ThemedText type="small" style={styles.center}>{strings.update.noStore}</ThemedText>}
        </SafeAreaView>
      </ThemedView>
    );
  }

  // Only suggested when there's a store to go to, and not again for a version the member put off.
  const suggest =
    data && APP_VERSION !== null && storeUrl && dismissed !== undefined && dismissed !== data.latest_version && canUpdate(APP_VERSION, data.latest_version);

  const later = () => {
    if (!data) return;
    setDismissed(data.latest_version);
    void AsyncStorage.setItem(DISMISSED_KEY, data.latest_version).catch(() => undefined);
  };

  return (
    <>
      {children}
      {suggest ? (
        <SafeAreaView edges={['top']} style={styles.bannerWrapper} pointerEvents="box-none">
          <ThemedView type="backgroundElement" accessibilityRole="alert" style={styles.banner}>
            <ThemedText type="smallBold">{strings.update.available(data.latest_version)}</ThemedText>
            {data.latest_notes ? <ThemedText type="small">{data.latest_notes}</ThemedText> : null}
            <View style={styles.actions}>
              <View style={styles.flex}>
                <Button title={strings.update.later} onPress={later} variant="secondary" />
              </View>
              <View style={styles.flex}>
                <Button title={strings.update.button} onPress={openStore} />
              </View>
            </View>
          </ThemedView>
        </SafeAreaView>
      ) : null}
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: Spacing.three, padding: Spacing.four },
  center: { textAlign: 'center' },
  bannerWrapper: { position: 'absolute', top: 0, left: 0, right: 0, padding: Spacing.three },
  banner: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two, elevation: 4, shadowOpacity: 0.15, shadowRadius: 8 },
  actions: { flexDirection: 'row', gap: Spacing.two },
  flex: { flex: 1 },
});
