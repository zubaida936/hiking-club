import { useNetInfo } from '@react-native-community/netinfo';
import { StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { BottomTabInset, Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/**
 * Shown over every screen while the phone has no connection: saved hikes and bookings still show, and everything
 * reloads by itself when the signal is back. Taps go through to the screen underneath.
 */
export function OfflineBanner() {
  const theme = useTheme();
  const { isConnected } = useNetInfo();

  // null while NetInfo hasn't checked yet: no banner.
  if (isConnected !== false) return null;

  return (
    <View pointerEvents="none" style={styles.wrapper}>
      <View accessibilityRole="alert" style={[styles.banner, { backgroundColor: theme.text }]}>
        <ThemedText type="smallBold" style={{ color: theme.background }}>
          {strings.errors.offlineBanner}
        </ThemedText>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: { position: 'absolute', left: 0, right: 0, bottom: BottomTabInset + Spacing.three, alignItems: 'center' },
  banner: { borderRadius: 999, paddingHorizontal: Spacing.three, paddingVertical: Spacing.two, opacity: 0.92 },
});
