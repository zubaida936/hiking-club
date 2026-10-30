import { Image } from 'expo-image';
import { router } from 'expo-router';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import type { CartCheck } from '@/api/types';
import { Button } from '@/components/form';
import { QuantityStepper } from '@/components/quantity-stepper';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { MAX_QUANTITY, useCart } from '@/shop/cart';
import { useCartCheck } from '@/shop/use-cart-check';
import { strings } from '@/strings';

/** The cart (kept on the phone), priced by the server; change quantities, remove, go to checkout. */
export default function CartScreen() {
  const { items, setQuantity, remove } = useCart();
  const { data: check, error, isPending, refetch } = useCartCheck();

  if (items.length === 0) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{strings.cart.empty}</ThemedText>
        <Button title={strings.cart.browse} onPress={() => router.dismissTo('/shop')} variant="secondary" />
      </ThemedView>
    );
  }

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error || !check) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{error?.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
      </ThemedView>
    );
  }

  return (
    <ScreenScroll>
      {check.unavailable.length > 0 ? (
        <ThemedView type="backgroundElement" style={styles.notice}>
          <ThemedText>{strings.cart.removedUnavailable}</ThemedText>
          <Button title={strings.cart.remove} onPress={() => check.unavailable.forEach(remove)} variant="secondary" />
        </ThemedView>
      ) : null}

      {check.lines.map((line) => (
        <CartLine key={line.variant_id} line={line} onQuantity={(quantity) => setQuantity(line.variant_id, quantity)} onRemove={() => remove(line.variant_id)} />
      ))}

      <View style={styles.total}>
        <ThemedText type="subtitle">{strings.cart.subtotal}</ThemedText>
        <ThemedText type="subtitle">{check.subtotal.formatted}</ThemedText>
      </View>
      <Button title={strings.cart.checkout} onPress={() => (check.can_checkout ? router.push('/checkout') : undefined)} variant={check.can_checkout ? 'primary' : 'secondary'} />
    </ScreenScroll>
  );
}

function CartLine({ line, onQuantity, onRemove }: { line: CartCheck['lines'][number]; onQuantity: (quantity: number) => void; onRemove: () => void }) {
  return (
    <ThemedView type="backgroundElement" style={styles.line}>
      {line.product.image ? <Image source={line.product.image} style={styles.image} contentFit="cover" /> : <View style={[styles.image, styles.noImage]} />}
      <View style={styles.flex}>
        <ThemedText type="smallBold">{line.product.name}</ThemedText>
        {line.variant_name !== 'One size' ? <ThemedText type="small" themeColor="textSecondary">{line.variant_name}</ThemedText> : null}
        <ThemedText type="small">
          {line.unit_price.formatted} · {line.line_total.formatted}
        </ThemedText>
        {!line.in_stock ? <ThemedText type="small" themeColor="danger">{strings.cart.notEnough(line.stock)}</ThemedText> : null}
        <View style={styles.actions}>
          <QuantityStepper value={line.quantity} max={Math.max(1, Math.min(line.stock, MAX_QUANTITY))} onChange={onQuantity} />
          <Pressable accessibilityRole="button" accessibilityLabel={`${strings.cart.remove} ${line.product.name}`} onPress={onRemove} hitSlop={Spacing.two}>
            <ThemedText type="small" themeColor="danger">{strings.cart.remove}</ThemedText>
          </Pressable>
        </View>
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  notice: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
  line: { flexDirection: 'row', gap: Spacing.three, borderRadius: Spacing.three, padding: Spacing.two },
  image: { width: 72, height: 72, borderRadius: Spacing.two },
  noImage: { backgroundColor: '#d9ded9' },
  flex: { flex: 1, gap: Spacing.half },
  actions: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: Spacing.one },
  total: { flexDirection: 'row', justifyContent: 'space-between' },
});
