import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { Member } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/** My profile (PUT /me/profile): name, email and emergency contact. The phone (the login) is changed by the organizers. */
export default function EditProfileScreen() {
  const { member } = useAuth();

  return member ? <ProfileForm member={member} /> : null;
}

function ProfileForm({ member }: { member: Member }) {
  const queryClient = useQueryClient();
  const [fields, setFields] = useState({
    name: member.name,
    email: member.email ?? '',
    current_password: '',
    emergency_contact_name: member.emergency_contact?.name ?? '',
    emergency_contact_phone: member.emergency_contact?.phone ?? '',
  });
  const set = (key: keyof typeof fields) => (value: string) => setFields((current) => ({ ...current, [key]: value }));
  // The password is only asked when the email changes (that's where reset codes go).
  const emailChanges = fields.email.trim().toLowerCase() !== (member.email ?? '');

  const save = useMutation({
    mutationFn: () =>
      apiFetch<{ data: Member }>('/me/profile', {
        method: 'PUT',
        body: {
          name: fields.name,
          email: fields.email.trim() || null,
          current_password: emailChanges ? fields.current_password : null,
          emergency_contact_name: fields.emergency_contact_name.trim() || null,
          emergency_contact_phone: fields.emergency_contact_phone.trim() || null,
        },
      }),
    onSuccess: ({ data }) => {
      queryClient.setQueryData(['me'], data);
      router.back();
    },
  });
  const errors = fieldErrors(save.error);

  return (
    <ScreenScroll>
      <ThemedText themeColor="textSecondary">{strings.profile.intro}</ThemedText>
      <FormField label={strings.profile.name} value={fields.name} onChangeText={set('name')} autoComplete="name" error={errors.name} />

      <ThemedText type="smallBold">{strings.profile.phone}</ThemedText>
      <ThemedText>{member.phone}</ThemedText>
      <ThemedText type="small" themeColor="textSecondary">{strings.profile.phoneNote}</ThemedText>

      <FormField label={strings.profile.email} value={fields.email} onChangeText={set('email')} keyboardType="email-address" autoCapitalize="none" autoComplete="email" error={errors.email} />
      {emailChanges ? (
        <FormField label={strings.profile.currentPassword} value={fields.current_password} onChangeText={set('current_password')} secureTextEntry autoComplete="current-password" error={errors.current_password} />
      ) : null}

      <ThemedText type="subtitle">{strings.profile.emergency}</ThemedText>
      <FormField label={strings.profile.emergencyName} value={fields.emergency_contact_name} onChangeText={set('emergency_contact_name')} error={errors.emergency_contact_name} />
      <FormField label={strings.profile.emergencyPhone} value={fields.emergency_contact_phone} onChangeText={set('emergency_contact_phone')} keyboardType="phone-pad" error={errors.emergency_contact_phone} />

      <FormError message={formMessage(save.error)} />
      <Button title={strings.profile.save} onPress={() => save.mutate()} loading={save.isPending} />
    </ScreenScroll>
  );
}
