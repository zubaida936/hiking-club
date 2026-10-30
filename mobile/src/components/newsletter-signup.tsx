import { useMutation } from '@tanstack/react-query';
import { useState } from 'react';
import { StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { SubscribeForm, Subscribed } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/**
 * "Next destination alerts" (POST /newsletter), like the website homepage: an email (confirmed by a link sent there)
 * and/or a phone number. The server checks it and answers with the message to show. A push alert can be added with
 * the push notifications task.
 */
export function NewsletterSignup() {
  const { member } = useAuth();
  const [email, setEmail] = useState(member?.email ?? '');
  const [phone, setPhone] = useState(member?.phone ?? '');

  // Home opens before the account has loaded: fill in the fields still empty once the member arrives.
  const [filledFor, setFilledFor] = useState(member?.id ?? null);
  if (member && member.id !== filledFor) {
    setFilledFor(member.id);
    if (!email) setEmail(member.email ?? '');
    if (!phone) setPhone(member.phone ?? '');
  }

  const subscribe = useMutation({
    mutationFn: () => {
      const body: SubscribeForm = { email: email.trim() || null, phone: phone.trim() || null };
      return apiFetch<Subscribed>('/newsletter', { method: 'POST', body });
    },
  });
  const errors = fieldErrors(subscribe.error);

  return (
    <ThemedView type="backgroundElement" style={styles.box}>
      <ThemedText type="small" themeColor="accent">{strings.newsletter.eyebrow}</ThemedText>
      <ThemedText type="subtitle">{strings.newsletter.title}</ThemedText>
      {subscribe.isSuccess ? (
        <ThemedText accessibilityRole="alert" type="smallBold">
          {subscribe.data.message}
        </ThemedText>
      ) : (
        <>
          <ThemedText type="small">{strings.newsletter.intro}</ThemedText>
          <FormField
            label={strings.newsletter.email}
            value={email}
            onChangeText={setEmail}
            placeholder="you@example.com"
            autoComplete="email"
            keyboardType="email-address"
            autoCapitalize="none"
            error={errors.email}
          />
          <FormField label={strings.newsletter.phone} value={phone} onChangeText={setPhone} placeholder="+961 70 123 456" autoComplete="tel" keyboardType="phone-pad" error={errors.phone} />
          <FormError message={formMessage(subscribe.error)} />
          <Button title={strings.newsletter.subscribe} onPress={() => subscribe.mutate()} loading={subscribe.isPending} />
        </>
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  box: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.two },
});
