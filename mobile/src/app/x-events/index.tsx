import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { XEvents } from '@/api/types';
import { useAuth } from '@/auth/auth-context';
import { EventCard } from '@/components/event-card';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/**
 * X Events (GET /x-events): the website's page texts, the member's access and application status, the upcoming
 * X hikes, and "Apply". Whether someone may book or apply is the API's answer (`access`), never worked out here.
 */
export default function XEventsScreen() {
  const { status } = useAuth();
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    // The answer depends on who is logged in: log in or out and it loads again.
    queryKey: ['x-events', status],
    queryFn: () => apiFetch<{ data: XEvents }>('/x-events').then((answer) => answer.data),
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

  const { page, events } = data;

  return (
    <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
      <ThemedText type="subtitle">{page.heading}</ThemedText>
      <ThemedText>{page.intro}</ThemedText>
      <AccessBox access={data.access} />

      <ThemedText type="subtitle">{strings.xEvents.requirements}</ThemedText>
      {page.requirements.map((item) => (
        <ThemedView key={item.title} type="backgroundElement" style={styles.card}>
          <ThemedText type="smallBold">{item.title}</ThemedText>
          <ThemedText type="small">{item.text}</ThemedText>
        </ThemedView>
      ))}

      <ThemedText type="subtitle">{strings.xEvents.steps}</ThemedText>
      {page.steps.map((item, index) => (
        <ThemedView key={item.title} type="backgroundElement" style={styles.card}>
          <ThemedText type="smallBold">
            {index + 1}. {item.title}
          </ThemedText>
          <ThemedText type="small">{item.text}</ThemedText>
        </ThemedView>
      ))}

      <ThemedText type="subtitle">{strings.xEvents.upcoming}</ThemedText>
      {events.length === 0 ? <ThemedText>{page.no_events}</ThemedText> : events.map((event) => <EventCard key={event.slug} event={event} />)}
    </ScreenScroll>
  );
}

/** What the visitor can do now: book (approved / Elite), wait (under review), or apply (again). */
function AccessBox({ access }: { access: XEvents['access'] }) {
  const apply = (title: string) => <Button title={title} onPress={() => router.push('/x-events/apply')} />;

  // Guests: apply, or log in when already approved.
  if (access === null) {
    return (
      <View style={styles.access}>
        {apply(strings.xEvents.apply)}
        <Button title={strings.xEvents.loginToBook} onPress={() => router.push('/account')} variant="secondary" />
      </View>
    );
  }

  const application = access.application;

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      {access.can_book ? (
        <ThemedText type="smallBold">{strings.xEvents.canBook}</ThemedText>
      ) : application ? (
        <>
          <ThemedText type="smallBold">{application.status.label}</ThemedText>
          <ThemedText type="small">{strings.xEvents.status[application.status.key] ?? ''}</ThemedText>
          <ThemedText type="small" themeColor="textSecondary">
            {strings.xEvents.submittedOn(formatMoment(application.submitted_at))}
          </ThemedText>
        </>
      ) : null}
      {access.can_apply ? apply(application ? strings.xEvents.applyAgain : strings.xEvents.apply) : null}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  access: { gap: Spacing.two },
});
