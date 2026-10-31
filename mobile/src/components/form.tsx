import { ActivityIndicator, Pressable, StyleSheet, TextInput, View, type TextInputProps } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

type FieldProps = TextInputProps & {
  label: string;
  /** The API's message(s) for this field (ApiError.errors[field]). */
  error?: string[];
};

/** A labelled text field that shows the API's validation message under it. */
export function FormField({ label, error, style, ...input }: FieldProps) {
  const theme = useTheme();
  const message = error?.[0];

  return (
    <View style={styles.field}>
      <ThemedText type="smallBold">{label}</ThemedText>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={theme.textSecondary}
        style={[styles.input, { color: theme.text, borderColor: message ? theme.danger : theme.border }, style]}
        {...input}
      />
      {message ? (
        <ThemedText type="small" themeColor="danger">
          {message}
        </ThemedText>
      ) : null}
    </View>
  );
}

/** A message for the whole form (e.g. "This code is wrong or has expired."). */
export function FormError({ message }: { message?: string | null }) {
  if (!message) return null;

  return (
    <ThemedText accessibilityRole="alert" themeColor="danger">
      {message}
    </ThemedText>
  );
}

type Choice = { value: string; label: string; hint?: string };

/** Pick one of a few options (pickup point, payment method, reward). */
export function ChoiceField({ label, choices, value, onChange, error }: { label: string; choices: Choice[]; value: string | null; onChange: (value: string) => void; error?: string[] }) {
  const theme = useTheme();

  return (
    <View style={styles.field} accessibilityRole="radiogroup" accessibilityLabel={label}>
      <ThemedText type="smallBold">{label}</ThemedText>
      {choices.map((choice) => {
        const selected = choice.value === value;
        return (
          <Pressable
            key={choice.value}
            accessibilityRole="radio"
            accessibilityLabel={choice.label}
            accessibilityState={{ checked: selected }}
            onPress={() => onChange(choice.value)}
            style={[styles.choice, { borderColor: selected ? theme.accent : theme.border }]}>
            <View style={[styles.dot, { borderColor: selected ? theme.accent : theme.border }]}>
              {selected ? <View style={[styles.dotInner, { backgroundColor: theme.accent }]} /> : null}
            </View>
            <View style={styles.flex}>
              <ThemedText>{choice.label}</ThemedText>
              {choice.hint ? <ThemedText type="small" themeColor="textSecondary">{choice.hint}</ThemedText> : null}
            </View>
          </Pressable>
        );
      })}
      {error?.[0] ? <ThemedText type="small" themeColor="danger">{error[0]}</ThemedText> : null}
    </View>
  );
}

/** A yes/no box (terms, level confirmation). */
export function CheckboxField({ label, checked, onChange, error }: { label: string; checked: boolean; onChange: (checked: boolean) => void; error?: string[] }) {
  const theme = useTheme();

  return (
    <View style={styles.field}>
      <Pressable accessibilityRole="checkbox" accessibilityLabel={label} accessibilityState={{ checked }} onPress={() => onChange(!checked)} style={styles.checkRow}>
        <View style={[styles.box, { borderColor: error?.[0] ? theme.danger : theme.accent, backgroundColor: checked ? theme.accent : 'transparent' }]}>
          {checked ? <ThemedText style={[styles.tick, { color: theme.onAccent }]}>✓</ThemedText> : null}
        </View>
        <ThemedText style={styles.flex}>{label}</ThemedText>
      </Pressable>
      {error?.[0] ? <ThemedText type="small" themeColor="danger">{error[0]}</ThemedText> : null}
    </View>
  );
}

type ButtonProps = {
  title: string;
  onPress: () => void;
  loading?: boolean;
  /** danger: a red, filled button for actions that can't be undone. */
  variant?: 'primary' | 'secondary' | 'danger';
};

export function Button({ title, onPress, loading = false, variant = 'primary' }: ButtonProps) {
  const theme = useTheme();
  const filled = variant !== 'secondary';
  const fill = variant === 'danger' ? theme.danger : theme.accent;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={title}
      accessibilityState={{ busy: loading, disabled: loading }}
      disabled={loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        filled ? { backgroundColor: fill } : { borderColor: theme.accent, borderWidth: 1 },
        (pressed || loading) && styles.dimmed,
      ]}>
      {loading ? (
        <ActivityIndicator color={filled ? theme.onAccent : theme.accent} />
      ) : (
        <ThemedText type="smallBold" style={{ color: filled ? theme.onAccent : theme.accent }}>
          {title}
        </ThemedText>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  field: { gap: Spacing.one },
  input: { borderWidth: 1, borderRadius: Spacing.two, paddingHorizontal: Spacing.three, paddingVertical: Spacing.two, fontSize: 16 },
  button: { borderRadius: 999, paddingVertical: Spacing.three, alignItems: 'center', minHeight: 48, justifyContent: 'center' },
  dimmed: { opacity: 0.7 },
  choice: { flexDirection: 'row', alignItems: 'center', gap: Spacing.three, borderWidth: 1, borderRadius: Spacing.two, padding: Spacing.three },
  dot: { width: 20, height: 20, borderRadius: 10, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  dotInner: { width: 10, height: 10, borderRadius: 5 },
  checkRow: { flexDirection: 'row', alignItems: 'flex-start', gap: Spacing.three },
  box: { width: 22, height: 22, borderRadius: 4, borderWidth: 2, alignItems: 'center', justifyContent: 'center', marginTop: 1 },
  tick: { fontSize: 14, lineHeight: 16 },
  flex: { flex: 1 },
});
