import { useMutation } from '@tanstack/react-query';
import * as WebBrowser from 'expo-web-browser';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { Booking } from '@/api/types';
import { Button, FormError, FormField } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Bookings and shop orders share the payment states and instruction types (see the API's BookingResource). */
type Payment = { state: Booking['payment']['state']; instructions: Booking['payment']['instructions'] };

type Props<T> = {
  /** The booking or order, e.g. "/bookings/{token}" or "/orders/{token}": pay and transfer are under it. */
  path: string;
  amount: string;
  payment: Payment;
  /** Reload after an online payment (the provider confirms it on the website). */
  reload: () => Promise<unknown>;
  /** The booking or order as the API answered after a transfer number was sent. */
  onUpdated: (fresh: T) => void;
};

/**
 * What the hiker still has to do to pay: pay online in the in-app browser, send the Whish / OMT transaction
 * number, or the cash / contact message. Same on the booking and order screens.
 */
export function PaymentPanel<T>({ path, amount, payment, reload, onUpdated }: Props<T>) {
  const [transaction, setTransaction] = useState('');
  const instructions = payment.instructions;

  const pay = useMutation({
    mutationFn: () => apiFetch<{ checkout_url: string }>(`${path}/pay`, { method: 'POST' }),
    // The provider page opens in an in-app browser; it confirms the payment, then we reload.
    onSuccess: async ({ checkout_url }) => {
      await WebBrowser.openBrowserAsync(checkout_url);
      await reload();
    },
  });

  const report = useMutation({
    mutationFn: () => apiFetch<{ data: T }>(`${path}/transfer`, { method: 'POST', body: { payment_reference: transaction } }),
    onSuccess: ({ data }) => {
      setTransaction('');
      onUpdated(data);
    },
  });

  if (payment.state === 'none') return null;

  return (
    <View style={styles.block}>
      <ThemedText type="subtitle">
        {strings.booking.amount} · {amount}
      </ThemedText>

      {payment.state === 'paid' ? <ThemedText type="smallBold">{strings.booking.paid}</ThemedText> : null}
      {payment.state === 'after_confirmation' ? <ThemedText>{strings.booking.afterConfirmation}</ThemedText> : null}

      {instructions?.type === 'online' ? <Button title={strings.booking.payOnline(amount)} onPress={() => pay.mutate()} loading={pay.isPending} /> : null}

      {instructions?.type === 'transfer' ? (
        <>
          <ThemedText>{strings.booking.transferSteps(instructions.provider, amount)}</ThemedText>
          <ThemedText type="smallBold" selectable>
            {instructions.account_name} · {instructions.account_number}
          </ThemedText>
          <ThemedText>{strings.booking.transferNote(instructions.note)}</ThemedText>
          {instructions.reported_transaction_number ? (
            <ThemedText type="small">{strings.booking.transactionReceived(instructions.reported_transaction_number)}</ThemedText>
          ) : null}
          <FormField
            label={strings.booking.transactionNumber}
            value={transaction}
            onChangeText={setTransaction}
            keyboardType="number-pad"
            maxLength={60}
            error={fieldErrors(report.error).payment_reference}
          />
          <Button title={strings.booking.sendTransaction} onPress={() => report.mutate()} loading={report.isPending} variant="secondary" />
          <FormError message={formMessage(report.error)} />
        </>
      ) : null}

      {instructions?.type === 'cash' || instructions?.type === 'contact' ? <ThemedText>{instructions.message}</ThemedText> : null}
      <FormError message={formMessage(pay.error)} />
    </View>
  );
}

const styles = StyleSheet.create({
  block: { gap: Spacing.two },
});
