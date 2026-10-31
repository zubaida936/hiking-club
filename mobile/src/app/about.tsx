import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router } from 'expo-router';
import { Linking, StyleSheet, View } from 'react-native';

import { apiFetch } from '@/api/client';
import type { About } from '@/api/types';
import { Button } from '@/components/form';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { ErrorState, LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/**
 * About the club (GET /about), like the website's page: story and offers from config/site.php, the team from
 * CMS → Team, testimonials, club numbers and the Google reviews link.
 */
export default function AboutScreen() {
  const { data, error, isPending, refetch, isRefetching } = useQuery({
    queryKey: ['about'],
    queryFn: () => apiFetch<{ data: About }>('/about').then((answer) => answer.data),
  });

  if (isPending) {
    return <LoadingState />;
  }

  if (error) {
    return <ErrorState message={error.message} onRetry={() => void refetch()} />;
  }

  return (
    <ScreenScroll onRefresh={() => void refetch()} refreshing={isRefetching}>
      <ThemedText type="smallBold" themeColor="accent">{strings.about.since(data.founded)}</ThemedText>
      <ThemedText>{data.intro}</ThemedText>

      <View style={styles.stats}>
        <Stat value={strings.about.hikes(data.stats.hikes)} />
        <Stat value={strings.about.hikers(data.stats.hikers)} />
      </View>

      <ThemedText type="subtitle">{strings.about.story}</ThemedText>
      {data.story.map((paragraph) => (
        <ThemedText key={paragraph}>{paragraph}</ThemedText>
      ))}

      <ThemedText type="subtitle">{strings.about.offers}</ThemedText>
      {data.offers.map((offer) => (
        <ThemedView key={offer.title} type="backgroundElement" style={styles.card}>
          <ThemedText type="smallBold">
            {offer.icon} {offer.title}
          </ThemedText>
          <ThemedText type="small">{offer.text}</ThemedText>
        </ThemedView>
      ))}

      {data.team.length > 0 ? (
        <>
          <ThemedText type="subtitle">{strings.about.team}</ThemedText>
          {data.team.map((member) => (
            <ThemedView key={member.name} type="backgroundElement" style={[styles.card, styles.member]}>
              {member.photo_url ? <Image source={member.photo_url} style={styles.photo} contentFit="cover" accessibilityIgnoresInvertColors /> : null}
              <View style={styles.flex}>
                <ThemedText type="smallBold">{member.name}</ThemedText>
                <ThemedText type="small" themeColor="accent">{member.role}</ThemedText>
                {member.bio ? <ThemedText type="small">{member.bio}</ThemedText> : null}
              </View>
            </ThemedView>
          ))}
        </>
      ) : null}

      <ThemedText type="subtitle">{strings.about.testimonials}</ThemedText>
      {data.testimonials.map((quote) => (
        <ThemedView key={quote.name} type="backgroundElement" style={styles.card}>
          <ThemedText type="small">“{quote.text}”</ThemedText>
          <ThemedText type="smallBold">{quote.name}</ThemedText>
        </ThemedView>
      ))}
      {data.google_reviews_url ? (
        <Button title={strings.about.reviews} onPress={() => void Linking.openURL(data.google_reviews_url as string)} variant="secondary" />
      ) : null}
      <Button title={strings.about.seeHikes} onPress={() => router.navigate('/hikes')} />
    </ScreenScroll>
  );
}

function Stat({ value }: { value: string }) {
  return (
    <ThemedView type="backgroundElement" style={[styles.card, styles.stat]}>
      <ThemedText type="subtitle">{value}</ThemedText>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  stats: { flexDirection: 'row', gap: Spacing.three },
  stat: { flex: 1, alignItems: 'center' },
  card: { borderRadius: Spacing.three, padding: Spacing.three, gap: Spacing.one },
  member: { flexDirection: 'row', gap: Spacing.three, alignItems: 'flex-start' },
  photo: { width: 64, height: 64, borderRadius: 32 },
  flex: { flex: 1, gap: Spacing.half },
});
