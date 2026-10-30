import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { apiFetch } from '@/api/client';
import type { Category, Product } from '@/api/types';
import { Chip } from '@/components/chip';
import { ProductCard } from '@/components/product-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useCart } from '@/shop/cart';
import { strings } from '@/strings';

/** The shop (GET /shop/products), by category, with the cart button. `?category=slug` picks a category (equipment guide). */
export default function ShopScreen() {
  const { count } = useCart();
  const params = useLocalSearchParams<{ category?: string }>();
  const [category, setCategory] = useState<string | null>(params.category ?? null);

  // The tab stays mounted: follow a new category link while it's open in the background (React's "adjust state
  // when a prop changes" pattern, without an effect).
  const [linkedCategory, setLinkedCategory] = useState(params.category);
  if (params.category !== linkedCategory) {
    setLinkedCategory(params.category);
    if (params.category) setCategory(params.category);
  }

  const { data: categories } = useQuery({
    queryKey: ['shop', 'categories'],
    queryFn: () => apiFetch<{ data: Category[] }>('/shop/categories').then((answer) => answer.data),
  });

  const { data: products, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['shop', 'products', category],
    queryFn: () => apiFetch<{ data: Product[] }>('/shop/products', { query: { category: category ?? undefined } }).then((answer) => answer.data),
  });

  const header = (
    <View style={styles.header}>
      <View style={styles.titleRow}>
        <ThemedText type="title">{strings.shop.title}</ThemedText>
        <Pressable accessibilityRole="button" accessibilityLabel={strings.shop.cart(count)} onPress={() => router.push('/cart')} hitSlop={Spacing.two}>
          <ThemedText type="linkPrimary">{strings.shop.cart(count)}</ThemedText>
        </Pressable>
      </View>
      {categories && categories.length > 0 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
          <Chip label={strings.shop.allCategories} selected={category === null} onPress={() => setCategory(null)} />
          {categories.map((item) => (
            <Chip key={item.slug} label={item.name} selected={category === item.slug} onPress={() => setCategory(category === item.slug ? null : item.slug)} />
          ))}
        </ScrollView>
      ) : null}
    </View>
  );

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <FlatList
          data={products ?? []}
          keyExtractor={(product) => String(product.id)}
          numColumns={2}
          columnWrapperStyle={styles.row}
          renderItem={({ item }) => <ProductCard product={item} />}
          ListHeaderComponent={header}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            isPending ? (
              <ActivityIndicator style={styles.state} />
            ) : error ? (
              <View style={styles.state}>
                <ThemedText style={styles.center}>{error.message}</ThemedText>
                <Pressable accessibilityRole="button" onPress={() => void refetch()}>
                  <ThemedText type="linkPrimary">{strings.errors.retry}</ThemedText>
                </Pressable>
              </View>
            ) : (
              <ThemedText style={styles.center}>{strings.shop.empty}</ThemedText>
            )
          }
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} />}
        />
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, flexDirection: 'row', justifyContent: 'center' },
  safeArea: { flex: 1, maxWidth: MaxContentWidth, paddingHorizontal: Spacing.three },
  header: { gap: Spacing.two, marginTop: Spacing.three, marginBottom: Spacing.one },
  titleRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  chips: { gap: Spacing.two, paddingVertical: Spacing.half },
  list: { gap: Spacing.three, paddingBottom: BottomTabInset + Spacing.three },
  row: { gap: Spacing.three },
  state: { alignItems: 'center', justifyContent: 'center', gap: Spacing.three, paddingVertical: Spacing.five },
  center: { textAlign: 'center' },
});
