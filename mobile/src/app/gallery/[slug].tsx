import { Image } from 'expo-image';
import { Link, router, Stack, useLocalSearchParams } from 'expo-router';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '@/api/client';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatDay, paragraphs } from '@/format';
import { useAlbum } from '@/gallery/use-album';
import { strings } from '@/strings';

/** One album (GET /gallery/{slug}): its photos three per row; a photo opens the full-screen viewer. */
export default function AlbumScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { data: album, error, isPending, refetch, isRefetching } = useAlbum(slug);
  const notFound = error instanceof ApiError && error.status === 404;

  return (
    <>
      <Stack.Screen options={{ title: album?.title ?? '' }} />
      {isPending ? (
        <ThemedView style={styles.center}>
          <ActivityIndicator />
        </ThemedView>
      ) : error ? (
        <ThemedView style={styles.center}>
          <ThemedText style={styles.text}>{notFound ? strings.gallery.notFound : error.message}</ThemedText>
          {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
        </ThemedView>
      ) : (
        <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
          <ThemedText type="small" themeColor="textSecondary">
            {[album.taken_on ? formatDay(album.taken_on, 'long') : null, strings.gallery.photos(album.photos.length)].filter(Boolean).join(' · ')}
          </ThemedText>
          {paragraphs(album.description).map((text) => (
            <ThemedText key={text}>{text}</ThemedText>
          ))}
          {album.event_slug ? (
            <Link href={`/events/${album.event_slug}`}>
              <ThemedText type="linkPrimary">{strings.gallery.seeHike}</ThemedText>
            </Link>
          ) : null}
          <View style={styles.grid}>
            {album.photos.map((photo, index) => (
              <Pressable
                key={photo.id}
                accessibilityRole="imagebutton"
                accessibilityLabel={strings.gallery.photo(index + 1, album.photos.length)}
                onPress={() => router.push({ pathname: '/photo-viewer', params: { slug: album.slug, index: String(index) } })}
                style={styles.thumb}>
                <Image source={photo.url} style={styles.fill} contentFit="cover" />
              </Pressable>
            ))}
          </View>
        </ScreenScroll>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.one },
  thumb: { width: '32.5%', aspectRatio: 1, borderRadius: Spacing.one, overflow: 'hidden' },
  fill: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
