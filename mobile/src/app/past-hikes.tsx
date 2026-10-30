import { useInfiniteQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { ActivityIndicator, SectionList, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { PastEvent, PastEventList } from '@/api/types';
import { EventCard } from '@/components/event-card';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatMonth } from '@/format';
import { strings } from '@/strings';

/** Past hikes from the API's order (newest first), one section per month like the website's archive. */
function byMonth(events: PastEvent[]): { title: string; data: PastEvent[] }[] {
  const sections: { title: string; data: PastEvent[] }[] = [];
  for (const event of events) {
    const title = formatMonth(event.starts_on);
    const last = sections.at(-1);
    if (last?.title === title) last.data.push(event);
    else sections.push({ title, data: [event] });
  }

  return sections;
}

/** The past hikes archive (GET /events/past), grouped by month, with each hike's photo album; loads more while scrolling. */
export default function PastHikesScreen() {
  const { data, error, isPending, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: ['events', 'past'],
    queryFn: ({ pageParam }) => apiFetch<PastEventList>('/events/past', { query: { page: pageParam > 1 ? pageParam : undefined } }),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.current_page < last.meta.last_page ? last.meta.current_page + 1 : undefined),
  });

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error && !data) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{error.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
      </ThemedView>
    );
  }

  return (
    <ThemedView style={styles.container}>
      <SectionList
        sections={byMonth(data.pages.flatMap((page) => page.data))}
        keyExtractor={(event) => String(event.id)}
        renderSectionHeader={({ section }) => (
          <ThemedText type="subtitle" style={styles.month}>
            {section.title}
          </ThemedText>
        )}
        renderItem={({ item }) => (
          <View style={styles.item}>
            <EventCard event={item} />
            {item.album_slug ? (
              <Link href={`/gallery/${item.album_slug}`}>
                <ThemedText type="linkPrimary">{strings.pastHikes.photos}</ThemedText>
              </Link>
            ) : null}
          </View>
        )}
        stickySectionHeadersEnabled={false}
        ListHeaderComponent={<ThemedText themeColor="textSecondary">{strings.pastHikes.intro}</ThemedText>}
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.pastHikes.empty}</ThemedText>}
        contentContainerStyle={styles.list}
        onEndReached={() => hasNextPage && !isFetchingNextPage && void fetchNextPage()}
        ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : undefined}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  month: { marginTop: Spacing.three },
  item: { gap: Spacing.two, marginTop: Spacing.three },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
