import { StyleSheet, View } from 'react-native';

import type { Member } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/**
 * The logged-in member: rank, points and progress (GET /me). Bookings, points history and rewards
 * come with the "Member dashboard / profile" task.
 */
export function MemberProfile({ member, onLogout, loggingOut }: { member: Member; onLogout: () => void; loggingOut: boolean }) {
  const theme = useTheme();
  const hiker = member.hiker;

  return (
    <>
      <ThemedText type="title">{member.name}</ThemedText>
      <ThemedText themeColor="textSecondary">{member.phone}</ThemedText>

      {hiker ? (
        <ThemedView type="backgroundElement" style={styles.card}>
          <ThemedText type="subtitle">{hiker.rank.label}</ThemedText>
          <ThemedText type="smallBold">{strings.account.points(hiker.points)}</ThemedText>
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
          <ThemedText type="small">{strings.account.hikesCompleted(hiker.hikes_completed)}</ThemedText>
          <ThemedText type="small">{strings.account.distance(hiker.distance_km)}</ThemedText>
          {hiker.leaderboard_position !== null ? (
            <ThemedText type="small">{strings.account.leaderboardPosition(hiker.leaderboard_position)}</ThemedText>
          ) : null}
        </ThemedView>
      ) : (
        <ThemedText themeColor="textSecondary">{strings.account.noHikerProfile}</ThemedText>
      )}

      <Button title={strings.account.logout} onPress={onLogout} loading={loggingOut} variant="secondary" />
    </>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: Spacing.four, padding: Spacing.three, gap: Spacing.two },
  track: { height: 8, borderRadius: 4, overflow: 'hidden' },
  bar: { height: 8 },
});
