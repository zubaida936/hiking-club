import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import { fieldErrors, formMessage } from '@/api/errors';
import type { EventDetail, HikeUpdateSent } from '@/api/types';
import { Button, FormError, FormField } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/**
 * Organizers send a hike update (POST /organizer/events/{slug}/updates): the same as CMS → "Send update to hikers"
 * (shown on the hike and booking pages, emailed to every booking with an email). Push comes with the push task.
 * The updates already sent come from the hike page (GET /events/{slug}).
 */
export default function HikeUpdateScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const queryClient = useQueryClient();
  const [message, setMessage] = useState('');
  const detailKey = ['events', 'detail', slug];

  const { data: event, isPending } = useQuery({
    queryKey: detailKey,
    queryFn: () => apiFetch<{ data: EventDetail }>(`/events/${encodeURIComponent(slug)}`).then((answer) => answer.data),
  });

  const send = useMutation({
    mutationFn: () =>
      apiFetch<{ data: HikeUpdateSent }>(`/organizer/events/${encodeURIComponent(slug)}/updates`, { method: 'POST', body: { message } }).then(
        (answer) => answer.data,
      ),
    onSuccess: () => {
      setMessage('');
      // The hike page (and its list of updates here) shows the new one.
      void queryClient.invalidateQueries({ queryKey: detailKey });
    },
  });

  return (
    <ScreenScroll>
      <Stack.Screen options={{ title: event?.title ?? strings.hikeUpdate.title }} />
      <ThemedText>{strings.hikeUpdate.intro}</ThemedText>
      {send.isSuccess ? (
        <ThemedText accessibilityRole="alert" type="smallBold" themeColor="accent">
          {strings.hikeUpdate.sent(send.data.emailed_count)}
        </ThemedText>
      ) : null}
      <FormField
        label={strings.hikeUpdate.message}
        value={message}
        onChangeText={setMessage}
        placeholder={strings.hikeUpdate.placeholder}
        multiline
        maxLength={2000}
        style={styles.message}
        error={fieldErrors(send.error).message}
      />
      <FormError message={formMessage(send.error)} />
      <Button title={strings.hikeUpdate.send} onPress={() => send.mutate()} loading={send.isPending} />
      {send.isSuccess ? <Button title={strings.organizer.done} onPress={() => router.back()} variant="secondary" /> : null}

      <ThemedText type="subtitle">{strings.hikeUpdate.previous}</ThemedText>
      {isPending ? (
        <ActivityIndicator />
      ) : !event || event.updates.length === 0 ? (
        <ThemedText themeColor="textSecondary">{strings.hikeUpdate.none}</ThemedText>
      ) : (
        <View style={styles.list}>
          {event.updates.map((update) => (
            <ThemedView key={update.posted_at} type="backgroundElement" style={styles.card}>
              <ThemedText type="small" themeColor="textSecondary">{formatMoment(update.posted_at)}</ThemedText>
              <ThemedText>{update.message}</ThemedText>
            </ThemedView>
          ))}
        </View>
      )}
    </ScreenScroll>
  );
}

const styles = StyleSheet.create({
  message: { minHeight: 120, textAlignVertical: 'top' },
  list: { gap: Spacing.two },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
});
