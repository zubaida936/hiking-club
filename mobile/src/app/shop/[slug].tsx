import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, useWindowDimensions, View } from 'react-native';

import { ApiError, apiFetch } from '@/api/client';
import type { Product } from '@/api/types';
import { Button, ChoiceField } from '@/components/form';
import { Price } from '@/components/product-card';
import { QuantityStepper } from '@/components/quantity-stepper';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { paragraphs } from '@/format';
import { MAX_QUANTITY, useCart } from '@/shop/cart';
import { strings } from '@/strings';

/** One product (GET /shop/products/{slug}): photos, price, sizes, description, add to cart. */
export default function ProductScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { data: product, error, isPending, refetch } = useQuery({
    queryKey: ['shop', 'product', slug],
    queryFn: () => apiFetch<{ data: Product }>(`/shop/products/${encodeURIComponent(slug)}`).then((answer) => answer.data),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    const notFound = error instanceof ApiError && error.status === 404;
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{notFound ? strings.shop.notFound : error.message}</ThemedText>
        {!notFound ? <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" /> : null}
      </ThemedView>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: product.name }} />
      <ProductView product={product} />
    </>
  );
}

function ProductView({ product }: { product: Product }) {
  const { add } = useCart();
  const { width } = useWindowDimensions();
  const inStock = product.variants.filter((variant) => variant.stock > 0);
  // One size ("One size") needs no choice; otherwise nothing is chosen until the hiker picks a size.
  const [variantId, setVariantId] = useState<number | null>(product.variants.length === 1 && inStock.length === 1 ? inStock[0].id : null);
  const [quantity, setQuantity] = useState(1);
  const [sizeMissing, setSizeMissing] = useState(false);
  const chosen = product.variants.find((variant) => variant.id === variantId);
  const max = Math.min(chosen?.stock ?? 1, MAX_QUANTITY);

  const addToCart = () => {
    if (!chosen) {
      setSizeMissing(true);
      return;
    }
    add(chosen.id, quantity);
    Alert.alert(strings.shop.added, `${product.name}${product.variants.length > 1 ? ` (${chosen.name})` : ''} × ${quantity}`, [
      { text: strings.shop.keepShopping, style: 'cancel' },
      { text: strings.shop.goToCart, onPress: () => router.push('/cart') },
    ]);
  };

  return (
    <ScreenScroll>
      {product.images.length > 0 ? (
        <ScrollView horizontal pagingEnabled showsHorizontalScrollIndicator={false}>
          {product.images.map((url) => (
            <Image key={url} source={url} style={[styles.photo, { width: Math.min(width, 800) - Spacing.three * 2 }]} contentFit="cover" />
          ))}
        </ScrollView>
      ) : null}

      <ThemedText type="small" themeColor="textSecondary">{product.category.name}</ThemedText>
      <ThemedText type="title">{product.name}</ThemedText>
      <Price product={product} />
      {product.tags.length > 0 ? <ThemedText type="small" themeColor="textSecondary">{product.tags.join(' · ')}</ThemedText> : null}

      {product.stock.sold_out ? (
        <ThemedText type="smallBold" themeColor="danger">{strings.shop.soldOut}</ThemedText>
      ) : (
        <>
          {product.variants.length > 1 ? (
            <ChoiceField
              label={strings.shop.size}
              choices={inStock.map((variant) => ({ value: String(variant.id), label: variant.name, hint: variant.stock <= 5 ? strings.shop.sizeLeft(variant.stock) : undefined }))}
              value={variantId === null ? null : String(variantId)}
              onChange={(value) => {
                setVariantId(Number(value));
                setQuantity(1);
                setSizeMissing(false);
              }}
              error={sizeMissing ? [strings.shop.chooseSize] : undefined}
            />
          ) : null}
          {product.variants.length > 1 && inStock.length < product.variants.length ? (
            <ThemedText type="small" themeColor="textSecondary">
              {strings.shop.soldOut}: {product.variants.filter((variant) => variant.stock === 0).map((variant) => variant.name).join(', ')}
            </ThemedText>
          ) : null}
          <View style={styles.row}>
            <ThemedText type="smallBold">{strings.shop.quantity}</ThemedText>
            <QuantityStepper value={quantity} max={max} onChange={setQuantity} />
          </View>
          <Button title={strings.shop.addToCart} onPress={addToCart} />
        </>
      )}

      {paragraphs(product.description).map((paragraph) => (
        <ThemedText key={paragraph}>{paragraph}</ThemedText>
      ))}
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  photo: { aspectRatio: 1, borderRadius: Spacing.four, marginRight: Spacing.two },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
});
