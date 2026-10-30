import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, useWindowDimensions, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ZoomableImage } from '@/components/zoomable-image';
import { Spacing } from '@/constants/theme';
import { sharePhoto } from '@/gallery/share-photo';
import { useAlbum } from '@/gallery/use-album';
import { strings } from '@/strings';

/**
 * Full-screen photos of an album (`?slug=&index=`): swipe between photos, pinch or double-tap to zoom (swiping
 * pauses while zoomed), "Share / save" through the phone's share sheet. Uses the album already loaded by its screen.
 */
export default function PhotoViewerScreen() {
  const { slug, index } = useLocalSearchParams<{ slug: string; index?: string }>();
  const { width } = useWindowDimensions();
  const { data: album } = useAlbum(slug);
  const [current, setCurrent] = useState(Number(index ?? 0) || 0);
  const [zoomed, setZoomed] = useState(false);
  const [sharing, setSharing] = useState(false);
  const [shareError, setShareError] = useState(false);

  if (!album) {
    return (
      <View style={[styles.container, styles.center]}>
        <ActivityIndicator color="#ffffff" />
      </View>
    );
  }

  const photo = album.photos[current];
  const share = async () => {
    if (!photo) return;
    setSharing(true);
    setShareError(false);
    try {
      await sharePhoto(photo, album.title);
    } catch {
      setShareError(true);
    } finally {
      setSharing(false);
    }
  };

  return (
    <View style={styles.container}>
      <FlatList
        data={album.photos}
        keyExtractor={(item) => String(item.id)}
        horizontal
        pagingEnabled
        scrollEnabled={!zoomed}
        showsHorizontalScrollIndicator={false}
        initialScrollIndex={Math.min(current, Math.max(0, album.photos.length - 1))}
        getItemLayout={(_, position) => ({ length: width, offset: width * position, index: position })}
        onMomentumScrollEnd={(event) => setCurrent(Math.round(event.nativeEvent.contentOffset.x / width))}
        renderItem={({ item, index: position }) => (
          <View style={{ width }}>
            <ZoomableImage
              uri={item.url}
              accessibilityLabel={strings.gallery.photo(position + 1, album.photos.length)}
              zoomed={zoomed && position === current}
              onZoomChange={setZoomed}
            />
          </View>
        )}
      />

      <SafeAreaView edges={['top']} style={styles.top} pointerEvents="box-none">
        <Pressable accessibilityRole="button" accessibilityLabel={strings.gallery.close} onPress={() => router.back()} hitSlop={Spacing.two}>
          <ThemedText style={styles.light}>✕ {strings.gallery.close}</ThemedText>
        </Pressable>
        <ThemedText style={styles.light}>{strings.gallery.photo(current + 1, album.photos.length)}</ThemedText>
      </SafeAreaView>

      <SafeAreaView edges={['bottom']} style={styles.bottom} pointerEvents="box-none">
        {shareError ? <ThemedText style={styles.light}>{strings.gallery.shareFailed}</ThemedText> : null}
        <Pressable accessibilityRole="button" accessibilityLabel={strings.gallery.share} accessibilityState={{ busy: sharing }} disabled={sharing} onPress={() => void share()} style={styles.shareButton}>
          {sharing ? <ActivityIndicator color="#ffffff" /> : <ThemedText type="smallBold" style={styles.light}>{strings.gallery.share}</ThemedText>}
        </Pressable>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  // Photos are shown on black in light and dark mode alike.
  container: { flex: 1, backgroundColor: '#000000' },
  center: { alignItems: 'center', justifyContent: 'center' },
  top: { position: 'absolute', top: 0, left: 0, right: 0, flexDirection: 'row', justifyContent: 'space-between', padding: Spacing.three },
  bottom: { position: 'absolute', bottom: 0, left: 0, right: 0, alignItems: 'center', gap: Spacing.two, padding: Spacing.three },
  shareButton: { borderRadius: 999, borderWidth: 1, borderColor: '#ffffff', paddingHorizontal: Spacing.four, paddingVertical: Spacing.two, minHeight: 44, justifyContent: 'center' },
  light: { color: '#ffffff' },
});
