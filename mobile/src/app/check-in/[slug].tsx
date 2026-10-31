import { useQuery, useQueryClient } from '@tanstack/react-query';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { FlatList, Linking, Pressable, StyleSheet, View } from 'react-native';

import { ApiError, apiFetch } from '@/api/client';
import type { CheckIn, OrganizerHike } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { Chip } from '@/components/chip';
import { Button, FormField } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { EmptyState, ErrorState, LoadingState } from '@/components/states';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { useCheckIn, type AttendanceValue } from '@/organizer/check-in-store';
import { strings } from '@/strings';

/**
 * A hike's check-in list (GET /organizer/events/{slug}/registrations). It is saved on the phone each time it loads,
 * so without signal the saved list shows and marks wait in the queue (see check-in-store).
 */
export default function CheckInScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { member } = useAuth();
  const { lists, saveList, send } = useCheckIn();
  const [search, setSearch] = useState('');
  const hike = useQueryClient()
    .getQueryData<OrganizerHike[]>(['organizer', 'events'])
    ?.find((item) => item.event.slug === slug);

  const { data, error, failureReason, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['organizer', 'check-in', slug],
    queryFn: () => apiFetch<{ data: CheckIn[] }>(`/organizer/events/${encodeURIComponent(slug)}/registrations`).then((answer) => answer.data),
  });

  useEffect(() => {
    if (data) saveList(slug, data);
  }, [data, slug, saveList]);

  const saved = lists[slug];
  // From the first failed try (the query retries network trouble before giving up).
  const problem = error ?? failureReason;
  const offline = problem instanceof ApiError && problem.status === 0;

  if (isPending && !saved && !offline) {
    return <LoadingState />;
  }

  if (!saved) {
    return <ErrorState message={offline ? strings.organizer.noList : error?.message} onRetry={() => void refetch()} />;
  }

  const items = saved.items;
  const confirmed = items.filter((item) => item.can_check_in);
  const checkedIn = confirmed.filter((item) => item.attendance === 'attended').length;
  const query = search.trim().toLowerCase();
  const shown = query === '' ? items : items.filter((item) => [item.name, item.phone, item.reference].some((text) => text.toLowerCase().includes(query)));
  // Before the hike day the server refuses marks; without the hike list (opened from a link) let the server say so.
  const canMark = Boolean(member?.organizer?.can_mark_attendance) && hike?.check_in_open !== false;

  return (
    <ThemedView style={styles.container}>
      <Stack.Screen options={{ title: hike?.event.title ?? '' }} />
      <FlatList
        testID="check-in-list"
        data={shown}
        keyExtractor={(item) => item.reference}
        renderItem={({ item }) => <CheckInRow slug={slug} item={item} canMark={canMark} />}
        contentContainerStyle={styles.list}
        keyboardShouldPersistTaps="handled"
        refreshing={isRefetching}
        onRefresh={() => {
          void send();
          void refetch();
        }}
        ListHeaderComponent={
          <View style={styles.header}>
            {offline ? (
              <ThemedView type="backgroundElement" style={styles.notice}>
                <ThemedText type="small">{strings.organizer.savedAt(formatMoment(saved.savedAt))}</ThemedText>
              </ThemedView>
            ) : null}
            <ThemedText type="subtitle">{strings.organizer.counts(checkedIn, confirmed.length)}</ThemedText>
            {hike && !hike.check_in_open ? <ThemedText type="small" themeColor="textSecondary">{strings.organizer.notYet}</ThemedText> : null}
            {canMark ? <Button title={strings.organizer.scan} onPress={() => router.push({ pathname: '/check-in/scan', params: { slug } })} /> : null}
            {member?.organizer?.can_post_updates ? (
              <Button title={strings.hikeUpdate.open} onPress={() => router.push({ pathname: '/hike-update', params: { slug } })} variant="secondary" />
            ) : null}
            <FormField label={strings.organizer.search} value={search} onChangeText={setSearch} autoCapitalize="none" autoCorrect={false} />
          </View>
        }
        ListEmptyComponent={<EmptyState inline message={strings.organizer.noMatch} />}
      />
    </ThemedView>
  );
}

function CheckInRow({ slug, item, canMark }: { slug: string; item: CheckIn; canMark: boolean }) {
  const { pending, refused, mark } = useCheckIn();
  const waiting = pending.some((entry) => entry.reference === item.reference);
  // Pressing the selected button again clears the mark.
  const toggle = (value: NonNullable<AttendanceValue>) => mark(slug, item.reference, item.attendance === value ? null : value);
  const payment = item.payment.state === 'paid' ? strings.organizer.paid : item.payment.state === 'none' ? strings.organizer.free : strings.organizer.toPay(item.payment.amount, item.payment.method);

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <ThemedText type="smallBold">{item.name}</ThemedText>
      <Pressable accessibilityRole="link" accessibilityLabel={strings.organizer.call(item.name)} onPress={() => void Linking.openURL(`tel:${item.phone}`)}>
        <ThemedText type="linkPrimary">{item.phone}</ThemedText>
      </Pressable>
      {item.pickup ? <ThemedText type="small">{strings.organizer.pickup(item.pickup.point, item.pickup.time)}</ThemedText> : null}
      <ThemedText type="small" themeColor={item.payment.state === 'due' ? 'danger' : undefined}>
        {payment}
      </ThemedText>
      <Pressable
        accessibilityRole="link"
        accessibilityLabel={strings.organizer.call(item.emergency_contact.name)}
        onPress={() => void Linking.openURL(`tel:${item.emergency_contact.phone}`)}>
        <ThemedText type="small" themeColor="textSecondary">
          {strings.organizer.emergency(item.emergency_contact.name, item.emergency_contact.phone)}
        </ThemedText>
      </Pressable>
      {item.notes ? <ThemedText type="small" themeColor="textSecondary">{item.notes}</ThemedText> : null}

      {!item.can_check_in ? (
        <ThemedText type="small" themeColor="textSecondary">{strings.organizer.awaitingApproval}</ThemedText>
      ) : canMark ? (
        <View style={styles.buttons}>
          <Chip label={strings.organizer.attended} accessibilityLabel={`${strings.organizer.attended}: ${item.name}`} selected={item.attendance === 'attended'} onPress={() => toggle('attended')} />
          <Chip label={strings.organizer.noShow} accessibilityLabel={`${strings.organizer.noShow}: ${item.name}`} selected={item.attendance === 'no_show'} onPress={() => toggle('no_show')} />
          {waiting ? <ThemedText type="small" themeColor="textSecondary">{strings.organizer.notSent}</ThemedText> : null}
        </View>
      ) : item.attendance ? (
        <ThemedText type="small">{item.attendance === 'attended' ? strings.organizer.attended : strings.organizer.noShow}</ThemedText>
      ) : null}
      {refused[item.reference] ? <ThemedText type="small" themeColor="danger">{refused[item.reference]}</ThemedText> : null}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  list: { padding: Spacing.three, gap: Spacing.three, width: '100%', maxWidth: MaxContentWidth, alignSelf: 'center' },
  header: { gap: Spacing.two },
  notice: { borderRadius: Spacing.three, padding: Spacing.three },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.half },
  buttons: { flexDirection: 'row', alignItems: 'center', gap: Spacing.two, marginTop: Spacing.one },
});
