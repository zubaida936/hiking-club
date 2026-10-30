import { Pressable, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/** − 2 + : a quantity between min and max. */
export function QuantityStepper({ value, min = 1, max, onChange }: { value: number; min?: number; max: number; onChange: (value: number) => void }) {
  const theme = useTheme();

  const step = (label: string, symbol: string, next: number, enabled: boolean) => (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled: !enabled }}
      disabled={!enabled}
      onPress={() => onChange(next)}
      style={[styles.button, { borderColor: theme.border }, !enabled && styles.disabled]}>
      <ThemedText type="smallBold">{symbol}</ThemedText>
    </Pressable>
  );

  return (
    <View style={styles.row} accessibilityLabel={`${strings.shop.quantity}: ${value}`}>
      {step(strings.shop.fewer, '−', value - 1, value > min)}
      <ThemedText type="smallBold" style={styles.value}>{value}</ThemedText>
      {step(strings.shop.more, '+', value + 1, value < max)}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: Spacing.two },
  button: { width: 36, height: 36, borderRadius: 18, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
  disabled: { opacity: 0.35 },
  value: { minWidth: 24, textAlign: 'center' },
});
