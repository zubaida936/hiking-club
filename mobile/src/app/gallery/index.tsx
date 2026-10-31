import { useInfiniteQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { AlbumCard, AlbumList } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatDay } from '@/format';
import { strings } from '@/strings';

/** Photo albums (GET /gallery), newest first, two per row; loads more while scrolling. */
export default function GalleryScreen() {
  const { data, error, isPending, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: ['gallery'],
    queryFn: ({ pageParam }) => apiFetch<AlbumList>('/gallery', { query: { page: pageParam > 1 ? pageParam : undefined } }),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.current_page < last.meta.last_page ? last.meta.current_page + 1 : undefined),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  return (
    <ThemedView style={styles.container}>
      <FlatList
        data={data.pages.flatMap((page) => page.data)}
        keyExtractor={(album) => album.slug}
        numColumns={2}
        columnWrapperStyle={styles.row}
        renderItem={({ item }) => <AlbumTile album={item} />}
        ListHeaderComponent={<ThemedText themeColor="textSecondary">{strings.gallery.intro}</ThemedText>}
        ListEmptyComponent={<EmptyState inline message={strings.gallery.empty} />}
        contentContainerStyle={styles.list}
        onEndReached={() => hasNextPage && !isFetchingNextPage && void fetchNextPage()}
        ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : undefined}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

function AlbumTile({ album }: { album: AlbumCard }) {
  return (
    <Link href={`/gallery/${album.slug}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={`${album.title}, ${strings.gallery.photos(album.photos_count)}`} style={styles.tile}>
        <ThemedView type="backgroundElement" style={styles.card}>
          {album.cover_url ? <Image source={album.cover_url} style={styles.cover} contentFit="cover" /> : <View style={styles.cover} />}
          <View style={styles.body}>
            <ThemedText type="smallBold" numberOfLines={2}>
              {album.title}
            </ThemedText>
            <ThemedText type="small" themeColor="textSecondary">
              {[album.taken_on ? formatDay(album.taken_on) : null, strings.gallery.photos(album.photos_count)].filter(Boolean).join(' · ')}
            </ThemedText>
          </View>
        </ThemedView>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  row: { gap: Spacing.three },
  tile: { flex: 1 },
  card: { borderRadius: Spacing.three, overflow: 'hidden' },
  cover: { width: '100%', aspectRatio: 4 / 3 },
  body: { padding: Spacing.two, gap: Spacing.half },
});
