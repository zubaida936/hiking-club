import { CameraView, type BarcodeScanningResult } from 'expo-camera';
import { router, useLocalSearchParams } from 'expo-router';
import { useRef, useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { CameraGate } from '@/components/camera-gate';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { referenceFromQr, useCheckIn } from '@/organizer/check-in-store';
import { strings } from '@/strings';

type Result = { ok: boolean; text: string };

/** How long (ms) before the same QR code counts again (the camera reads it many times a second). */
const SAME_CODE_PAUSE_MS = 3000;

/**
 * Scan the QR code on a hiker's booking screen and check them in. The code is looked up in the hike's list saved on
 * the phone, so scanning works without signal; the mark is sent like one made by hand.
 */
export default function ScanScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { lists, mark } = useCheckIn();
  const [result, setResult] = useState<Result | null>(null);
  const last = useRef<{ data: string; at: number } | null>(null);
  const items = lists[slug]?.items ?? [];
  const confirmed = items.filter((item) => item.can_check_in);
  const checkedIn = confirmed.filter((item) => item.attendance === 'attended').length;

  const onScanned = ({ data }: BarcodeScanningResult) => {
    const now = Date.now();
    if (last.current && last.current.data === data && now - last.current.at < SAME_CODE_PAUSE_MS) return;
    last.current = { data, at: now };

    const reference = referenceFromQr(data);
    if (!reference) return setResult({ ok: false, text: data.startsWith('PERK-') ? strings.organizer.perkOnBookingScan : strings.organizer.notBookingQr });
    const item = lists[slug]?.items.find((entry) => entry.reference === reference);
    if (!item) return setResult({ ok: false, text: strings.organizer.notOnList });
    if (!item.can_check_in) return setResult({ ok: false, text: strings.organizer.notConfirmed(item.name) });

    if (item.attendance !== 'attended') mark(slug, reference, 'attended');
    setResult({ ok: true, text: strings.organizer.scanned(item.name) });
  };

  return (
    <CameraGate reason={strings.organizer.cameraNeeded}>
      <ThemedView style={styles.container}>
        <CameraView style={styles.camera} facing="back" barcodeScannerSettings={{ barcodeTypes: ['qr'] }} onBarcodeScanned={onScanned} />
        <View style={styles.panel}>
          <ThemedText accessibilityRole="alert" type="smallBold" themeColor={result && !result.ok ? 'danger' : undefined} style={styles.text}>
            {result?.text ?? strings.organizer.scanHint}
          </ThemedText>
          <ThemedText type="small" style={styles.text}>
            {strings.organizer.counts(checkedIn, confirmed.length)}
          </ThemedText>
          <Button title={strings.organizer.done} onPress={() => router.back()} variant="secondary" />
        </View>
      </ThemedView>
    </CameraGate>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  camera: { flex: 1 },
  panel: { padding: Spacing.three, gap: Spacing.three },
  text: { textAlign: 'center' },
});
