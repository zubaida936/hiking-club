import { StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '@/auth/auth-context';
import { ListRow, ListSection } from '@/components/list-row';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { strings } from '@/strings';

/**
 * More tab: every screen that isn't a tab of its own. Staff who may see hike registrations also get the Organizer
 * screen here (Android's tab bar holds 5 tabs at most); the API decides who, and checks again on every call.
 */
export default function MoreScreen() {
  const { member } = useAuth();

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScreenScroll>
        <ThemedText type="title" accessibilityRole="header">{strings.more.title}</ThemedText>

        {member?.organizer ? (
          <ListSection title={strings.more.staff}>
            <ListRow title={strings.more.organizer} href="/organizer" />
          </ListSection>
        ) : null}

        <ListSection title={strings.more.club}>
          <ListRow title={strings.xEvents.link} href="/x-events" />
          <ListRow title={strings.pastHikes.link} href="/past-hikes" />
          <ListRow title={strings.gallery.link} href="/gallery" />
          <ListRow title={strings.leaderboard.link} href="/leaderboard" />
          <ListRow title={strings.rankSystem.link} href="/rank-system" />
        </ListSection>

        <ListSection title={strings.more.plan}>
          <ListRow title={strings.guides.link} href="/guides" />
          <ListRow title={strings.equipment.title} href="/equipment" />
          <ListRow title={strings.difficulty.link} href="/difficulty-calculator" />
        </ListSection>

        <ListSection title={strings.more.contact}>
          <ListRow title={strings.about.link} href="/about" />
          <ListRow title={strings.contact.link} href="/contact" />
        </ListSection>
      </ScreenScroll>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
});
