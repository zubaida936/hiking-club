import { useQuery } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { ActivityIndicator, FlatList, Pressable, StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import type { Order, OrderList } from '@/api/types';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/** Orders placed with my phone number, newest first (GET /me/orders). */
export default function MyOrdersScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['my', 'orders'],
    queryFn: () => apiFetch<OrderList>('/me/orders').then((answer) => answer.data),
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
    <ThemedView style={styles.container}>
      <FlatList
        data={data}
        keyExtractor={(order) => order.token}
        renderItem={({ item }) => <OrderCard order={item} />}
        contentContainerStyle={styles.list}
        ListEmptyComponent={<ThemedText style={styles.text}>{strings.order.myOrdersEmpty}</ThemedText>}
        refreshing={isRefetching}
        onRefresh={() => void refetch()}
      />
    </ThemedView>
  );
}

function OrderCard({ order }: { order: Order }) {
  const pieces = order.items.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <Link href={`/orders/${order.token}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={`${strings.order.reference} ${order.reference}`}>
        <ThemedView type="backgroundElement" style={styles.card}>
          <ThemedText type="small" themeColor="textSecondary">
            {formatMoment(order.ordered_at)} · {order.status.label}
          </ThemedText>
          <ThemedText type="smallBold">{order.reference}</ThemedText>
          <ThemedText type="small">
            {order.items.map((item) => item.product_name).join(', ')} ({pieces})
          </ThemedText>
          <ThemedText type="smallBold">{order.total.formatted}</ThemedText>
        </ThemedView>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.half },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
