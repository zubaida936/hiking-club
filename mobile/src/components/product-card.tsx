import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import type { Product } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** The price, with the regular price struck through while on sale. */
export function Price({ product }: { product: Product }) {
  return (
    <View style={styles.price}>
      <ThemedText type="smallBold">{product.price.formatted}</ThemedText>
      {product.regular_price ? (
        <ThemedText type="small" themeColor="textSecondary" style={styles.struck}>
          {product.regular_price.formatted}
        </ThemedText>
      ) : null}
    </View>
  );
}

/** A product in the shop list; opens its page. */
export function ProductCard({ product }: { product: Product }) {
  const stock = product.stock.sold_out ? strings.shop.soldOut : product.stock.low ? strings.shop.lowStock(product.stock.total) : null;

  return (
    <Link href={`/shop/${product.slug}`} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={product.name} style={styles.flex}>
        <ThemedView type="backgroundElement" style={styles.card}>
          {product.images[0] ? <Image source={product.images[0]} style={styles.image} contentFit="cover" /> : <View style={[styles.image, styles.noImage]} />}
          <View style={styles.body}>
            <ThemedText type="small" themeColor="textSecondary">{product.category.name}</ThemedText>
            <ThemedText type="smallBold">{product.name}</ThemedText>
            <Price product={product} />
            {stock ? <ThemedText type="small" themeColor={product.stock.sold_out ? 'danger' : 'textSecondary'}>{stock}</ThemedText> : null}
          </View>
        </ThemedView>
      </Pressable>
    </Link>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  card: { borderRadius: Spacing.three, overflow: 'hidden', flex: 1 },
  image: { width: '100%', aspectRatio: 1 },
  noImage: { backgroundColor: '#d9ded9' },
  body: { padding: Spacing.two, gap: Spacing.half },
  price: { flexDirection: 'row', alignItems: 'baseline', gap: Spacing.two },
  struck: { textDecorationLine: 'line-through' },
});
