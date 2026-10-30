/**
 * Below are the colors that are used in the app. The colors are defined in the light and dark mode.
 * There are many other ways to style your app. For example, [Nativewind](https://www.nativewind.dev/), [Tamagui](https://tamagui.dev/), [unistyles](https://unistyl.es/), etc.
 */

import { Platform } from 'react-native';

export const Colors = {
  light: {
    text: '#000000',
    background: '#ffffff',
    backgroundElement: '#F0F0F3',
    backgroundSelected: '#E0E1E6',
    textSecondary: '#60646C',
    // Placeholder brand colours until the branding decision.
    accent: '#1f6f43',
    danger: '#c0352b',
    border: '#D0D2D7',
  },
  dark: {
    text: '#ffffff',
    background: '#000000',
    backgroundElement: '#212225',
    backgroundSelected: '#2E3135',
    textSecondary: '#B0B4BA',
    accent: '#4fbf7f',
    danger: '#ff6b5e',
    border: '#3A3D42',
  },
} as const;

export type ThemeColor = keyof typeof Colors.light & keyof typeof Colors.dark;

export const Fonts = Platform.select({
  ios: {
    /** iOS `UIFontDescriptorSystemDesignDefault` */
    sans: 'system-ui',
    /** iOS `UIFontDescriptorSystemDesignSerif` */
    serif: 'ui-serif',
    /** iOS `UIFontDescriptorSystemDesignRounded` */
    rounded: 'ui-rounded',
    /** iOS `UIFontDescriptorSystemDesignMonospaced` */
    mono: 'ui-monospace',
  },
  default: {
    sans: 'normal',
    serif: 'serif',
    rounded: 'normal',
    mono: 'monospace',
  },
});

export const Spacing = {
  half: 2,
  one: 4,
  two: 8,
  three: 16,
  four: 24,
  five: 32,
  six: 64,
} as const;

export const BottomTabInset = Platform.select({ ios: 50, android: 80 }) ?? 0;
export const MaxContentWidth = 800;

/**
 * Badge colours per difficulty level (1–4), like the website's badges (green, blue, amber, red). Only the colour
 * lives here: the level itself always comes from the API.
 */
export const LevelColors = {
  light: {
    1: { background: '#D1FAE5', text: '#065F46' },
    2: { background: '#E0F2FE', text: '#075985' },
    3: { background: '#FEF3C7', text: '#92400E' },
    4: { background: '#FEE2E2', text: '#991B1B' },
  },
  dark: {
    1: { background: '#064E3B', text: '#A7F3D0' },
    2: { background: '#0C4A6E', text: '#BAE6FD' },
    3: { background: '#78350F', text: '#FDE68A' },
    4: { background: '#7F1D1D', text: '#FECACA' },
  },
} as const;

export type Level = keyof typeof LevelColors.light;
