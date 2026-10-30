import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, Linking, Pressable, StyleSheet } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { XApplicationForm, XApplicationResult } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
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
  age: string;
  city: string;
  guardian_name: string;
  guardian_phone: string;
  hike_frequency: string | null;
  hardest_route: string;
  hardest_date: string;
  hardest_km: string;
  hardest_elevation_m: string;
  hardest_duration_hours: string;
  longest_km: string;
  max_ascent_m: string;
  pace: string | null;
  steady_pace: string | null;
  main_challenge: string;
  track_link: string;
  experience: string[];
  gear: string[];
  limitations: string;
  consent: boolean;
};

/**
 * Apply for X Events (GET /x-events/application-form, POST /x-events/applications): the website's form, its
 * choices from the API, and every rule and message from the API (apply once, guardian under the age limit).
 */
export default function XApplyScreen() {
  const { status } = useAuth();
  const { data: form, error, isPending, refetch } = useQuery({
    queryKey: ['x-events', 'form', status],
    queryFn: () => apiFetch<{ data: XApplicationForm }>('/x-events/application-form').then((answer) => answer.data),
    enabled: status !== 'loading',
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

  return <ApplicationFields form={form} />;
}

/** Empty text is sent as null (the API's optional fields). */
const orNull = (value: string) => (value.trim() === '' ? null : value.trim());

function ApplicationFields({ form }: { form: XApplicationForm }) {
  const queryClient = useQueryClient();
  const [sent, setSent] = useState<XApplicationResult | null>(null);
  const [fields, setFields] = useState<Fields>(() => ({
    name: form.member?.name ?? '',
    phone: form.member?.phone ?? '',
    email: form.member?.email ?? '',
    age: '',
    city: '',
    guardian_name: '',
    guardian_phone: '',
    hike_frequency: null,
    hardest_route: '',
    hardest_date: '',
    hardest_km: '',
    hardest_elevation_m: '',
    hardest_duration_hours: '',
    longest_km: '',
    max_ascent_m: '',
    pace: null,
    steady_pace: null,
    main_challenge: '',
    track_link: '',
    experience: [],
    gear: [],
    limitations: '',
    consent: false,
  }));
  const set = <K extends keyof Fields>(key: K) => (value: Fields[K]) => setFields((current) => ({ ...current, [key]: value }));
  const toggle = (key: 'experience' | 'gear', option: string) => (checked: boolean) =>
    setFields((current) => ({ ...current, [key]: checked ? [...current[key], option] : current[key].filter((item) => item !== option) }));

  const apply = useMutation({
    mutationFn: () =>
      apiFetch<{ data: XApplicationResult }>('/x-events/applications', {
        method: 'POST',
        body: {
          ...fields,
          email: orNull(fields.email),
          guardian_name: orNull(fields.guardian_name),
          guardian_phone: orNull(fields.guardian_phone),
          hardest_date: orNull(fields.hardest_date),
          track_link: orNull(fields.track_link),
          limitations: orNull(fields.limitations),
          steady_pace: fields.steady_pace === null ? null : fields.steady_pace === '1',
        },
      }),
    onSuccess: async ({ data }) => {
      // The X Events screen shows the new status when it's shown again.
      await queryClient.invalidateQueries({ queryKey: ['x-events'] });
      setSent(data);
    },
  });

  if (sent) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText type="subtitle">{strings.xApply.sentTitle}</ThemedText>
        <ThemedText style={styles.text}>{strings.xApply.sent}</ThemedText>
        <Button title={strings.xApply.back} onPress={() => (router.canGoBack() ? router.back() : router.replace('/x-events'))} />
      </ThemedView>
    );
  }

  const errors = fieldErrors(apply.error);
  // Same as the website: the guardian fields show until an age at or above the limit is entered.
  const age = Number(fields.age);
  const minor = fields.age.trim() === '' || Number.isNaN(age) || age < form.guardian_under_age;
  const options = (list: XApplicationForm['pace']) => list.map((option) => ({ value: option.key, label: option.label }));

  return (
    <ScreenScroll>
      <ThemedText>{strings.xApply.intro}</ThemedText>

      <ThemedText type="subtitle">{strings.xApply.you}</ThemedText>
      <FormField label={strings.xApply.name} value={fields.name} onChangeText={set('name')} autoComplete="name" maxLength={120} error={errors.name} />
      <FormField label={strings.xApply.phone} value={fields.phone} onChangeText={set('phone')} keyboardType="phone-pad" autoComplete="tel" placeholder={strings.login.phonePlaceholder} error={errors.phone} />
      <ThemedText type="small" themeColor="textSecondary">{strings.xApply.phoneHint}</ThemedText>
      <FormField label={strings.xApply.email} value={fields.email} onChangeText={set('email')} keyboardType="email-address" autoCapitalize="none" autoComplete="email" error={errors.email} />
      <FormField label={strings.xApply.age} value={fields.age} onChangeText={set('age')} keyboardType="number-pad" maxLength={2} error={errors.age} />
      <FormField label={strings.xApply.city} value={fields.city} onChangeText={set('city')} maxLength={80} error={errors.city} />
      {minor ? (
        <ThemedView type="backgroundElement" style={styles.box}>
          <ThemedText type="smallBold">{strings.xApply.guardian(form.guardian_under_age)}</ThemedText>
          <FormField label={strings.xApply.guardianName} value={fields.guardian_name} onChangeText={set('guardian_name')} maxLength={120} error={errors.guardian_name} />
          <FormField label={strings.xApply.guardianPhone} value={fields.guardian_phone} onChangeText={set('guardian_phone')} keyboardType="phone-pad" error={errors.guardian_phone} />
        </ThemedView>
      ) : null}

      <ThemedText type="subtitle">{strings.xApply.experience}</ThemedText>
      <ChoiceField label={strings.xApply.frequency} choices={options(form.frequency)} value={fields.hike_frequency} onChange={set('hike_frequency')} error={errors.hike_frequency} />
      <ThemedText type="smallBold">{strings.xApply.hardest}</ThemedText>
      <FormField label={strings.xApply.hardestRoute} value={fields.hardest_route} onChangeText={set('hardest_route')} placeholder={strings.xApply.hardestRoutePlaceholder} maxLength={150} error={errors.hardest_route} />
      <FormField label={strings.xApply.hardestDate} value={fields.hardest_date} onChangeText={set('hardest_date')} placeholder="2026-06-14" keyboardType="numbers-and-punctuation" maxLength={10} error={errors.hardest_date} />
      <FormField label={strings.xApply.hardestKm} value={fields.hardest_km} onChangeText={set('hardest_km')} keyboardType="decimal-pad" error={errors.hardest_km} />
      <FormField label={strings.xApply.hardestElevation} value={fields.hardest_elevation_m} onChangeText={set('hardest_elevation_m')} keyboardType="number-pad" error={errors.hardest_elevation_m} />
      <FormField label={strings.xApply.hardestDuration} value={fields.hardest_duration_hours} onChangeText={set('hardest_duration_hours')} keyboardType="decimal-pad" error={errors.hardest_duration_hours} />
      <FormField label={strings.xApply.longestKm} value={fields.longest_km} onChangeText={set('longest_km')} keyboardType="decimal-pad" error={errors.longest_km} />
      <FormField label={strings.xApply.maxAscent} value={fields.max_ascent_m} onChangeText={set('max_ascent_m')} keyboardType="number-pad" error={errors.max_ascent_m} />
      <ChoiceField label={strings.xApply.pace} choices={options(form.pace)} value={fields.pace} onChange={set('pace')} error={errors.pace} />
      <ChoiceField
        label={strings.xApply.steadyPace}
        choices={[
          { value: '1', label: strings.xApply.yes },
          { value: '0', label: strings.xApply.no },
        ]}
        value={fields.steady_pace}
        onChange={set('steady_pace')}
        error={errors.steady_pace}
      />
      <FormField label={strings.xApply.challenge} value={fields.main_challenge} onChangeText={set('main_challenge')} multiline maxLength={1000} error={errors.main_challenge} />
      <FormField label={strings.xApply.trackLink} value={fields.track_link} onChangeText={set('track_link')} keyboardType="url" autoCapitalize="none" placeholder="https://" error={errors.track_link} />
      <ThemedText type="smallBold">{strings.xApply.done}</ThemedText>
      {form.experience.map((option) => (
        <CheckboxField key={option.key} label={option.label} checked={fields.experience.includes(option.key)} onChange={toggle('experience', option.key)} />
      ))}
      <FormError message={errors.experience?.[0]} />

      <ThemedText type="subtitle">{strings.xApply.gearHealth}</ThemedText>
      <ThemedText type="smallBold">{strings.xApply.gear}</ThemedText>
      {form.gear.map((option) => (
        <CheckboxField key={option.key} label={option.label} checked={fields.gear.includes(option.key)} onChange={toggle('gear', option.key)} />
      ))}
      <FormError message={errors.gear?.[0]} />
      <FormField label={strings.xApply.limitations} value={fields.limitations} onChangeText={set('limitations')} multiline maxLength={1000} error={errors.limitations} />

      <CheckboxField label={form.consent} checked={fields.consent} onChange={set('consent')} error={errors.consent} />
      <Pressable accessibilityRole="link" onPress={() => void Linking.openURL(form.privacy_url)}>
        <ThemedText type="linkPrimary">{strings.xApply.privacy}</ThemedText>
      </Pressable>

      <FormError message={Object.keys(errors).length > 0 ? strings.xApply.fixFields : formMessage(apply.error)} />
      <Button title={strings.xApply.submit} onPress={() => apply.mutate()} loading={apply.isPending} />
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  box: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.three },
});
