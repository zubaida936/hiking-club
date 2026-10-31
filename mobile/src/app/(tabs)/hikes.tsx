import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, ScrollView, StyleSheet, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { EventList, ResponseOf } from '@/api/types';
import { Chip } from '@/components/chip';
import { EventCard } from '@/components/event-card';
import { SavedCopyNote } from '@/components/saved-copy-note';
import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

const LEVELS = [1, 2, 3, 4] as const;

type Filters = { level: number | null; month: string | null; xtreme: boolean; search: string };

/** Waits until typing stops before searching, so the API isn't called for every letter. */
function useDebounced(value: string, delay = 400): string {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}

/** Upcoming hikes (GET /events), with search and level / month / X events filters; loads more while scrolling. */
export default function HikesScreen() {
  const theme = useTheme();
  const [typed, setTyped] = useState('');
  const [filters, setFilters] = useState<Omit<Filters, 'search'>>({ level: null, month: null, xtreme: false });
  const search = useDebounced(typed.trim());
  const active = filters.level !== null || filters.month !== null || filters.xtreme || search !== '';

  // Months that have upcoming hikes, for the month chips.
  const { data: months } = useQuery({
    queryKey: ['events', 'months'],
    queryFn: () => apiFetch<ResponseOf<'v1.events.months'>>('/events/months').then((answer) => answer.data.filter((month) => month.upcoming > 0).reverse()),
  });

  const { data, error, isPending, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage, dataUpdatedAt } = useInfiniteQuery({
    queryKey: ['events', 'upcoming', { ...filters, search }],
    queryFn: ({ pageParam }) =>
      apiFetch<EventList>('/events', {
        query: {
          level: filters.level ?? undefined,
          month: filters.month ?? undefined,
          xtreme: filters.xtreme ? 1 : undefined,
          search: search || undefined,
          page: pageParam > 1 ? pageParam : undefined,
        },
      }),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.current_page < last.meta.last_page ? last.meta.current_page + 1 : undefined),
  });

  const toggle = <K extends keyof typeof filters>(key: K, value: (typeof filters)[K]) =>
    setFilters((current) => ({ ...current, [key]: current[key] === value ? (key === 'xtreme' ? false : null) : value }));
  const clear = () => {
    setTyped('');
    setFilters({ level: null, month: null, xtreme: false });
  };

  const header = (
    <View style={styles.header}>
      <ThemedText type="title">{strings.hikes.title}</ThemedText>
      <SavedCopyNote error={error} updatedAt={dataUpdatedAt} />
      <View style={styles.links}>
        <Link href="/difficulty-calculator">
          <ThemedText type="linkPrimary">{strings.difficulty.link}</ThemedText>
        </Link>
        <Link href="/past-hikes">
          <ThemedText type="linkPrimary">{strings.pastHikes.link}</ThemedText>
        </Link>
      </View>
      <TextInput
        accessibilityLabel={strings.hikes.search}
        placeholder={strings.hikes.searchPlaceholder}
        placeholderTextColor={theme.textSecondary}
        value={typed}
        onChangeText={setTyped}
        returnKeyType="search"
        autoCorrect={false}
        style={[styles.search, { color: theme.text, borderColor: theme.border }]}
      />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        <Chip label={strings.hikes.allLevels} selected={filters.level === null} onPress={() => setFilters((current) => ({ ...current, level: null }))} />
        {LEVELS.map((level) => (
          <Chip key={level} label={strings.hikes.level(level)} selected={filters.level === level} onPress={() => toggle('level', level)} />
        ))}
        <Chip label={strings.hikes.xOnly} selected={filters.xtreme} onPress={() => toggle('xtreme', true)} />
      </ScrollView>
      {months && months.length > 0 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
          <Chip label={strings.hikes.anyMonth} selected={filters.month === null} onPress={() => setFilters((current) => ({ ...current, month: null }))} />
          {months.map((month) => (
            <Chip key={month.month} label={month.label} selected={filters.month === month.month} onPress={() => toggle('month', month.month)} />
          ))}
        </ScrollView>
      ) : null}
    </View>
  );

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        {error && !data ? (
          <>
            {header}
            <ErrorState inline message={error.message} onRetry={() => void refetch()} />
          </>
        ) : (
          <FlatList
            data={data?.pages.flatMap((page) => page.data) ?? []}
            keyExtractor={(event) => String(event.id)}
            renderItem={({ item }) => <EventCard event={item} />}
            ListHeaderComponent={header}
            contentContainerStyle={styles.list}
            keyboardShouldPersistTaps="handled"
            ListEmptyComponent={
              isPending ? (
                <LoadingState inline />
              ) : active ? (
                <EmptyState inline message={strings.hikes.noMatch} action={{ title: strings.hikes.clearFilters, onPress: clear }} />
              ) : (
                <EmptyState inline message={strings.hikes.empty} />
              )
            }
            onEndReached={() => hasNextPage && !isFetchingNextPage && void fetchNextPage()}
            ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : undefined}
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
  header: { gap: Spacing.two, marginTop: Spacing.three, marginBottom: Spacing.one },
  links: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.four },
  search: { borderWidth: 1, borderRadius: 999, paddingHorizontal: Spacing.three, paddingVertical: Spacing.two, fontSize: 16 },
  chips: { gap: Spacing.two, paddingVertical: Spacing.half },
  list: { gap: Spacing.three, paddingBottom: BottomTabInset + Spacing.three },
});
