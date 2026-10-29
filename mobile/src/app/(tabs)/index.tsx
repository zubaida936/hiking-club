import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { Link, router } from 'expo-router';
import { ActivityIndicator, Linking, ScrollView, StyleSheet, useWindowDimensions, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { Home, Slide } from '@/api/types';
import { EventCard } from '@/components/event-card';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { appRouteFor } from '@/links';
import { strings } from '@/strings';

/** Home (GET /home): the homepage slides, the next hike and the top 10 hikers. */
export default function HomeScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['home'],
    queryFn: () => apiFetch<{ data: Home }>('/home').then((answer) => answer.data),
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
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
        {data.slides.length > 0 ? <Slides slides={data.slides} /> : null}

        <ThemedText type="subtitle">{strings.home.nextHike}</ThemedText>
        {data.next_event ? <EventCard event={data.next_event} /> : <ThemedText themeColor="textSecondary">{strings.home.noNextHike}</ThemedText>}
        <Link href="/hikes">
          <ThemedText type="linkPrimary">{strings.home.allHikes}</ThemedText>
        </Link>

        {data.top_hikers.length > 0 ? (
          <View style={styles.block}>
            <ThemedText type="subtitle">{strings.home.topHikers}</ThemedText>
            {data.top_hikers.map((hiker) => (
              <ThemedView key={hiker.position} type="backgroundElement" style={styles.hiker}>
                <ThemedText type="smallBold" style={styles.position}>{hiker.position}</ThemedText>
                <View style={styles.flex}>
                  <ThemedText>{hiker.name}</ThemedText>
                  <ThemedText type="small" themeColor="textSecondary">{hiker.rank.label}</ThemedText>
                </View>
                <ThemedText type="smallBold">{strings.home.points(hiker.points)}</ThemedText>
              </ThemedView>
            ))}
          </View>
        ) : null}
      </ScreenScroll>
    </SafeAreaView>
  );
}

/** Swipeable homepage slides. A slide's button opens the matching app screen, or the website. */
function Slides({ slides }: { slides: Slide[] }) {
  const { width } = useWindowDimensions();
  const slideWidth = Math.min(width, 800) - Spacing.three * 2;

  const open = (url: string) => {
    const route = appRouteFor(url);
    if (route) {
      router.push(route);
    } else {
      void Linking.openURL(url);
    }
  };

  return (
    <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false} snapToInterval={slideWidth + Spacing.two} decelerationRate="fast" contentContainerStyle={styles.slides}>
      {slides.map((slide) => (
        <View key={slide.heading} style={[styles.slide, { width: slideWidth }]}>
          {slide.image ? <Image source={slide.image} style={StyleSheet.absoluteFill} contentFit="cover" accessibilityIgnoresInvertColors /> : null}
          <View style={styles.shade} />
          <View style={styles.slideText}>
            {slide.eyebrow ? <ThemedText type="small" style={styles.light}>{slide.eyebrow}</ThemedText> : null}
            <ThemedText type="subtitle" style={styles.light}>{slide.heading}</ThemedText>
            {slide.text ? <ThemedText type="small" style={styles.light}>{slide.text}</ThemedText> : null}
            {slide.cta && slide.url ? <Button title={slide.cta} onPress={() => open(slide.url as string)} /> : null}
          </View>
        </View>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  slides: { gap: Spacing.two },
  slide: { height: 260, borderRadius: Spacing.four, overflow: 'hidden', backgroundColor: '#1f3d2b', justifyContent: 'flex-end' },
  shade: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: 'rgba(0,0,0,0.35)' },
  slideText: { padding: Spacing.three, gap: Spacing.one },
  light: { color: '#ffffff' },
  block: { gap: Spacing.two },
  hiker: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three, borderRadius: Spacing.three, padding: Spacing.three },
  position: { width: 24, textAlign: 'center' },
  flex: { flex: 1 },
});
