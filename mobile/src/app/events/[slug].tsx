import { useQuery } from '@tanstack/react-query';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { ActivityIndicator, Pressable, Share, StyleSheet } from 'react-native';

import { ApiError, apiFetch } from '@/api/client';
import type { EventDetail } from '@/api/types';
import { EventDetailView } from '@/components/event-detail';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatDay } from '@/format';
import { strings } from '@/strings';

/** One hike, opened from the hikes list (or a shared link later, with deep links). */
export default function HikeScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { data: event, error, isPending, refetch } = useQuery({
    queryKey: ['events', 'detail', slug],
    queryFn: () => apiFetch<{ data: EventDetail }>(`/events/${encodeURIComponent(slug)}`).then((answer) => answer.data),
  });

  const share = () => {
    if (!event) return;
    void Share.share({ message: strings.hike.shareMessage(event.title, formatDay(event.starts_on, 'long'), event.web_url) });
  };

  return (
    <>
      <Stack.Screen
        options={{
          title: event?.title ?? '',
          headerRight: () =>
            event ? (
              <Pressable accessibilityRole="button" accessibilityLabel={strings.hike.share} onPress={share} hitSlop={Spacing.two}>
                <ThemedText type="linkPrimary">{strings.hike.share}</ThemedText>
              </Pressable>
            ) : null,
        }}
      />

      {isPending ? (
        <ThemedView style={styles.center}>
          <ActivityIndicator />
        </ThemedView>
      ) : error ? (
        <ThemedView style={styles.center}>
          <ThemedText style={styles.text}>{error instanceof ApiError && error.status === 404 ? strings.hike.notFound : error.message}</ThemedText>
          {!(error instanceof ApiError && error.status === 404) ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
        </ThemedView>
      ) : (
        <ScreenScroll>
          <EventDetailView event={event} />
          {/* Open for booking: a free seat, or a waitlist when full (the booking form explains the rest). */}
          {event.registration.open && (!event.seats.sold_out || event.registration.waitlist_open) ? (
            <Button
              title={event.seats.sold_out ? strings.hike.joinWaitlist : strings.hike.reserve}
              onPress={() => router.push(`/book/${event.slug}`)}
            />
          ) : null}
        </ScreenScroll>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
