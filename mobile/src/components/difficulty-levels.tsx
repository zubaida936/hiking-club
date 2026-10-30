import { useQuery } from '@tanstack/react-query';
import { StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { DifficultyLevels } from '@/api/types';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing, type Level } from '@/constants/theme';
import { useLevelColors, useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/** The 4 levels and the formula (GET /difficulty/levels); loaded once and shared by every screen that shows them. */
export function useDifficultyLevels() {
  return useQuery({
    queryKey: ['difficulty', 'levels'],
    queryFn: () => apiFetch<{ data: DifficultyLevels }>('/difficulty/levels').then((answer) => answer.data),
  });
}

/** A level's name on its colour, like the website's badges. */
export function LevelPill({ level, label }: { level: Level; label: string }) {
  const colors = useLevelColors(level);

  return (
    <View style={[styles.pill, { backgroundColor: colors.background }]}>
      <ThemedText type="smallBold" style={{ color: colors.text }}>
        {label}
      </ThemedText>
    </View>
  );
}

type ListProps = {
  levels: DifficultyLevels['levels'];
  /** The level to mark (the hike's, or the calculated route's), with the text shown on it. */
  highlight?: { level: number; label: string } | null;
};

/** Each level with its score range, description and points, from the API. */
export function LevelsList({ levels, highlight = null }: ListProps) {
  const theme = useTheme();

  return (
    <View style={styles.list}>
      {levels.map((item, index) => {
        const previous = levels[index - 1];
        const min = previous?.max_score != null ? previous.max_score + 1 : 0;
        const current = item.level === highlight?.level;

        return (
          <ThemedView key={item.level} type="backgroundElement" style={[styles.card, current && { borderColor: theme.accent, borderWidth: 2 }]}>
            <View style={styles.row}>
              <LevelPill level={item.level} label={item.label} />
              <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.range(min, item.max_score)}</ThemedText>
            </View>
            {current ? <ThemedText type="smallBold" themeColor="accent">{highlight.label}</ThemedText> : null}
            <ThemedText type="small">{item.description}</ThemedText>
            <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.levelPoints(item.points)}</ThemedText>
          </ThemedView>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  pill: { alignSelf: 'flex-start', borderRadius: 999, paddingHorizontal: Spacing.three, paddingVertical: Spacing.one },
  list: { gap: Spacing.three },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: Spacing.two, flexWrap: 'wrap' },
});
