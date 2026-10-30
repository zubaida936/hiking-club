import { useMutation, useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { ActivityIndicator, Linking, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { ContactDetails, ContactForm, ContactSent } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/**
 * Contact (GET /contact, POST /contact): the club's details with call / WhatsApp / email / map buttons, the social
 * links, and the website's contact form. The server checks the form (same rules and limits as the website) and
 * emails it to the club.
 */
export default function ContactScreen() {
  const { data, error, isPending, refetch } = useQuery({
    queryKey: ['contact'],
    queryFn: () => apiFetch<{ data: ContactDetails }>('/contact').then((answer) => answer.data),
  });

  return (
    <ScreenScroll>
      <ThemedText>{strings.contact.intro}</ThemedText>
      {isPending ? (
        <ActivityIndicator />
      ) : error ? (
        <View style={styles.block}>
          <ThemedText>{error.message}</ThemedText>
          <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
        </View>
      ) : (
        <Details contact={data} />
      )}
      <MessageForm />
    </ScreenScroll>
  );
}

function Details({ contact }: { contact: ContactDetails }) {
  const open = (url: string) => () => void Linking.openURL(url);

  return (
    <View style={styles.block}>
      <Button title={strings.contact.call(contact.phone)} onPress={open(`tel:${contact.phone.replace(/[^\d+]/g, '')}`)} />
      {contact.whatsapp_url ? <Button title={strings.contact.whatsapp} onPress={open(contact.whatsapp_url)} variant="secondary" /> : null}
      <Button title={strings.contact.email(contact.email)} onPress={open(`mailto:${contact.email}`)} variant="secondary" />
      <ThemedText type="linkPrimary" accessibilityRole="link" onPress={open(contact.maps_url)}>
        {strings.contact.map(contact.address)}
      </ThemedText>
      {contact.social.length > 0 ? (
        <>
          <ThemedText type="smallBold">{strings.contact.follow}</ThemedText>
          <View style={styles.social}>
            {contact.social.map((link) => (
              <ThemedText key={link.network} type="linkPrimary" accessibilityRole="link" onPress={open(link.url)}>
                {link.label}
              </ThemedText>
            ))}
          </View>
        </>
      ) : null}
    </View>
  );
}

/** The contact form; a logged-in member's details are filled in. */
function MessageForm() {
  const { member } = useAuth();
  const [fields, setFields] = useState({ name: member?.name ?? '', email: member?.email ?? '', phone: member?.phone ?? '', message: '' });
  const set = (key: keyof typeof fields) => (value: string) => setFields((current) => ({ ...current, [key]: value }));

  // The member can load after the screen opened (a link at app start): fill in the fields still empty, once.
  const [filledFor, setFilledFor] = useState(member?.id ?? null);
  if (member && member.id !== filledFor) {
    setFilledFor(member.id);
    setFields((current) => ({
      ...current,
      name: current.name || member.name,
      email: current.email || (member.email ?? ''),
      phone: current.phone || (member.phone ?? ''),
    }));
  }

  const send = useMutation({
    mutationFn: () => {
      const body: ContactForm = { name: fields.name, email: fields.email.trim(), phone: fields.phone.trim() || null, message: fields.message };
      return apiFetch<ContactSent>('/contact', { method: 'POST', body });
    },
    onSuccess: () => setFields((current) => ({ ...current, message: '' })),
  });
  const errors = fieldErrors(send.error);

  return (
    <ThemedView type="backgroundElement" style={styles.form}>
      <ThemedText type="subtitle">{strings.contact.form}</ThemedText>
      {send.isSuccess ? (
        <ThemedText accessibilityRole="alert" type="smallBold" themeColor="accent">
          {send.data.message}
        </ThemedText>
      ) : null}
      <FormField label={strings.contact.name} value={fields.name} onChangeText={set('name')} autoComplete="name" maxLength={80} error={errors.name} />
      <FormField
        label={strings.contact.emailField}
        value={fields.email}
        onChangeText={set('email')}
        autoComplete="email"
        keyboardType="email-address"
        autoCapitalize="none"
        error={errors.email}
      />
      <FormField label={strings.contact.phone} value={fields.phone} onChangeText={set('phone')} autoComplete="tel" keyboardType="phone-pad" error={errors.phone} />
      <FormField
        label={strings.contact.message}
        value={fields.message}
        onChangeText={set('message')}
        multiline
        maxLength={3000}
        style={styles.message}
        error={errors.message}
      />
      <FormError message={formMessage(send.error)} />
      <Button title={strings.contact.send} onPress={() => send.mutate()} loading={send.isPending} />
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  block: { gap: Spacing.two },
  social: { flexDirection: 'row', flexWrap: 'wrap', gap: Spacing.three },
  form: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.three },
  message: { minHeight: 120, textAlignVertical: 'top' },
});
