import type { ReactNode } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/**
 * What a screen shows instead of its content: loading, a failed request, or nothing to show. Full screen by default;
 * `inline` inside a list (its ListEmptyComponent) or under a screen's header.
 */
type Placement = { inline?: boolean };

function StateBox({ inline = false, children }: Placement & { children: ReactNode }) {
  return inline ? <View style={styles.inline}>{children}</View> : <ThemedView style={styles.screen}>{children}</ThemedView>;
}

/** The first answer is still on its way. */
export function LoadingState({ inline }: Placement) {
  const theme = useTheme();

  return (
    <StateBox inline={inline}>
      <ActivityIndicator accessibilityLabel={strings.states.loading} color={theme.accent} size="large" />
    </StateBox>
  );
}

/** A request failed and nothing is saved: the API's user-safe message, and a way to try again. */
export function ErrorState({ message, onRetry, inline }: Placement & { message?: string | null; onRetry: () => void }) {
  return (
    <StateBox inline={inline}>
      <ThemedText accessibilityRole="alert" style={styles.text}>{message ?? strings.errors.generic}</ThemedText>
      <Button title={strings.errors.retry} onPress={onRetry} variant="secondary" />
    </StateBox>
  );
}

/** Loaded, but there's nothing to show (no hikes, no orders); optionally one thing to do about it. */
export function EmptyState({ message, action, inline }: Placement & { message: string; action?: { title: string; onPress: () => void } }) {
  return (
    <StateBox inline={inline}>
      <ThemedText themeColor="textSecondary" style={styles.text}>{message}</ThemedText>
      {action ? <Button title={action.title} onPress={action.onPress} variant="secondary" /> : null}
    </StateBox>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  inline: { alignItems: 'center', justifyContent: 'center', gap: Spacing.three, paddingVertical: Spacing.five, paddingHorizontal: Spacing.three },
  text: { textAlign: 'center' },
});
