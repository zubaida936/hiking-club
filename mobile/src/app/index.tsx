import { useQuery } from '@tanstack/react-query';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { EventList } from '@/api/types';
import { EventCard } from '@/components/event-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { strings } from '@/strings';

export default function HikesScreen() {
  const { data, error, isPending, isRefetching, refetch } = useQuery({
    queryKey: ['events', 'upcoming'],
    queryFn: () => apiFetch<EventList>('/events'),
  });

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <ThemedText type="title" style={styles.title}>
          {strings.hikes.title}
        </ThemedText>

        {isPending ? (
          <ActivityIndicator style={styles.state} />
        ) : error ? (
          <ThemedView style={styles.state}>
            <ThemedText style={styles.center}>{error.message}</ThemedText>
            <Pressable accessibilityRole="button" onPress={() => refetch()}>
              <ThemedText type="linkPrimary">{strings.errors.retry}</ThemedText>
            </Pressable>
          </ThemedView>
        ) : (
          <FlatList
            data={data.data}
            keyExtractor={(event) => String(event.id)}
            renderItem={({ item }) => <EventCard event={item} />}
            contentContainerStyle={styles.list}
            ListEmptyComponent={<ThemedText style={styles.center}>{strings.hikes.empty}</ThemedText>}
            refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />}
          />
        )}
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, flexDirection: 'row', justifyContent: 'center' },
  safeArea: { flex: 1, maxWidth: MaxContentWidth, paddingHorizontal: Spacing.three },
  title: { marginVertical: Spacing.three },
  list: { gap: Spacing.three, paddingBottom: BottomTabInset + Spacing.three },
  state: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three },
  center: { textAlign: 'center' },
});
