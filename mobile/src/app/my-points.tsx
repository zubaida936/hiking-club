import { useInfiniteQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { ActivityIndicator, FlatList, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { PointLine, PointList } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/** The member's points ledger, newest first (GET /me/points). Corrections are lines with negative points. */
export default function MyPointsScreen() {
  const { data, error, isPending, refetch, fetchNextPage, hasNextPage, isFetchingNextPage, isRefetching } = useInfiniteQuery({
    queryKey: ['my', 'points'],
    queryFn: ({ pageParam }) => apiFetch<PointList>('/me/points', { query: { page: pageParam } }),
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
        keyExtractor={(line) => String(line.id)}
        renderItem={({ item }) => <PointRow line={item} />}
        contentContainerStyle={styles.list}
        ListHeaderComponent={
          <Link href="/rank-system">
            <ThemedText type="linkPrimary">{strings.rankSystem.link}</ThemedText>
          </Link>
        }
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.points.empty}</ThemedText>}
        onEndReached={() => hasNextPage && !isFetchingNextPage && void fetchNextPage()}
        ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : undefined}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

function PointRow({ line }: { line: PointLine }) {
  return (
    <ThemedView type="backgroundElement" style={styles.row}>
      <View style={styles.flex}>
        <ThemedText type="small" themeColor="textSecondary">
          {formatMoment(line.created_at)} · {line.type.label}
        </ThemedText>
        <ThemedText>{line.description}</ThemedText>
        {line.event?.title ? <ThemedText type="small">{line.event.title}</ThemedText> : null}
      </View>
      <ThemedText type="subtitle" themeColor={line.points < 0 ? 'danger' : 'accent'}>
        {strings.points.amount(line.points)}
      </ThemedText>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.two, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three, borderRadius: Spacing.three, padding: Spacing.three },
  flex: { flex: 1, gap: Spacing.half },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
