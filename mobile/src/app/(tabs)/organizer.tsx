import { useQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { useEffect } from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { OrganizerHike } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { formatDay } from '@/format';
import { useCheckIn } from '@/organizer/check-in-store';
import { strings } from '@/strings';

/** Organizer tab (staff only): the hikes around today (GET /organizer/events) and the marks still to send. */
export default function OrganizerScreen() {
  const { member } = useAuth();
  const { pending, send } = useCheckIn();
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['organizer', 'events'],
    queryFn: () => apiFetch<{ data: OrganizerHike[] }>('/organizer/events').then((answer) => answer.data),
    enabled: Boolean(member?.organizer),
  });

  // When the tab opens: try the waiting marks again (signal may be back). Nothing to do when none wait.
  useEffect(() => {
    void send();
  }, [send]);

  const header = (
    <View style={styles.header}>
      <ThemedText type="title">{strings.organizer.title}</ThemedText>
      <ThemedText type="small" themeColor="textSecondary">{strings.organizer.intro}</ThemedText>
      {pending.length > 0 ? (
        <ThemedView type="backgroundElement" style={styles.waiting}>
          <ThemedText type="smallBold">{strings.organizer.waiting(pending.length)}</ThemedText>
          <Button title={strings.organizer.sendNow} onPress={() => void send()} variant="secondary" />
        </ThemedView>
      ) : null}
    </View>
  );

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ThemedView style={styles.container}>
        <FlatList
          data={data ?? []}
          keyExtractor={(item) => item.event.slug}
          renderItem={({ item }) => <HikeRow hike={item} />}
          ListHeaderComponent={header}
          ListEmptyComponent={
            error ? (
              <View style={styles.center}>
                <ThemedText style={styles.text}>{error.message}</ThemedText>
                <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
              </View>
            ) : (
              <ThemedText style={styles.text}>{strings.organizer.empty}</ThemedText>
            )
          }
          contentContainerStyle={styles.list}
          refreshing={isRefetching}
          onRefresh={() => {
            void send();
            void refetch();
          }}
        />
      </ThemedView>
    </SafeAreaView>
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
  list: { padding: Spacing.three, gap: Spacing.three, paddingBottom: BottomTabInset + Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  waiting: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.half },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
