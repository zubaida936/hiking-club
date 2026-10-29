import { useMutation } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { Alert } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import { useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/**
 * Delete my account (DELETE /me, required by the App Store and Google Play). The server anonymises the
 * bookings and orders and deletes the login; the rules (password, upcoming bookings) and messages are its own.
 */
export default function DeleteAccountScreen() {
  const { forgetLogin } = useAuth();
  const [password, setPassword] = useState('');

  const remove = useMutation({
    mutationFn: () => apiFetch<void>('/me', { method: 'DELETE', body: { password } }),
    onSuccess: async () => {
      await forgetLogin();
      router.dismissTo('/account');
      Alert.alert(strings.deleteAccount.done);
    },
  });

  const confirm = () =>
    Alert.alert(strings.deleteAccount.confirmTitle, strings.deleteAccount.confirmText, [
      { text: strings.deleteAccount.keep, style: 'cancel' },
      { text: strings.deleteAccount.confirm, style: 'destructive', onPress: () => remove.mutate() },
    ]);

  return (
    <ScreenScroll>
      <ThemedText>{strings.deleteAccount.intro}</ThemedText>
      {strings.deleteAccount.deletes.map((line) => (
        <ThemedText key={line}>• {line}</ThemedText>
      ))}
      <ThemedText type="small" themeColor="textSecondary">{strings.deleteAccount.openBookings}</ThemedText>

      <FormField
        label={strings.deleteAccount.password}
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        autoComplete="current-password"
        error={fieldErrors(remove.error).password}
      />
      <FormError message={formMessage(remove.error)} />
      <Button title={strings.deleteAccount.submit} onPress={confirm} loading={remove.isPending} variant="danger" />
      <Button title={strings.deleteAccount.keep} onPress={() => router.back()} variant="secondary" />
    </ScreenScroll>
  );
}
