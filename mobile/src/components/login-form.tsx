import { useMutation } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { useState } from 'react';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { LoginResult } from '@/api/types';
import { deviceName, useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/** Phone + password login (POST /auth/login), with the website's rules and messages. */
export function LoginForm() {
  const { signIn } = useAuth();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');

  const login = useMutation({
    mutationFn: () => apiFetch<LoginResult>('/auth/login', { method: 'POST', body: { phone, password, device_name: deviceName() } }),
    onSuccess: signIn,
  });
  const errors = fieldErrors(login.error);

  return (
    <>
      <ThemedText type="title">{strings.login.title}</ThemedText>
      <ThemedText themeColor="textSecondary">{strings.login.intro}</ThemedText>

      <FormField
        label={strings.login.phone}
        placeholder={strings.login.phonePlaceholder}
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
        autoComplete="tel"
        textContentType="telephoneNumber"
        error={errors.phone}
      />
      <FormField
        label={strings.login.password}
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        autoComplete="current-password"
        textContentType="password"
        error={errors.password}
        onSubmitEditing={() => login.mutate()}
      />
      <FormError message={formMessage(login.error)} />
      <Button title={strings.login.submit} onPress={() => login.mutate()} loading={login.isPending} />

      <Link href="/forgot-password">
        <ThemedText type="linkPrimary">{strings.login.forgot}</ThemedText>
      </Link>
      <Link href="/activate">
        <ThemedText type="linkPrimary">{strings.login.noAccount}</ThemedText>
      </Link>
    </>
  );
}
