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

type ButtonProps = {
  title: string;
  onPress: () => void;
  loading?: boolean;
  variant?: 'primary' | 'secondary';
};

export function Button({ title, onPress, loading = false, variant = 'primary' }: ButtonProps) {
  const theme = useTheme();
  const primary = variant === 'primary';

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={title}
      accessibilityState={{ busy: loading, disabled: loading }}
      disabled={loading}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        primary ? { backgroundColor: theme.accent } : { borderColor: theme.accent, borderWidth: 1 },
        (pressed || loading) && styles.dimmed,
      ]}>
      {loading ? (
        <ActivityIndicator color={primary ? '#ffffff' : theme.accent} />
      ) : (
        <ThemedText type="smallBold" style={{ color: primary ? '#ffffff' : theme.accent }}>
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
});
