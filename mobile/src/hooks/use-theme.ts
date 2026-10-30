/**
 * Learn more about light and dark modes:
 * https://docs.expo.dev/guides/color-schemes/
 */

import { Colors, LevelColors, type Level } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export function useTheme() {
  const scheme = useColorScheme();
  const theme = scheme === 'dark' ? 'dark' : 'light';

  return Colors[theme];
}

/** The badge colours of a difficulty level, for the current light / dark mode. */
export function useLevelColors(level: Level) {
  const scheme = useColorScheme();

  return LevelColors[scheme === 'dark' ? 'dark' : 'light'][level];
}
