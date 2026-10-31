import { useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { ApiError, apiFetch } from '@/api/client';
import type { Order } from '@/api/types';
import { Button } from '@/components/form';
import { PaymentPanel } from '@/components/payment-panel';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** A shop order, opened with its secret token (GET /orders/{token}): status, items, totals, delivery and payment. */
export default function OrderScreen() {
  const { token } = useLocalSearchParams<{ token: string }>();
  const queryClient = useQueryClient();
  const path = `/orders/${encodeURIComponent(token)}`;
  const queryKey = ['my', 'order', token];

  const { data: order, error, isPending, refetch } = useQuery({
    queryKey,
    queryFn: () => apiFetch<{ data: Order }>(path).then((answer) => answer.data),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    const notFound = error instanceof ApiError && error.status === 404;
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{notFound ? strings.order.notFound : error.message}</ThemedText>
        {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
      </ThemedView>
    );
  }

  return (
    <ScreenScroll>
      <ThemedText type="title">{order.status.label}</ThemedText>

      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="small" themeColor="textSecondary">{strings.order.reference}</ThemedText>
        <ThemedText type="title" selectable>{order.reference}</ThemedText>
        {order.cancellation?.reason ? <ThemedText themeColor="danger">{order.cancellation.reason}</ThemedText> : null}
      </ThemedView>

      <View style={styles.block}>
        <ThemedText type="subtitle">{strings.order.items}</ThemedText>
        {order.items.map((item) => (
          <View key={`${item.product_name}-${item.variant_name}`} style={styles.row}>
            <ThemedText style={styles.flex}>
              {item.quantity} × {item.product_name}
              {item.variant_name !== 'One size' ? ` (${item.variant_name})` : ''}
            </ThemedText>
            <ThemedText>{item.line_total.formatted}</ThemedText>
          </View>
        ))}
        <View style={styles.row}>
          <ThemedText themeColor="textSecondary">{strings.order.subtotal}</ThemedText>
          <ThemedText>{order.subtotal.formatted}</ThemedText>
        </View>
        {order.delivery_fee.cents > 0 ? (
          <View style={styles.row}>
            <ThemedText themeColor="textSecondary">{strings.order.delivery}</ThemedText>
            <ThemedText>{order.delivery_fee.formatted}</ThemedText>
          </View>
        ) : null}
        <View style={styles.row}>
          <ThemedText type="smallBold">{strings.order.total}</ThemedText>
          <ThemedText type="smallBold">{order.total.formatted}</ThemedText>
        </View>
      </View>

      <View style={styles.block}>
        <ThemedText type="subtitle">{order.fulfilment.label}</ThemedText>
        {order.fulfilment.address ? (
          <ThemedText>
            {order.fulfilment.address}
            {order.fulfilment.city ? `, ${order.fulfilment.city}` : ''}
          </ThemedText>
        ) : null}
        <ThemedText type="small" themeColor="textSecondary">{order.fulfilment.note}</ThemedText>
      </View>

      <PaymentPanel<Order>
        path={path}
        amount={order.total.formatted}
        payment={order.payment}
        reload={refetch}
        onUpdated={(fresh) => {
          queryClient.setQueryData(queryKey, fresh);
          void queryClient.invalidateQueries({ queryKey: ['my', 'orders'] });
        }}
      />

      <Button title={strings.order.backToShop} onPress={() => router.dismissTo('/shop')} variant="secondary" />
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  card: { borderRadius: Spacing.four, padding: Spacing.three, gap: Spacing.one },
  block: { gap: Spacing.two },
  row: { flexDirection: 'row', justifyContent: 'space-between', gap: Spacing.three },
  flex: { flex: 1 },
});
