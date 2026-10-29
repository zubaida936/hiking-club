import { Image } from 'expo-image';
import { StyleSheet, View } from 'react-native';

import type { Event } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/**
 * A hike in the list. Seats, fee and level come from the API as they are: the app never works them out.
 */
export function EventCard({ event }: { event: Event }) {
  const date = new Date(`${event.starts_on}T12:00:00`).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });

  const seats =
    event.status === 'cancelled'
      ? strings.hikes.cancelled
      : event.seats.sold_out
        ? event.registration.waitlist_open
          ? strings.hikes.waitlist
          : strings.hikes.soldOut
        : strings.hikes.seatsLeft(event.seats.left);

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      {event.cover_image_url ? <Image source={event.cover_image_url} style={styles.cover} contentFit="cover" /> : null}
      <View style={styles.body}>
        <ThemedText type="small" themeColor="textSecondary">
          {date} · {event.location}
        </ThemedText>
        <ThemedText type="subtitle">{event.title}</ThemedText>
        <ThemedText type="small">
          {event.difficulty.label} · {event.fee.formatted}
        </ThemedText>
        <ThemedText type="smallBold">{seats}</ThemedText>
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: Spacing.four, overflow: 'hidden' },
  cover: { width: '100%', height: 160 },
  body: { padding: Spacing.three, gap: Spacing.one },
});
