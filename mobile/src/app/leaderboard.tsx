import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ActivityIndicator, FlatList, StyleSheet, Switch, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { formMessage } from '@/api/errors';
import type { LeaderboardList, LeaderboardRow, Member } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button, FormError } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/**
 * The public leaderboard (GET /leaderboard): the top 100, names as "Rima H.", hidden hikers left out, like the
 * website. Logged in, the member's row is marked and "Your place" holds the "Show me on the leaderboard" setting
 * (PUT /me/leaderboard).
 */
export default function LeaderboardScreen() {
  const { status, member } = useAuth();
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    // `is_me` and `my_position` depend on who is logged in.
    queryKey: ['leaderboard', status],
    queryFn: () => apiFetch<LeaderboardList>('/leaderboard'),
    enabled: status !== 'loading',
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

  return (
    <ThemedView style={styles.container}>
      <FlatList
        data={data.data}
        keyExtractor={(row) => String(row.position)}
        renderItem={({ item }) => <Row row={item} />}
        ListHeaderComponent={
          <View style={styles.header}>
            <ThemedText type="small" themeColor="textSecondary">{strings.leaderboard.intro}</ThemedText>
            {member?.hiker ? <YourPlace hiker={member.hiker} position={data.my_position} /> : null}
          </View>
        }
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.leaderboard.empty}</ThemedText>}
        contentContainerStyle={styles.list}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

/** The member's place (or why they aren't listed) and the "show me" setting. */
function YourPlace({ hiker, position }: { hiker: NonNullable<Member['hiker']>; position: number | null }) {
  const queryClient = useQueryClient();
  const toggle = useMutation({
    mutationFn: (show: boolean) => apiFetch<{ data: Member }>('/me/leaderboard', { method: 'PUT', body: { show_on_leaderboard: show } }),
    onSuccess: ({ data }) => {
      queryClient.setQueryData(['me'], data);
      void queryClient.invalidateQueries({ queryKey: ['leaderboard'] });
      void queryClient.invalidateQueries({ queryKey: ['home'] });
    },
  });
  const shown = toggle.isPending && toggle.variables !== undefined ? toggle.variables : hiker.show_on_leaderboard;

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <ThemedText type="small" themeColor="textSecondary">{strings.leaderboard.yourPlace}</ThemedText>
      <ThemedText type="subtitle">
        {position !== null ? strings.leaderboard.position(position) : !hiker.show_on_leaderboard ? strings.leaderboard.hidden : strings.leaderboard.noPoints}
      </ThemedText>
      <View style={styles.switchRow}>
        <View style={styles.flex}>
          <ThemedText type="smallBold">{strings.leaderboard.show}</ThemedText>
          <ThemedText type="small" themeColor="textSecondary">{strings.leaderboard.showHint}</ThemedText>
        </View>
        <Switch accessibilityLabel={strings.leaderboard.show} value={shown} disabled={toggle.isPending} onValueChange={(value) => toggle.mutate(value)} />
      </View>
      <FormError message={formMessage(toggle.error)} />
    </ThemedView>
  );
}

function Row({ row }: { row: LeaderboardRow }) {
  const theme = useTheme();

  return (
    <ThemedView
      type="backgroundElement"
      accessibilityLabel={row.is_me ? `${strings.leaderboard.me}: ${row.position}. ${row.name}` : undefined}
      style={[styles.row, row.is_me && { borderColor: theme.accent, borderWidth: 2 }]}>
      <ThemedText type="smallBold" style={styles.position}>{row.position}</ThemedText>
      <View style={styles.flex}>
        <ThemedText>
          {row.name}
          {row.is_me ? ` · ${strings.leaderboard.me}` : ''}
        </ThemedText>
        <ThemedText type="small" themeColor="textSecondary">
          {row.rank.label} · {strings.leaderboard.stats(row.hikes_completed, row.distance_km)}
        </ThemedText>
      </View>
      <ThemedText type="smallBold">{strings.home.points(row.points)}</ThemedText>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.two, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  header: { gap: Spacing.three, paddingBottom: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  switchRow: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three },
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three, borderRadius: Spacing.three, padding: Spacing.three },
  position: { minWidth: 28, textAlign: 'center' },
  flex: { flex: 1, gap: Spacing.half },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
