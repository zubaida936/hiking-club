import { useQuery } from '@tanstack/react-query';
import { StyleSheet, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';

import { apiFetch } from '@/api/client';
import type { RewardState } from '@/api/types';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { ErrorState, LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/**
 * Every reward of the rank system for this member (GET /me/rewards): available, used, or locked and why.
 * Discounts are used on the booking form; perks are handed over by the organizers, who scan the perk's QR code.
 */
export default function MyRewardsScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['my', 'rewards'],
    queryFn: () => apiFetch<{ data: RewardState[] }>('/me/rewards').then((answer) => answer.data),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  // Grouped by rank, in the order the API sends them (lowest rank first).
  const ranks = [...new Map(data.map((reward) => [reward.rank.key, reward.rank.label])).entries()];

  return (
    <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
      {data.length === 0 ? <ThemedText style={styles.text}>{strings.rewards.empty}</ThemedText> : null}
      {ranks.map(([key, label]) => (
        <View key={key} style={styles.group}>
          <ThemedText type="subtitle">{strings.rewards.rank(label)}</ThemedText>
          {data
            .filter((reward) => reward.rank.key === key)
            .map((reward) => (
              <RewardCard key={reward.key} reward={reward} />
            ))}
        </View>
      ))}
    </ScreenScroll>
  );
}

function RewardCard({ reward }: { reward: RewardState }) {
  const status = !reward.unlocked
    ? reward.locked_reason
    : reward.uses_left === 0
      ? strings.rewards.usedUp
      : strings.rewards.available(reward.uses_left);

  return (
    <ThemedView type="backgroundElement" style={[styles.card, !reward.unlocked && styles.locked]}>
      <ThemedText type="smallBold">{reward.label}</ThemedText>
      <ThemedText type="small" themeColor={reward.unlocked ? 'accent' : 'textSecondary'}>
        {status}
      </ThemedText>
      <ThemedText type="small" themeColor="textSecondary">{reward.how_it_works}</ThemedText>
      {reward.used.map((use) => (
        <ThemedText key={use.used_at} type="small">
          {use.event ? strings.rewards.usedOn(use.event.title, formatMoment(use.used_at)) : strings.rewards.usedAt(formatMoment(use.used_at))}
        </ThemedText>
      ))}
      {/* A perk that can be handed over now: an organizer scans this (More → Organizer → "Scan a reward code"). */}
      {reward.perk_code ? (
        <View style={styles.qrBlock}>
          <ThemedText type="smallBold">{strings.rewards.perkQrTitle}</ThemedText>
          <View style={styles.qr}>
            <QRCode value={reward.perk_code} size={160} backgroundColor="#ffffff" color="#000000" />
          </View>
          <ThemedText type="small" themeColor="textSecondary" style={styles.text}>{strings.rewards.perkQrHint}</ThemedText>
        </View>
      ) : null}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  group: { gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  locked: { opacity: 0.7 },
  qrBlock: { alignItems: 'center', gap: Spacing.two, marginTop: Spacing.two },
  // White around the code so it scans in dark mode too.
  qr: { padding: Spacing.three, backgroundColor: '#ffffff', borderRadius: Spacing.two },
  text: { textAlign: 'center' },
});
