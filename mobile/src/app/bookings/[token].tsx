import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { ActivityIndicator, Alert, Linking, StyleSheet, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';

import { ApiError, apiFetch } from '@/api/client';
import { formMessage } from '@/api/errors';
import type { Booking } from '@/api/types';
import { Button, FormError } from '@/components/form';
import { PaymentPanel } from '@/components/payment-panel';
import { SavedCopyNote } from '@/components/saved-copy-note';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatDay, formatMoment } from '@/format';
import { QR_PREFIX } from '@/organizer/check-in-store';
import { strings } from '@/strings';

/**
 * A booking, opened with its secret token: status, reference and what to do next (pay, report a transfer,
 * cancel), the same as the website's booking page (GET /bookings/{token}).
 */
export default function BookingScreen() {
  const { token } = useLocalSearchParams<{ token: string }>();
  const queryClient = useQueryClient();
  const path = `/bookings/${encodeURIComponent(token)}`;
  const queryKey = ['my', 'booking', token];

  const { data: booking, error, isPending, refetch, dataUpdatedAt } = useQuery({
    queryKey,
    queryFn: () => apiFetch<{ data: Booking }>(path).then((answer) => answer.data),
  });

  const update = (fresh: Booking) => {
    queryClient.setQueryData(queryKey, fresh);
    // Seats and "My upcoming hikes" changed too.
    void queryClient.invalidateQueries({ queryKey: ['events'] });
    void queryClient.invalidateQueries({ queryKey: ['my', 'bookings'] });
  };

  const cancel = useMutation({
    mutationFn: () => apiFetch<{ data: Booking }>(`${path}/cancel`, { method: 'POST' }),
    onSuccess: ({ data }) => update(data),
  });

  // On the waitlist the same cancel means leaving it: say so.
  const waitlisted = booking?.status.key === 'waitlisted';
  const confirmCancel = () =>
    Alert.alert(waitlisted ? strings.booking.leaveConfirmTitle : strings.booking.cancelConfirmTitle, waitlisted ? strings.booking.leaveConfirmText : strings.booking.cancelConfirmText, [
      { text: strings.booking.keep, style: 'cancel' },
      { text: waitlisted ? strings.booking.leaveConfirm : strings.booking.cancelConfirm, style: 'destructive', onPress: () => cancel.mutate() },
    ]);

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error && !booking) {
    const notFound = error instanceof ApiError && error.status === 404;
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{notFound ? strings.booking.notFound : error.message}</ThemedText>
        {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
      </ThemedView>
    );
  }

  const { payment } = booking;

  return (
    <ScreenScroll>
      <SavedCopyNote error={error} updatedAt={dataUpdatedAt} />
      <ThemedText type="title">{strings.booking.titles[booking.status.key] ?? booking.status.label}</ThemedText>
      <ThemedText type="smallBold">
        {booking.event.title} · {formatDay(booking.event.starts_on, 'long')}
      </ThemedText>

      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="small" themeColor="textSecondary">{strings.booking.reference}</ThemedText>
        <ThemedText type="title" selectable>{booking.reference}</ThemedText>
        {booking.waitlist_position !== null ? <ThemedText>{strings.booking.waitlistPosition(booking.waitlist_position)}</ThemedText> : null}
        {booking.pickup?.point ? <ThemedText>{strings.booking.pickup(booking.pickup.point, booking.pickup.time)}</ThemedText> : null}
        {booking.cancellation.reason ? <ThemedText themeColor="danger">{booking.cancellation.reason}</ThemedText> : null}
      </ThemedView>

      {/* Organizers scan it at the meeting point (Organizer tab); it holds only the reference. */}
      {booking.status.key === 'confirmed' ? (
        <ThemedView type="backgroundElement" style={[styles.card, styles.qrCard]}>
          <ThemedText type="smallBold">{strings.booking.qrTitle}</ThemedText>
          <View style={styles.qr}>
            <QRCode value={QR_PREFIX + booking.reference} size={180} backgroundColor="#ffffff" color="#000000" />
          </View>
          <ThemedText type="small" themeColor="textSecondary" style={styles.qrHint}>{strings.booking.qrHint}</ThemedText>
        </ThemedView>
      ) : null}

      <PaymentPanel<Booking> path={path} amount={booking.amount_due.formatted} payment={payment} reload={refetch} onUpdated={update} />

      {payment.refund_due ? <ThemedText>{strings.booking.refundDue}</ThemedText> : null}
      {booking.status.key !== 'cancelled' ? <ThemedText type="small" themeColor="textSecondary">{strings.book.points(booking.points_to_earn)}</ThemedText> : null}

      {booking.whatsapp_url ? (
        <Button title={strings.booking.whatsapp} onPress={() => void Linking.openURL(booking.whatsapp_url as string)} variant="secondary" />
      ) : null}

      {booking.cancellation.can_cancel ? (
        <View style={styles.block}>
          {booking.cancellation.until ? (
            <ThemedText type="small" themeColor="textSecondary">{strings.booking.cancelUntil(formatMoment(booking.cancellation.until))}</ThemedText>
          ) : null}
          <Button title={waitlisted ? strings.booking.leaveWaitlist : strings.booking.cancel} onPress={confirmCancel} loading={cancel.isPending} variant="secondary" />
          <FormError message={formMessage(cancel.error)} />
        </View>
      ) : null}

      <Button title={strings.booking.backToHikes} onPress={() => router.dismissTo('/hikes')} variant="secondary" />
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  card: { borderRadius: Spacing.four, padding: Spacing.three, gap: Spacing.one },
  qrCard: { alignItems: 'center', gap: Spacing.two },
  // White around the code (also in dark mode) so cameras read it.
  qr: { backgroundColor: '#ffffff', padding: Spacing.three, borderRadius: Spacing.two },
  qrHint: { textAlign: 'center' },
  block: { gap: Spacing.two },
});
