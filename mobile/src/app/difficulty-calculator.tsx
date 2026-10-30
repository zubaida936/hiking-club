import { useMutation, useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { DifficultyLevels, DifficultyResult } from '@/api/types';
import { EventCard } from '@/components/event-card';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { strings } from '@/strings';

/** Empty fields aren't sent; a decimal comma (some Android keyboards) becomes a point. */
const number = (value: string) => (value.trim() === '' ? undefined : value.trim().replace(',', '.'));

/**
 * The difficulty calculator (GET /difficulty/calculate, GET /difficulty/levels). The score, level and points always
 * come from the API, with the same formula the website and the CMS use; the app never computes them.
 */
export default function DifficultyCalculatorScreen() {
  const [km, setKm] = useState('');
  const [gain, setGain] = useState('');
  const [loss, setLoss] = useState('');

  const calculate = useMutation({
    mutationFn: () =>
      apiFetch<{ data: DifficultyResult }>('/difficulty/calculate', { query: { km: number(km), gain: number(gain), loss: number(loss) } }).then(
        (answer) => answer.data,
      ),
  });
  const errors = fieldErrors(calculate.error);
  const result = calculate.data;

  return (
    <ScreenScroll>
      <ThemedText>{strings.difficulty.intro}</ThemedText>

      <FormField label={strings.difficulty.km} value={km} onChangeText={setKm} keyboardType="decimal-pad" placeholder={strings.difficulty.kmPlaceholder} error={errors.km} />
      <View style={styles.hinted}>
        <FormField label={strings.difficulty.gain} value={gain} onChangeText={setGain} keyboardType="number-pad" placeholder={strings.difficulty.gainPlaceholder} error={errors.gain} />
        <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.gainHint}</ThemedText>
      </View>
      <View style={styles.hinted}>
        <FormField label={strings.difficulty.loss} value={loss} onChangeText={setLoss} keyboardType="number-pad" placeholder={strings.difficulty.lossPlaceholder} error={errors.loss} />
        <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.lossHint}</ThemedText>
      </View>
      <FormError message={formMessage(calculate.error)} />
      <Button title={strings.difficulty.calculate} onPress={() => calculate.mutate()} loading={calculate.isPending} />

      {result ? <Result result={result} /> : null}

      <HowItWorks level={result?.difficulty.level ?? null} />
    </ScreenScroll>
  );
}

function Result({ result }: { result: DifficultyResult }) {
  const { difficulty } = result;

  return (
    <View style={styles.section} accessibilityLiveRegion="polite">
      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="title">{result.effort_score}</ThemedText>
        <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.score}</ThemedText>
        <ThemedText type="subtitle">{difficulty.label}</ThemedText>
        <ThemedText>{difficulty.description}</ThemedText>
        <ThemedText type="smallBold">{strings.difficulty.points(result.points)}</ThemedText>
      </ThemedView>

      <ThemedText type="subtitle">{strings.difficulty.upcoming(difficulty.label)}</ThemedText>
      {result.upcoming_hikes.length === 0 ? (
        <ThemedText themeColor="textSecondary">{strings.difficulty.noUpcoming}</ThemedText>
      ) : (
        result.upcoming_hikes.map((event) => <EventCard key={event.slug} event={event} />)
      )}
    </View>
  );
}

/** The formula and the 4 levels with their score ranges, from the API; the route's level is marked once calculated. */
function HowItWorks({ level }: { level: number | null }) {
  const theme = useTheme();
  const { data, error, isPending, refetch } = useQuery({
    queryKey: ['difficulty', 'levels'],
    queryFn: () => apiFetch<{ data: DifficultyLevels }>('/difficulty/levels').then((answer) => answer.data),
  });

  if (isPending) return <ActivityIndicator />;

  if (error) {
    return (
      <View style={styles.section}>
        <ThemedText>{error.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
      </View>
    );
  }

  const { formula, levels } = data;
  const gainPerKm = String(formula.gain_per_km);
  const lossPerKm = String(formula.loss_per_km);

  return (
    <View style={styles.section}>
      <ThemedText type="subtitle">{strings.difficulty.howItWorks}</ThemedText>
      <ThemedText>{strings.difficulty.howItWorksText}</ThemedText>
      <ThemedView type="backgroundElement" style={styles.card}>
        <ThemedText type="smallBold">{strings.difficulty.formula(String(formula.multiplier), gainPerKm, lossPerKm)}</ThemedText>
      </ThemedView>
      <ThemedText>{strings.difficulty.formulaText(gainPerKm, lossPerKm)}</ThemedText>

      <ThemedText type="subtitle">{strings.difficulty.levels}</ThemedText>
      {levels.map((item, index) => {
        const previous = levels[index - 1];
        const min = previous?.max_score != null ? previous.max_score + 1 : 0;
        const current = item.level === level;

        return (
          <ThemedView key={item.level} type="backgroundElement" style={[styles.card, current && { borderColor: theme.accent, borderWidth: 2 }]}>
            <View style={styles.row}>
              <ThemedText type="smallBold">{item.label}</ThemedText>
              <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.range(min, item.max_score)}</ThemedText>
            </View>
            {current ? <ThemedText type="smallBold" themeColor="accent">{strings.difficulty.yourRoute}</ThemedText> : null}
            <ThemedText type="small">{item.description}</ThemedText>
            <ThemedText type="small" themeColor="textSecondary">{strings.difficulty.levelPoints(item.points)}</ThemedText>
          </ThemedView>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  hinted: { gap: Spacing.one },
  section: { gap: Spacing.three },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  row: { flexDirection: 'row', justifyContent: 'space-between', gap: Spacing.two, flexWrap: 'wrap' },
});
