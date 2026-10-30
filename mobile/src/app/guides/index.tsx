import { useInfiniteQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { ArticleCard, ArticleList } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatDate } from '@/format';
import { strings } from '@/strings';

/** The guide articles (GET /guides), newest first; loads more while scrolling. Only published ones come from the API. */
export default function GuidesScreen() {
  const { data, error, isPending, refetch, isRefetching, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: ['guides'],
    queryFn: ({ pageParam }) => apiFetch<ArticleList>('/guides', { query: { page: pageParam > 1 ? pageParam : undefined } }),
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
        keyExtractor={(article) => article.slug}
        renderItem={({ item }) => <GuideCard article={item} />}
        ListHeaderComponent={<ThemedText themeColor="textSecondary">{strings.guides.intro}</ThemedText>}
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.guides.empty}</ThemedText>}
        contentContainerStyle={styles.list}
        onEndReached={() => hasNextPage && !isFetchingNextPage && void fetchNextPage()}
        ListFooterComponent={isFetchingNextPage ? <ActivityIndicator /> : undefined}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

function GuideCard({ article }: { article: ArticleCard }) {
  return (
    <Link href={`/guides/${article.slug}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={article.title}>
        <ThemedView type="backgroundElement" style={styles.card}>
          {article.cover_url ? <Image source={article.cover_url} style={styles.cover} contentFit="cover" /> : null}
          <View style={styles.body}>
            <ThemedText type="subtitle">{article.title}</ThemedText>
            <ThemedText type="small" themeColor="textSecondary">
              {strings.guides.meta(article.author, article.published_at ? formatDate(article.published_at) : null, article.reading_minutes)}
            </ThemedText>
            <ThemedText type="small" numberOfLines={3}>
              {article.summary}
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
  card: { borderRadius: Spacing.four, overflow: 'hidden' },
  cover: { width: '100%', height: 160 },
  body: { padding: Spacing.three, gap: Spacing.one },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
