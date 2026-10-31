import { Platform, StyleSheet, Text, type TextProps } from 'react-native';

import { DisplayFonts, Fonts, HeadingMaxFontScale, ThemeColor } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

export type ThemedTextProps = TextProps & {
  type?: 'default' | 'title' | 'small' | 'smallBold' | 'subtitle' | 'link' | 'linkPrimary' | 'code';
  themeColor?: ThemeColor;
};

const headings: NonNullable<ThemedTextProps['type']>[] = ['title', 'subtitle'];

export function ThemedText({ style, type = 'default', themeColor, ...rest }: ThemedTextProps) {
  const theme = useTheme();
  // Links are in the accent colour unless the screen asks for another one.
  const color = theme[themeColor ?? (type === 'linkPrimary' ? 'accent' : 'text')];

  return (
    <Text
      maxFontSizeMultiplier={headings.includes(type) ? HeadingMaxFontScale : undefined}
      style={[{ color }, styles[type], style]}
      {...rest}
    />
  );
}

const styles = StyleSheet.create({
  small: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: 500,
  },
  smallBold: {
    fontSize: 14,
    lineHeight: 20,
    fontWeight: 700,
  },
  default: {
    fontSize: 16,
    lineHeight: 24,
    fontWeight: 500,
  },
  // Custom fonts carry their weight in the family name (Android ignores fontWeight for them).
  title: {
    fontFamily: DisplayFonts.bold,
    fontSize: 32,
    lineHeight: 40,
  },
  subtitle: {
    fontFamily: DisplayFonts.semiBold,
    fontSize: 24,
    lineHeight: 32,
  },
  link: {
    lineHeight: 30,
    fontSize: 14,
  },
  linkPrimary: {
    lineHeight: 30,
    fontSize: 14,
    fontWeight: 700,
  },
  code: {
    fontFamily: Fonts.mono,
    fontWeight: Platform.select({ android: 700 }) ?? 500,
    fontSize: 12,
  },
});
