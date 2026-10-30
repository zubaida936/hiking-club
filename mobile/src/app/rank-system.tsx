import { useQuery } from '@tanstack/react-query';
import { Link, router } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { Member, RankSystem } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/** The website's icon per rank (decoration only; the ranks themselves come from the API). */
const RANK_ICONS: Record<string, string> = { pathfinder: '🧭', trekker: '🥾', mountaineer: '⛰️', highlander: '🏔️', elite: '👑' };

/**
 * How the rank system works (GET /rank-system): the ranks and their rewards, how points are earned, an example and
 * the terms, all from config/points.php and config/rewards.php like the website's page. The member's own rank is
 * from GET /me.
 */
export default function RankSystemScreen() {
  const { member } = useAuth();
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['rank-system'],
    queryFn: () => apiFetch<{ data: RankSystem }>('/rank-system').then((answer) => answer.data),
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

  const hiker = member?.hiker ?? null;
  const { points, example } = data;

  return (
    <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
      <ThemedText>{strings.rankSystem.intro}</ThemedText>
      <YourRank hiker={hiker} />

      <ThemedText type="subtitle">{strings.rankSystem.ranks}</ThemedText>
      {data.ranks.map((rank) => (
        <RankCard key={rank.key} rank={rank} current={hiker?.rank.key === rank.key} />
      ))}

      <ThemedText type="subtitle">{strings.rankSystem.howToEarn}</ThemedText>
      <ThemedView type="backgroundElement" style={styles.card}>
        {points.per_hike.map((line) => (
          <PointsRow key={line.level} label={strings.rankSystem.perHike(line.label)} points={line.points} />
        ))}
        <PointsRow label={strings.rankSystem.earlyBooking} hint={strings.rankSystem.earlyBookingHint(points.early_booking.days)} points={points.early_booking.points} />
        <PointsRow label={strings.rankSystem.referral} hint={strings.rankSystem.referralHint} points={points.referral} />
      </ThemedView>
      <ThemedText>{strings.rankSystem.afterHike(points.paid_only)}</ThemedText>
      <Link href="/difficulty-calculator">
        <ThemedText type="linkPrimary">{strings.difficulty.howRated}</ThemedText>
      </Link>
      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="smallBold">{strings.rankSystem.example}</ThemedText>
        <ThemedText type="small">
          {strings.rankSystem.exampleText(example.level_label, example.hike_points, example.early_booking_points, example.total, example.hikes_to_rank, example.rank_label)}
        </ThemedText>
      </ThemedView>

      <ThemedText type="subtitle">{strings.rankSystem.terms}</ThemedText>
      {data.terms.map((term) => (
        <ThemedText key={term} type="small">
          • {term}
        </ThemedText>
      ))}
      <Button title={strings.rankSystem.seeHikes} onPress={() => router.navigate('/hikes')} />
    </ScreenScroll>
  );
}

/** The member's rank and progress from GET /me, or a hint to log in. */
function YourRank({ hiker }: { hiker: NonNullable<Member['hiker']> | null }) {
  const theme = useTheme();

  if (!hiker) {
    return (
      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="small">{strings.rankSystem.loginHint}</ThemedText>
      </ThemedView>
    );
  }

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <ThemedText type="small" themeColor="textSecondary">{strings.rankSystem.yourRank}</ThemedText>
      <ThemedText type="subtitle">
        {RANK_ICONS[hiker.rank.key] ?? ''} {strings.rankSystem.yourRankLine(hiker.rank.label, hiker.points)}
      </ThemedText>
      <View
        accessibilityRole="progressbar"
        accessibilityValue={{ min: 0, max: 100, now: hiker.rank_progress_percent }}
        style={[styles.track, { backgroundColor: theme.backgroundSelected }]}>
        <View style={[styles.bar, { width: `${hiker.rank_progress_percent}%`, backgroundColor: theme.accent }]} />
      </View>
      <ThemedText type="small" themeColor="textSecondary">
        {hiker.next_rank?.label && hiker.next_rank.points_needed !== null
          ? strings.account.nextRank(hiker.next_rank.points_needed, hiker.next_rank.label)
          : strings.account.topRank}
      </ThemedText>
    </ThemedView>
  );
}

function RankCard({ rank, current }: { rank: RankSystem['ranks'][number]; current: boolean }) {
  const theme = useTheme();

  return (
    <ThemedView type="backgroundElement" style={[styles.card, current && { borderColor: theme.accent, borderWidth: 2 }]}>
      <ThemedText type="subtitle">
        {RANK_ICONS[rank.key] ?? ''} {rank.label}
      </ThemedText>
      <ThemedText type="smallBold" themeColor="accent">
        {rank.min_points === 0 ? strings.rankSystem.fromFirstHike : strings.rankSystem.minPoints(rank.min_points)}
      </ThemedText>
      {current ? <ThemedText type="smallBold">{strings.rankSystem.youAreHere}</ThemedText> : null}
      {rank.rewards.length === 0 ? (
        <ThemedText type="small">{strings.rankSystem.starter}</ThemedText>
      ) : (
        rank.rewards.map((reward) => (
          <View key={reward.key} style={styles.reward}>
            <ThemedText type="smallBold">🎁 {reward.label}</ThemedText>
            <ThemedText type="small" themeColor="textSecondary">{reward.how_it_works}</ThemedText>
          </View>
        ))
      )}
    </ThemedView>
  );
}

function PointsRow({ label, hint, points }: { label: string; hint?: string; points: number }) {
  return (
    <View style={styles.row}>
      <View style={styles.flex}>
        <ThemedText type="small">{label}</ThemedText>
        {hint ? <ThemedText type="small" themeColor="textSecondary">{hint}</ThemedText> : null}
      </View>
      <ThemedText type="smallBold" themeColor="accent">{strings.rankSystem.plusPoints(points)}</ThemedText>
    </View>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  track: { height: 8, borderRadius: 4, overflow: 'hidden' },
  bar: { height: 8 },
  reward: { gap: Spacing.half },
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three },
  flex: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
