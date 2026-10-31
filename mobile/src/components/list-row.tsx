import { Link, type Href } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

/** One tappable line in a menu (More, Account) that opens a screen. At least 48 dp high, like Android asks. */
export function ListRow({ title, href }: { title: string; href: Href }) {
  return (
    <Link href={href} asChild>
      <Pressable accessibilityRole="link" accessibilityLabel={title} style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
        <ThemedText style={styles.title}>{title}</ThemedText>
        <ThemedText themeColor="textSecondary" importantForAccessibility="no" accessibilityElementsHidden>
          ›
        </ThemedText>
      </Pressable>
    </Link>
  );
}

/** A titled group of rows on one rounded panel. */
export function ListSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.section}>
      <ThemedText type="smallBold" themeColor="textSecondary" accessibilityRole="header">
        {title}
      </ThemedText>
      <ThemedView type="backgroundElement" style={styles.panel}>
        {children}
      </ThemedView>
    </View>
  );
}

const styles = StyleSheet.create({
  section: { gap: Spacing.two },
  panel: { borderRadius: Spacing.four, overflow: 'hidden' },
  row: { minHeight: 48, flexDirection: 'row', alignItems: 'center', gap: Spacing.three, paddingHorizontal: Spacing.three, paddingVertical: Spacing.two },
  pressed: { opacity: 0.6 },
  title: { flex: 1 },
});
