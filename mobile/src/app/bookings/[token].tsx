import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useState } from 'react';
import { ActivityIndicator, Alert, Linking, StyleSheet, View } from 'react-native';

import { ApiError, apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { Booking } from '@/api/types';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatDay, formatMoment } from '@/format';
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
  const [transaction, setTransaction] = useState('');

  const { data: booking, error, isPending, refetch } = useQuery({
    queryKey,
    queryFn: () => apiFetch<{ data: Booking }>(path).then((answer) => answer.data),
  });

  const update = (fresh: Booking) => {
    queryClient.setQueryData(queryKey, fresh);
    // Seats and "My upcoming hikes" changed too.
    void queryClient.invalidateQueries({ queryKey: ['events'] });
    void queryClient.invalidateQueries({ queryKey: ['my', 'bookings'] });
  };

  const pay = useMutation({
    mutationFn: () => apiFetch<{ checkout_url: string }>(`${path}/pay`, { method: 'POST' }),
    // The provider page opens in an in-app browser; it confirms the payment, then we reload the booking.
    onSuccess: async ({ checkout_url }) => {
      await WebBrowser.openBrowserAsync(checkout_url);
      await refetch();
    },
  });

  const report = useMutation({
    mutationFn: () => apiFetch<{ data: Booking }>(`${path}/transfer`, { method: 'POST', body: { payment_reference: transaction } }),
    onSuccess: ({ data }) => {
      setTransaction('');
      update(data);
    },
  });

  const cancel = useMutation({
    mutationFn: () => apiFetch<{ data: Booking }>(`${path}/cancel`, { method: 'POST' }),
    onSuccess: ({ data }) => update(data),
  });

  const confirmCancel = () =>
    Alert.alert(strings.booking.cancelConfirmTitle, strings.booking.cancelConfirmText, [
      { text: strings.booking.keep, style: 'cancel' },
      { text: strings.booking.cancelConfirm, style: 'destructive', onPress: () => cancel.mutate() },
    ]);

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error) {
    const notFound = error instanceof ApiError && error.status === 404;
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{notFound ? strings.booking.notFound : error.message}</ThemedText>
        {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
      </ThemedView>
    );
  }

  const { payment } = booking;
  const instructions = payment.instructions;

  return (
    <ScreenScroll>
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

      {payment.state !== 'none' ? (
        <View style={styles.block}>
          <ThemedText type="subtitle">
            {strings.booking.amount} · {booking.amount_due.formatted}
          </ThemedText>

          {payment.state === 'paid' ? <ThemedText type="smallBold">{strings.booking.paid}</ThemedText> : null}
          {payment.state === 'after_confirmation' ? <ThemedText>{strings.booking.afterConfirmation}</ThemedText> : null}

          {instructions?.type === 'online' ? (
            <Button title={strings.booking.payOnline(booking.amount_due.formatted)} onPress={() => pay.mutate()} loading={pay.isPending} />
          ) : null}

          {instructions?.type === 'transfer' ? (
            <>
              <ThemedText>{strings.booking.transferSteps(instructions.provider, booking.amount_due.formatted)}</ThemedText>
              <ThemedText type="smallBold" selectable>
                {instructions.account_name} · {instructions.account_number}
              </ThemedText>
              <ThemedText>{strings.booking.transferNote(instructions.note)}</ThemedText>
              {instructions.reported_transaction_number ? (
                <ThemedText type="small">{strings.booking.transactionReceived(instructions.reported_transaction_number)}</ThemedText>
              ) : null}
              <FormField label={strings.booking.transactionNumber} value={transaction} onChangeText={setTransaction} keyboardType="number-pad" maxLength={60} error={fieldErrors(report.error).payment_reference} />
              <Button title={strings.booking.sendTransaction} onPress={() => report.mutate()} loading={report.isPending} variant="secondary" />
              <FormError message={formMessage(report.error)} />
            </>
          ) : null}

          {instructions?.type === 'cash' || instructions?.type === 'contact' ? <ThemedText>{instructions.message}</ThemedText> : null}
          <FormError message={formMessage(pay.error)} />
        </View>
      ) : null}

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
          <Button title={strings.booking.cancel} onPress={confirmCancel} loading={cancel.isPending} variant="secondary" />
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
  block: { gap: Spacing.two },
});
