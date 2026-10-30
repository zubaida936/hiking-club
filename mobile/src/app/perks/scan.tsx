import { useMutation } from '@tanstack/react-query';
import { CameraView, type BarcodeScanningResult } from 'expo-camera';
import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { PerkState } from '@/api/types';
import { CameraGate } from '@/components/camera-gate';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

const PERK_PREFIX = 'PERK-';

/**
 * Hand over a perk (gift card, winter bonus): scan the QR code on the member's My rewards screen, see who it belongs
 * to and whether it can be handed over (GET /organizer/perks/{code}), then mark it handed over (POST .../hand-over).
 * The server decides everything and records it in the Rewards used log, so this needs signal.
 */
export default function PerkScanScreen() {
  const [code, setCode] = useState<string | null>(null);
  const [wrongCode, setWrongCode] = useState(false);

  const check = useMutation({
    mutationFn: (scanned: string) =>
      apiFetch<{ data: PerkState }>(`/organizer/perks/${encodeURIComponent(scanned)}`).then((answer) => answer.data),
  });

  const onScanned = ({ data }: BarcodeScanningResult) => {
    // The camera reads the same code many times a second: only the first read counts until "Scan another code".
    if (code !== null || check.isPending) return;
    if (!data.startsWith(PERK_PREFIX)) return setWrongCode(true);

    setWrongCode(false);
    setCode(data);
    check.mutate(data);
  };

  const scanAgain = () => {
    setCode(null);
    check.reset();
  };

  if (code !== null) {
    return (
      <ScreenScroll>
        {check.isPending ? (
          <View style={styles.checking}>
            <ActivityIndicator />
            <ThemedText style={styles.text}>{strings.perks.checking}</ThemedText>
          </View>
        ) : check.data ? (
          <HandOver code={code} perk={check.data} />
        ) : (
          <FormError message={check.error?.message ?? null} />
        )}
        {!check.isPending ? <Button title={strings.perks.scanAnother} onPress={scanAgain} variant="secondary" /> : null}
        <Button title={strings.organizer.done} onPress={() => router.back()} variant="secondary" />
      </ScreenScroll>
    );
  }

  return (
    <CameraGate reason={strings.organizer.cameraNeeded}>
      <ThemedView style={styles.container}>
        <CameraView style={styles.camera} facing="back" barcodeScannerSettings={{ barcodeTypes: ['qr'] }} onBarcodeScanned={onScanned} />
        <View style={styles.panel}>
          <ThemedText accessibilityRole="alert" type="smallBold" themeColor={wrongCode ? 'danger' : undefined} style={styles.text}>
            {wrongCode ? strings.perks.notPerkQr : strings.perks.hint}
          </ThemedText>
          <Button title={strings.organizer.done} onPress={() => router.back()} variant="secondary" />
        </View>
      </ThemedView>
    </CameraGate>
  );
}

/** Who the perk belongs to and whether it can be handed over; the button records it. */
function HandOver({ code, perk }: { code: string; perk: PerkState }) {
  const [note, setNote] = useState('');
  const handOver = useMutation({
    mutationFn: () =>
      apiFetch<{ data: PerkState }>(`/organizer/perks/${encodeURIComponent(code)}/hand-over`, {
        method: 'POST',
        body: { note: note.trim() === '' ? null : note.trim() },
      }).then((answer) => answer.data),
  });
  const current = handOver.data ?? perk;

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <ThemedText type="subtitle">{current.reward.label}</ThemedText>
      <ThemedText type="small" themeColor="textSecondary">{strings.rewards.rank(current.reward.rank)}</ThemedText>
      <ThemedText type="smallBold">{current.member.name}</ThemedText>
      <ThemedText type="small">{current.member.phone}</ThemedText>

      {handOver.isSuccess ? (
        <ThemedText accessibilityRole="alert" type="smallBold" themeColor="accent">
          {strings.perks.handedOver(current.reward.label, current.member.name)}
        </ThemedText>
      ) : current.available ? (
        <>
          <ThemedText type="smallBold" themeColor="accent">{strings.perks.canHandOver}</ThemedText>
          <FormField
            label={strings.perks.note}
            value={note}
            onChangeText={setNote}
            placeholder={strings.perks.notePlaceholder}
            maxLength={200}
            error={fieldErrors(handOver.error).note}
          />
          <FormError message={formMessage(handOver.error)} />
          <Button title={strings.perks.handOver} onPress={() => handOver.mutate()} loading={handOver.isPending} />
        </>
      ) : (
        <>
          <ThemedText accessibilityRole="alert" type="smallBold" themeColor="danger">{current.unavailable_reason}</ThemedText>
          {current.handed_over_at ? (
            <ThemedText type="small" themeColor="textSecondary">{strings.perks.lastHandedOver(formatMoment(current.handed_over_at))}</ThemedText>
          ) : null}
        </>
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  camera: { flex: 1 },
  panel: { padding: Spacing.three, gap: Spacing.three },
  checking: { alignItems: 'center', gap: Spacing.two, paddingVertical: Spacing.four },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  text: { textAlign: 'center' },
});
