import { useQuery } from '@tanstack/react-query';
import { Link, router } from 'expo-router';
import { useEffect } from 'react';
import { FlatList, Pressable, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { OrganizerHike } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatDay } from '@/format';
import { useCheckIn } from '@/organizer/check-in-store';
import { strings } from '@/strings';

/** Organizer screen (staff only, opened from More): the hikes around today (GET /organizer/events) and the marks still to send. */
export default function OrganizerScreen() {
  const { member, status } = useAuth();
  const { pending, send } = useCheckIn();
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['organizer', 'events'],
    queryFn: () => apiFetch<{ data: OrganizerHike[] }>('/organizer/events').then((answer) => answer.data),
    enabled: Boolean(member?.organizer),
  });

  // When the screen opens: try the waiting marks again (signal may be back). Nothing to do when none wait.
  useEffect(() => {
    void send();
  }, [send]);

  const header = (
    <View style={styles.header}>
      <ThemedText type="small" themeColor="textSecondary">{strings.organizer.intro}</ThemedText>
      {member?.organizer?.can_hand_over_perks ? <Button title={strings.perks.scan} onPress={() => router.push('/perks/scan')} variant="secondary" /> : null}
      {pending.length > 0 ? (
        <ThemedView type="backgroundElement" style={styles.waiting}>
          <ThemedText type="smallBold">{strings.organizer.waiting(pending.length)}</ThemedText>
          <Button title={strings.organizer.sendNow} onPress={() => void send()} variant="secondary" />
        </ThemedView>
      ) : null}
    </View>
  );

  // Opened by a link without a staff account: say so instead of waiting for a list that never loads.
  if (status === 'signedOut' || (member && !member.organizer)) {
    return <EmptyState message={strings.organizer.staffOnly} />;
  }

  if (isPending) {
    return <LoadingState />;
  }

  return (
    <ThemedView style={styles.container}>
      <FlatList
        data={data ?? []}
        keyExtractor={(item) => item.event.slug}
        renderItem={({ item }) => <HikeRow hike={item} />}
        ListHeaderComponent={header}
        ListEmptyComponent={
          error ? <ErrorState inline message={error.message} onRetry={() => void refetch()} /> : <EmptyState inline message={strings.organizer.empty} />
        }
        contentContainerStyle={styles.list}
        refreshing={isRefetching}
        onRefresh={() => {
          void send();
          void refetch();
        }}
      />
    </ThemedView>
  );
}

function HikeRow({ hike }: { hike: OrganizerHike }) {
  const { event } = hike;

  return (
    <Link href={`/check-in/${event.slug}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={`${event.title}, ${formatDay(event.starts_on)}`}>
        <ThemedView type="backgroundElement" style={styles.card}>
          <ThemedText type="small" themeColor="textSecondary">{formatDay(event.starts_on)}</ThemedText>
          <ThemedText type="smallBold">{event.title}</ThemedText>
          <ThemedText type="small">{strings.organizer.counts(hike.checked_in, hike.confirmed)}</ThemedText>
          {!hike.check_in_open ? <ThemedText type="small" themeColor="textSecondary">{strings.organizer.notYet}</ThemedText> : null}
        </ThemedView>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: { gap: Spacing.two, paddingBottom: Spacing.two },
  list: { padding: Spacing.three, gap: Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  waiting: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.half },
});
