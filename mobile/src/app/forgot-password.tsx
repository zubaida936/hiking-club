import { useMutation } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { LoginResult } from '@/api/types';
import { deviceName, useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/**
 * Forgot password, as on the website: send a code (POST /auth/password/forgot, same answer for every number),
 * then the code and a new password (POST /auth/password/reset), which logs this phone in and the others out.
 */
export default function ForgotPasswordScreen() {
  const { signIn } = useAuth();
  const [phone, setPhone] = useState('');
  const [codeSent, setCodeSent] = useState<string | null>(null);
  const [form, setForm] = useState({ code: '', password: '', password_confirmation: '' });
  const set = (field: keyof typeof form) => (value: string) => setForm((current) => ({ ...current, [field]: value }));

  const send = useMutation({
    mutationFn: () => apiFetch<{ message: string }>('/auth/password/forgot', { method: 'POST', body: { phone } }),
    onSuccess: (answer) => setCodeSent(answer.message),
  });

  const reset = useMutation({
    mutationFn: () => apiFetch<LoginResult>('/auth/password/reset', { method: 'POST', body: { phone, ...form, device_name: deviceName() } }),
    onSuccess: async (result) => {
      await signIn(result);
      router.back();
    },
  });

  if (codeSent === null) {
    return (
      <ScreenScroll>
        <ThemedText themeColor="textSecondary">{strings.forgot.intro}</ThemedText>
        <FormField label={strings.login.phone} placeholder={strings.login.phonePlaceholder} value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" error={fieldErrors(send.error).phone} />
        <FormError message={formMessage(send.error)} />
        <Button title={strings.forgot.send} onPress={() => send.mutate()} loading={send.isPending} />
      </ScreenScroll>
    );
  }

  const errors = fieldErrors(reset.error);

  return (
    <ScreenScroll>
      <ThemedText>{codeSent}</ThemedText>
      <ThemedText themeColor="textSecondary">{strings.forgot.codeIntro}</ThemedText>
      <FormField label={strings.forgot.code} value={form.code} onChangeText={set('code')} keyboardType="number-pad" maxLength={6} autoComplete="one-time-code" textContentType="oneTimeCode" error={errors.code} />
      <FormField label={strings.forgot.password} value={form.password} onChangeText={set('password')} secureTextEntry autoComplete="new-password" textContentType="newPassword" error={errors.password} />
      <FormField label={strings.forgot.passwordConfirmation} value={form.password_confirmation} onChangeText={set('password_confirmation')} secureTextEntry autoComplete="new-password" textContentType="newPassword" />
      <FormError message={formMessage(reset.error)} />
      <Button title={strings.forgot.submit} onPress={() => reset.mutate()} loading={reset.isPending} />
      <Pressable accessibilityRole="button" onPress={() => { setCodeSent(null); reset.reset(); }}>
        <ThemedText type="linkPrimary">{strings.forgot.otherNumber}</ThemedText>
      </Pressable>
    </ScreenScroll>
  );
}
