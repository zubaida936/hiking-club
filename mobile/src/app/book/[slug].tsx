import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { Booking, BookingForm } from '@/api/types';
import { Button, CheckboxField, ChoiceField, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

type Fields = {
  name: string;
  phone: string;
  email: string;
  meeting_point_id: string | null;
  emergency_contact_name: string;
  emergency_contact_phone: string;
  notes: string;
  referrer_phone: string;
  reward: string | null;
  payment_method: string | null;
  level_acknowledged: boolean;
  terms: boolean;
};

/**
 * Book a hike (GET /events/{slug}/booking-form, POST /events/{slug}/bookings). Every rule and message is the
 * website's: the app only shows the choices the API offers and the errors it answers.
 */
export default function BookScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { data: form, error, isPending, refetch } = useQuery({
    queryKey: ['booking-form', slug],
    queryFn: () => apiFetch<{ data: BookingForm }>(`/events/${encodeURIComponent(slug)}/booking-form`).then((answer) => answer.data),
  });

  if (isPending) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  if (error) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{error.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={() => void refetch()} variant="secondary" />
      </ThemedView>
    );
  }

  if (!form.open || form.x_locked) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{form.x_locked ? strings.book.xLocked : strings.book.closed}</ThemedText>
        {form.x_locked ? <Button title={strings.book.xAbout} onPress={() => router.push('/x-events')} /> : null}
      </ThemedView>
    );
  }

  return <BookingFields slug={slug} form={form} />;
}

/** The form itself, starting with the logged-in member's details filled in. */
function BookingFields({ slug, form }: { slug: string; form: BookingForm }) {
  const queryClient = useQueryClient();
  const [fields, setFields] = useState<Fields>(() => ({
    name: form.member?.name ?? '',
    phone: form.member?.phone ?? '',
    email: form.member?.email ?? '',
    meeting_point_id: null,
    emergency_contact_name: form.member?.emergency_contact_name ?? '',
    emergency_contact_phone: form.member?.emergency_contact_phone ?? '',
    notes: '',
    referrer_phone: '',
    reward: null,
    payment_method: null,
    level_acknowledged: false,
    terms: false,
  }));
  const set = <K extends keyof Fields>(key: K) => (value: Fields[K]) => setFields((current) => ({ ...current, [key]: value }));

  const book = useMutation({
    mutationFn: () =>
      apiFetch<{ data: Booking }>(`/events/${encodeURIComponent(slug)}/bookings`, {
        method: 'POST',
        body: {
          ...fields,
          email: fields.email || null,
          notes: fields.notes || null,
          referrer_phone: fields.referrer_phone || null,
          meeting_point_id: fields.meeting_point_id === null ? null : Number(fields.meeting_point_id),
        },
      }),
    onSuccess: async ({ data: booking }) => {
      // Seats changed: the lists and the hike reload when shown again.
      await queryClient.invalidateQueries({ queryKey: ['events'] });
      await queryClient.invalidateQueries({ queryKey: ['my'] });
      router.replace(`/bookings/${booking.token}`);
    },
  });

  const errors = fieldErrors(book.error);
  const reward = form.rewards.find((option) => option.key === fields.reward);
  // A 100% reward makes the hike free: no payment method then (same rule as the website).
  const needsPayment = form.payment_methods.length > 0 && reward?.fee_after !== 'Free';
  const price = reward?.fee_after ?? form.fee.formatted;

  return (
    <ScreenScroll>
      {form.waitlist ? <ThemedText type="smallBold">{strings.book.waitlist}</ThemedText> : null}

      <ThemedText type="subtitle">{strings.book.you}</ThemedText>
      <FormField label={strings.book.name} value={fields.name} onChangeText={set('name')} autoComplete="name" error={errors.name} />
      <FormField label={strings.book.phone} value={fields.phone} onChangeText={set('phone')} keyboardType="phone-pad" autoComplete="tel" placeholder={strings.login.phonePlaceholder} error={errors.phone} />
      <FormField label={strings.book.email} value={fields.email} onChangeText={set('email')} keyboardType="email-address" autoCapitalize="none" autoComplete="email" error={errors.email} />

      {form.meeting_points.length > 0 ? (
        <ChoiceField
          label={strings.book.pickup}
          choices={form.meeting_points.map((point) => ({ value: String(point.id), label: point.name, hint: point.pickup_time }))}
          value={fields.meeting_point_id}
          onChange={set('meeting_point_id')}
          error={errors.meeting_point_id}
        />
      ) : null}

      <ThemedText type="subtitle">{strings.book.emergency}</ThemedText>
      <FormField label={strings.book.emergencyName} value={fields.emergency_contact_name} onChangeText={set('emergency_contact_name')} error={errors.emergency_contact_name} />
      <FormField label={strings.book.emergencyPhone} value={fields.emergency_contact_phone} onChangeText={set('emergency_contact_phone')} keyboardType="phone-pad" error={errors.emergency_contact_phone} />

      <FormField label={strings.book.notes} value={fields.notes} onChangeText={set('notes')} multiline maxLength={500} error={errors.notes} />
      <FormField label={strings.book.referrer} value={fields.referrer_phone} onChangeText={set('referrer_phone')} keyboardType="phone-pad" error={errors.referrer_phone} />

      {form.rewards.length > 0 ? (
        <ChoiceField
          label={strings.book.reward}
          choices={[
            { value: '', label: strings.book.noReward, hint: strings.book.rewardHint(form.fee.formatted) },
            ...form.rewards.map((option) => ({ value: option.key, label: option.label, hint: strings.book.rewardHint(option.fee_after) })),
          ]}
          value={fields.reward ?? ''}
          onChange={(value) => set('reward')(value === '' ? null : value)}
          error={errors.reward}
        />
      ) : null}

      {needsPayment ? (
        <ChoiceField
          label={strings.book.payment}
          choices={form.payment_methods.map((method) => ({ value: method.key, label: method.label }))}
          value={fields.payment_method}
          onChange={set('payment_method')}
          error={errors.payment_method}
        />
      ) : null}

      {form.requires_level_acknowledgement ? (
        <CheckboxField label={strings.book.level(form.level_label)} checked={fields.level_acknowledged} onChange={set('level_acknowledged')} error={errors.level_acknowledged} />
      ) : null}
      <CheckboxField label={form.terms} checked={fields.terms} onChange={set('terms')} error={errors.terms} />

      <ThemedText type="small" themeColor="textSecondary">{strings.book.points(form.points_to_earn)}</ThemedText>
      <FormError message={formMessage(book.error)} />
      <Button title={form.waitlist ? strings.book.submitWaitlist : strings.book.submit(price)} onPress={() => book.mutate()} loading={book.isPending} />
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
