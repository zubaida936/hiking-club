import { Image } from 'expo-image';
import type { ReactNode } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';

import type { EventDetail } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatDay, formatHours, formatKm, formatMoment, paragraphs } from '@/format';
import { strings } from '@/strings';

/** Google Maps search for a pickup point: the API has its name, not its coordinates. */
export function mapsUrl(place: string): string {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${place}, Lebanon`)}`;
}

/**
 * Everything about one hike (GET /events/{slug}), in the order of the website's hike page.
 * Seats, fee, level and booking state are shown as the API sends them.
 */
export function EventDetailView({ event }: { event: EventDetail }) {
  const cancelled = event.status === 'cancelled';
  const seats = event.seats.sold_out
    ? event.registration.waitlist_open
      ? strings.hikes.waitlist
      : strings.hikes.soldOut
    : strings.hikes.seatsLeft(event.seats.left);

  return (
    <>
      {event.cover_image_url ? <Image source={event.cover_image_url} style={styles.cover} contentFit="cover" accessibilityIgnoresInvertColors /> : null}

      <View style={styles.block}>
        {event.season_tag ? <ThemedText type="small" themeColor="textSecondary">{event.season_tag}</ThemedText> : null}
        <ThemedText type="title">{event.title}</ThemedText>
        <ThemedText type="smallBold">
          {formatDay(event.starts_on, 'long')} · {event.location}
        </ThemedText>
      </View>

      {cancelled ? (
        <Notice tone="danger" title={strings.hike.cancelledTitle}>
          {event.cancellation_reason}
        </Notice>
      ) : null}

      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="subtitle">{event.difficulty.label}</ThemedText>
        <ThemedText type="small">{event.difficulty.description}</ThemedText>
        <ThemedText type="small" themeColor="textSecondary">{strings.hike.effort(event.effort_score)}</ThemedText>
        {event.is_xtreme ? <ThemedText type="smallBold">{strings.hike.xtreme}</ThemedText> : null}
      </ThemedView>

      <View style={styles.facts}>
        <Fact label={strings.hike.distance} value={formatKm(event.distance_km)} />
        <Fact label={strings.hike.elevation} value={`+${event.elevation_gain_m} / -${event.elevation_loss_m} m`} />
        {event.duration_hours !== null ? <Fact label={strings.hike.duration} value={formatHours(event.duration_hours)} /> : null}
        <Fact label={strings.hike.fee} value={event.fee.formatted} />
      </View>

      {!cancelled ? (
        <View style={styles.block}>
          <ThemedText type="smallBold">{seats}</ThemedText>
          <ThemedText type="small" themeColor="textSecondary">
            {event.registration.open ? strings.hike.bookingOpen(formatMoment(event.registration.closes_at)) : strings.hike.bookingClosed}
          </ThemedText>
          {event.registration.open && event.registration.requires_level_acknowledgement ? (
            <ThemedText type="small" themeColor="textSecondary">{strings.hike.levelAcknowledgement}</ThemedText>
          ) : null}
        </View>
      ) : null}

      {event.updates.length > 0 && !cancelled ? (
        <Section title={strings.hike.updates}>
          {event.updates.map((update) => (
            <Notice key={update.posted_at + update.message} tone="info" title={formatMoment(update.posted_at)}>
              {update.message}
            </Notice>
          ))}
        </Section>
      ) : null}

      {event.summary || event.description ? (
        <Section title={strings.hike.about}>
          {event.summary ? <ThemedText type="smallBold">{event.summary}</ThemedText> : null}
          {paragraphs(event.description).map((paragraph) => (
            <ThemedText key={paragraph}>{paragraph}</ThemedText>
          ))}
        </Section>
      ) : null}

      {event.itinerary.length > 0 ? (
        <Section title={strings.hike.itinerary}>
          {event.itinerary.map((step) => (
            <View key={`${step.time}-${step.text}`} style={styles.row}>
              <ThemedText type="smallBold" style={styles.time}>{step.time ?? '·'}</ThemedText>
              <ThemedText style={styles.flex}>{step.text}</ThemedText>
            </View>
          ))}
        </Section>
      ) : null}

      {event.meeting_points.length > 0 ? (
        <Section title={strings.hike.meetingPoints}>
          {event.meeting_points.map((point) => (
            <Pressable
              key={point.name}
              accessibilityRole="link"
              accessibilityHint={strings.hike.openInMaps}
              onPress={() => Linking.openURL(mapsUrl(point.name))}
              style={styles.row}>
              <ThemedText type="smallBold" style={styles.time}>{point.pickup_time}</ThemedText>
              <ThemedText type="linkPrimary" style={styles.flex}>{point.name}</ThemedText>
            </Pressable>
          ))}
        </Section>
      ) : null}

      <List title={strings.hike.included} items={event.included} />
      <List title={strings.hike.whatToBring} items={event.what_to_bring} />

      {event.gpx_url ? (
        <Section title={strings.hike.route}>
          <Pressable accessibilityRole="link" onPress={() => Linking.openURL(event.gpx_url as string)}>
            <ThemedText type="linkPrimary">{strings.hike.downloadGpx}</ThemedText>
          </Pressable>
        </Section>
      ) : null}

      {event.photo_credit ? <ThemedText type="small" themeColor="textSecondary">{strings.hike.photoCredit(event.photo_credit)}</ThemedText> : null}
    </>
  );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
  return (
    <View style={styles.block}>
      <ThemedText type="subtitle">{title}</ThemedText>
      {children}
    </View>
  );
}

function List({ title, items }: { title: string; items: string[] }) {
  if (items.length === 0) return null;

  return (
    <Section title={title}>
      {items.map((item) => (
        <ThemedText key={item}>• {item}</ThemedText>
      ))}
    </Section>
  );
}

function Fact({ label, value }: { label: string; value: string }) {
  return (
    <ThemedView type="backgroundElement" style={styles.fact}>
      <ThemedText type="small" themeColor="textSecondary">{label}</ThemedText>
      <ThemedText type="smallBold">{value}</ThemedText>
    </ThemedView>
  );
}

function Notice({ title, tone, children }: { title: string; tone: 'info' | 'danger'; children: ReactNode }) {
  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <ThemedText type="smallBold" themeColor={tone === 'danger' ? 'danger' : 'text'}>{title}</ThemedText>
      {children ? <ThemedText>{children}</ThemedText> : null}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  cover: { width: '100%', height: 220, borderRadius: Spacing.four },
  block: { gap: Spacing.two },
  card: { borderRadius: Spacing.four, padding: Spacing.three, gap: Spacing.one },
  facts: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.two },
  fact: { borderRadius: Spacing.three, paddingHorizontal: Spacing.three, paddingVertical: Spacing.two, gap: Spacing.half },
  row: { flexDirection: 'row', gap: Spacing.three, alignItems: 'flex-start' },
  time: { width: 48 },
  flex: { flex: 1 },
});
