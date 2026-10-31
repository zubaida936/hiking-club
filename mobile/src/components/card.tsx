import { StyleSheet } from 'react-native';

import { ThemedView, type ThemedViewProps } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

type Props = ThemedViewProps & {
  /** No inner padding: for a card that starts with an edge-to-edge picture (put the text in a padded View). */
  flush?: boolean;
};

/** A rounded panel on the screen background: hikes, products, bookings, list rows. */
export function Card({ flush = false, style, ...rest }: Props) {
  return <ThemedView type="backgroundElement" style={[styles.card, !flush && styles.padded, style]} {...rest} />;
}

const styles = StyleSheet.create({
  card: { borderRadius: Spacing.four, overflow: 'hidden' },
  padded: { padding: Spacing.three, gap: Spacing.one },
});
