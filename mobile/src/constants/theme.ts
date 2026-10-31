/**
 * The app's design tokens. Every colour a screen uses comes from here (through `useTheme()`), so the branding
 * decision is a change in this file only.
 */

import { Platform } from 'react-native';

/**
 * The website's brand tokens (`resources/css/app.css` → `@theme`). PLACEHOLDERS like the website's until the
 * branding decision (ClickUp "Decide tech stack, hosting & branding"): change both together.
 */
export const Brand = {
  50: '#eef7f1',
  100: '#d5ebdc',
  500: '#2f7d52',
  600: '#24653f',
  700: '#1c4f32',
  900: '#0f2a1b',
  /** The website's orange accent (accent-500). */
  highlight: '#ee8f12',
} as const;

export const Colors = {
  light: {
    text: '#0f172a',
    background: '#ffffff',
    backgroundElement: '#f1f5f2',
    backgroundSelected: '#e2e8e4',
    textSecondary: '#5b6470',
    /** Buttons, links, selected states. */
    accent: Brand[600],
    /** Text and icons on an accent background. */
    onAccent: '#ffffff',
    danger: '#c0352b',
    border: '#d0d6d2',
    /** The header and tab bar. */
    bar: '#ffffff',
  },
  dark: {
    text: '#f1f5f2',
    background: '#0b0f0d',
    backgroundElement: '#1a201d',
    backgroundSelected: '#27302b',
    textSecondary: '#a9b2ad',
    accent: '#5cc489',
    onAccent: Brand[900],
    danger: '#ff6b5e',
    border: '#353d39',
    bar: '#111714',
  },
} as const;

export type ThemeColor = keyof typeof Colors.light & keyof typeof Colors.dark;

export const Fonts = Platform.select({
  ios: {
    sans: 'system-ui',
    mono: 'ui-monospace',
  },
  default: {
    sans: 'normal',
    mono: 'monospace',
  },
});

/**
 * Headings use Jost like the website (`--font-display`); body text stays the system font (Roboto on Android, like the
 * website's `--font-sans`). Loaded in the root layout (`useFonts`); until then the system font is shown.
 */
export const DisplayFonts = {
  semiBold: 'Jost_600SemiBold',
  bold: 'Jost_700Bold',
} as const;

/**
 * Text grows with the phone's font size setting. Headings stop at this multiple so a long title still fits;
 * body text has no limit.
 */
export const HeadingMaxFontScale = 1.6;

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
