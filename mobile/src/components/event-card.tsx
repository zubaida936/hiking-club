import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import type { Event } from '@/api/types';
import { Card } from '@/components/card';
import { DifficultyBadge } from '@/components/difficulty-badge';
import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { formatDay } from '@/format';
import { strings } from '@/strings';

/**
 * A hike in the list; opens its detail screen. Seats, fee and level come from the API as they are:
 * the app never works them out.
 */
export function EventCard({ event }: { event: Event }) {
  const seats =
    event.status === 'cancelled'
      ? strings.hikes.cancelled
      : event.seats.sold_out
        ? event.registration.waitlist_open
          ? strings.hikes.waitlist
          : strings.hikes.soldOut
        : strings.hikes.seatsLeft(event.seats.left);

  return (
    <Link href={`/events/${event.slug}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={`${event.title}, ${formatDay(event.starts_on)}`}>
        <Card flush>
          {event.cover_image_url ? <Image source={event.cover_image_url} style={styles.cover} contentFit="cover" /> : null}
          <View style={styles.body}>
            <ThemedText type="small" themeColor="textSecondary">
              {formatDay(event.starts_on)} · {event.location}
            </ThemedText>
            <ThemedText type="subtitle">{event.title}</ThemedText>
            <View style={styles.row}>
              <DifficultyBadge difficulty={event.difficulty} score={event.effort_score} />
              <ThemedText type="small">{event.fee.formatted}</ThemedText>
            </View>
            <ThemedText type="smallBold">{seats}</ThemedText>
          </View>
        </Card>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  cover: { width: '100%', height: 160 },
  body: { padding: Spacing.three, gap: Spacing.one },
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.two, flexWrap: 'wrap' },
});
