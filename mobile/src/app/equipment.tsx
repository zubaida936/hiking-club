import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { Equipment } from '@/api/types';
import { Chip } from '@/components/chip';
import { Button, CheckboxField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { ErrorState, LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { usePacked } from '@/equipment/use-packed';
import { strings } from '@/strings';

/**
 * The equipment guide (GET /equipment, from config/equipment.php like the website): a checklist per adventure and
 * hike level, with ticks kept on the phone and links to the matching shop products.
 */
export default function EquipmentScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['equipment'],
    queryFn: () => apiFetch<{ data: Equipment }>('/equipment').then((answer) => answer.data),
  });
  const [adventureKey, setAdventureKey] = useState<string | null>(null);
  const [level, setLevel] = useState(1);
  const { packed, toggle, clear } = usePacked();

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  const adventure = data.adventures.find((item) => item.key === adventureKey) ?? data.adventures[0];
  if (!adventure) return null;

  // The API marks from which level an item is needed; the list shows what the chosen level needs, like the website.
  const items = adventure.items.filter((item) => item.from_level === null || item.from_level <= level);
  const ticked = packed[adventure.key] ?? [];
  const done = items.filter((item) => ticked.includes(item.name)).length;

  return (
    <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {data.adventures.map((item) => (
          <Chip key={item.key} label={`${item.icon} ${item.label}`} accessibilityLabel={item.label} selected={item.key === adventure.key} onPress={() => setAdventureKey(item.key)} />
        ))}
      </ScrollView>
      <ThemedText>{adventure.intro}</ThemedText>

      <ThemedText type="smallBold">{strings.equipment.level}</ThemedText>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {data.levels.map((item) => (
          <Chip key={item.level} label={item.label} selected={item.level === level} onPress={() => setLevel(item.level)} />
        ))}
      </ScrollView>

      <View style={styles.progress}>
        <ThemedText type="subtitle">{strings.equipment.packed(done, items.length)}</ThemedText>
        {ticked.length > 0 ? <Button title={strings.equipment.clear} onPress={() => clear(adventure.key)} variant="secondary" /> : null}
      </View>

      {items.map((item) => (
        <ThemedView key={item.name} type="backgroundElement" style={styles.card}>
          <CheckboxField label={item.name} checked={ticked.includes(item.name)} onChange={() => toggle(adventure.key, item.name)} />
          <ThemedText type="small" themeColor="textSecondary">{item.tip}</ThemedText>
          {item.from_level !== null ? <ThemedText type="small" themeColor="accent">{strings.equipment.fromLevel(item.from_level)}</ThemedText> : null}
          {item.shop_category ? (
            <ThemedText
              type="linkPrimary"
              accessibilityRole="link"
              accessibilityLabel={`${strings.equipment.inShop}: ${item.name}`}
              onPress={() => router.navigate({ pathname: '/shop', params: { category: item.shop_category } })}>
              {strings.equipment.inShop}
            </ThemedText>
          ) : null}
        </ThemedView>
      ))}
      <ThemedText type="small" themeColor="textSecondary" style={styles.text}>{strings.equipment.remembered}</ThemedText>
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  chips: { gap: Spacing.two, paddingVertical: Spacing.half },
  progress: { gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  text: { textAlign: 'center' },
});
