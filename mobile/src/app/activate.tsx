import { useMutation } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { LoginResult } from '@/api/types';
import { deviceName, useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/**
 * No open signup: hikers who booked a hike activate an account with their phone and a booking reference
 * (POST /auth/activate, the website's two steps in one). Logs the app in.
 */
export default function ActivateScreen() {
  const { signIn } = useAuth();
  const [form, setForm] = useState({ phone: '', reference: '', password: '', password_confirmation: '' });
  const set = (field: keyof typeof form) => (value: string) => setForm((current) => ({ ...current, [field]: value }));

  const activate = useMutation({
    mutationFn: () => apiFetch<LoginResult>('/auth/activate', { method: 'POST', body: { ...form, device_name: deviceName() } }),
    onSuccess: async (result) => {
      await signIn(result);
      router.back();
    },
  });
  const errors = fieldErrors(activate.error);

  return (
    <ScreenScroll>
      <ThemedText themeColor="textSecondary">{strings.activate.intro}</ThemedText>
      <FormField label={strings.login.phone} placeholder={strings.login.phonePlaceholder} value={form.phone} onChangeText={set('phone')} keyboardType="phone-pad" autoComplete="tel" error={errors.phone} />
      <FormField label={strings.activate.reference} placeholder={strings.activate.referencePlaceholder} value={form.reference} onChangeText={set('reference')} autoCapitalize="characters" autoCorrect={false} error={errors.reference} />
      <FormField label={strings.activate.password} value={form.password} onChangeText={set('password')} secureTextEntry autoComplete="new-password" textContentType="newPassword" error={errors.password} />
      <FormField label={strings.activate.passwordConfirmation} value={form.password_confirmation} onChangeText={set('password_confirmation')} secureTextEntry autoComplete="new-password" textContentType="newPassword" />
      <FormError message={formMessage(activate.error)} />
      <Button title={strings.activate.submit} onPress={() => activate.mutate()} loading={activate.isPending} />
    </ScreenScroll>
  );
}
