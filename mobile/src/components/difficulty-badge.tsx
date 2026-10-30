import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import type { Event } from '@/api/types';
import { LevelsList, useDifficultyLevels } from '@/components/difficulty-levels';
import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useLevelColors } from '@/hooks/use-theme';
import { strings } from '@/strings';

type Props = {
  difficulty: Event['difficulty'];
  score: number;
  /** The text on the level in the sheet ("This hike", "Your route"). */
  marker?: string;
};

/**
 * A hike's level and effort score on the level's colour. Tapping it opens a sheet explaining every level.
 * Level and score are the API's; only the colour is chosen here.
 */
export function DifficultyBadge({ difficulty, score, marker = strings.difficulty.thisHike }: Props) {
  const colors = useLevelColors(difficulty.level);
  const [open, setOpen] = useState(false);

  return (
    <>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={strings.difficulty.badgeA11y(difficulty.label, score)}
        accessibilityHint={strings.difficulty.badgeHint}
        onPress={() => setOpen(true)}
        hitSlop={Spacing.one}
        style={({ pressed }) => [styles.badge, { backgroundColor: colors.background }, pressed && styles.pressed]}>
        <ThemedText type="smallBold" style={{ color: colors.text }}>
          {strings.difficulty.badge(difficulty.label, score)} ⓘ
        </ThemedText>
      </Pressable>
      {open ? <LevelsSheet difficulty={difficulty} score={score} marker={marker} onClose={() => setOpen(false)} /> : null}
    </>
  );
}

function LevelsSheet({ difficulty, score, marker, onClose }: Required<Props> & { onClose: () => void }) {
  const { data, error, isPending, refetch } = useDifficultyLevels();
  const openCalculator = () => {
    onClose();
    router.push('/difficulty-calculator');
  };

  return (
    <Modal visible transparent animationType="slide" onRequestClose={onClose}>
      <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel={strings.difficulty.close} />
      <ThemedView style={styles.sheet}>
        <SafeAreaView edges={['bottom']} style={styles.sheetInner}>
          <ScrollView contentContainerStyle={styles.content}>
            <ThemedText type="subtitle">{strings.difficulty.sheetTitle}</ThemedText>
            <ThemedText type="smallBold">{strings.difficulty.sheetScore(score, difficulty.label)}</ThemedText>
            <ThemedText type="small">{difficulty.description}</ThemedText>
            {isPending ? (
              <ActivityIndicator />
            ) : error ? (
              <View style={styles.content}>
                <ThemedText>{error.message}</ThemedText>
                <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
              </View>
            ) : (
              <LevelsList levels={data.levels} highlight={{ level: difficulty.level, label: marker }} />
            )}
            <Button title={strings.difficulty.openCalculator} onPress={openCalculator} variant="secondary" />
            <Button title={strings.difficulty.close} onPress={onClose} />
          </ScrollView>
        </SafeAreaView>
      </ThemedView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  badge: { alignSelf: 'flex-start', borderRadius: 999, paddingHorizontal: Spacing.three, paddingVertical: Spacing.one },
  pressed: { opacity: 0.7 },
  backdrop: { flex: 1, backgroundColor: 'rgba(0, 0, 0, 0.4)' },
  sheet: { maxHeight: '85%', borderTopLeftRadius: Spacing.four, borderTopRightRadius: Spacing.four },
  sheetInner: { flexShrink: 1 },
  content: { padding: Spacing.three, gap: Spacing.three },
});
