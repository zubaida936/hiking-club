import { useInfiniteQuery } from '@tanstack/react-query';
import { ActivityIndicator, FlatList, StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import type { BookingList } from '@/api/types';
import { BookingCard } from '@/components/booking-card';
import { SavedCopyNote } from '@/components/saved-copy-note';
import { ThemedView } from '@/components/themed-view';
import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Hikes the member attended, newest first, with the points each earned (GET /me/bookings?status=completed). */
export default function MyHikesScreen() {
  const { data, error, isPending, refetch, fetchNextPage, hasNextPage, isFetchingNextPage, isRefetching, dataUpdatedAt } = useInfiniteQuery({
    queryKey: ['my', 'bookings', 'completed'],
    queryFn: ({ pageParam }) => apiFetch<BookingList>('/me/bookings', { query: { status: 'completed', page: pageParam } }),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.current_page < last.meta.last_page ? last.meta.current_page + 1 : undefined),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error && !data) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  return (
    <ThemedView style={styles.container}>
      <FlatList
        data={data.pages.flatMap((page) => page.data)}
        keyExtractor={(booking) => booking.token}
        renderItem={({ item }) => <BookingCard booking={item} />}
        contentContainerStyle={styles.list}
        ListHeaderComponent={<SavedCopyNote error={error} updatedAt={dataUpdatedAt} />}
        ListEmptyComponent={<EmptyState inline message={strings.myBookings.completedEmpty} />}
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
});
