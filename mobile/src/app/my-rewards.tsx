import { useQuery } from '@tanstack/react-query';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { RewardState } from '@/api/types';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/**
 * Every reward of the rank system for this member (GET /me/rewards): available, used, or locked and why.
 * Discounts are used on the booking form; perks are handed over by the organizers.
 */
export default function MyRewardsScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['my', 'rewards'],
    queryFn: () => apiFetch<{ data: RewardState[] }>('/me/rewards').then((answer) => answer.data),
  });

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{error.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
      </ThemedView>
    );
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
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  group: { gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  locked: { opacity: 0.7 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
