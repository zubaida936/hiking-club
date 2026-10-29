import { useInfiniteQuery } from '@tanstack/react-query';
import { ActivityIndicator, FlatList, StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import type { BookingList } from '@/api/types';
import { BookingCard } from '@/components/booking-card';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Hikes the member attended, newest first, with the points each earned (GET /me/bookings?status=completed). */
export default function MyHikesScreen() {
  const { data, error, isPending, refetch, fetchNextPage, hasNextPage, isFetchingNextPage, isRefetching } = useInfiniteQuery({
    queryKey: ['my', 'bookings', 'completed'],
    queryFn: ({ pageParam }) => apiFetch<BookingList>('/me/bookings', { query: { status: 'completed', page: pageParam } }),
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
        data={data.pages.flatMap((page) => page.data)}
        keyExtractor={(booking) => booking.token}
        renderItem={({ item }) => <BookingCard booking={item} />}
        contentContainerStyle={styles.list}
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.myBookings.completedEmpty}</ThemedText>}
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
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
