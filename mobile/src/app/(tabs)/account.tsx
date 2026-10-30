import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { LoginForm } from '@/components/login-form';
import { MemberProfile } from '@/components/member-profile';
import { MyUpcomingBookings, upcomingBookingsKey } from '@/components/my-upcoming-bookings';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Logged out: the login form. Logged in: rank and points, my upcoming hikes, and log out. */
export default function AccountScreen() {
  const { status, member, memberError, reloadMember, signOut } = useAuth();
  const queryClient = useQueryClient();
  const logout = useMutation({ mutationFn: signOut });
  const refresh = useMutation({
    mutationFn: () => Promise.all([queryClient.invalidateQueries({ queryKey: ['me'] }), queryClient.invalidateQueries({ queryKey: upcomingBookingsKey })]),
  });

  if (status === 'signedIn' && !member && memberError) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{memberError.message}</ThemedText>
        <Button title={strings.errors.retry} onPress={reloadMember} variant="secondary" />
      </ThemedView>
    );
  }

  if (status === 'loading' || (status === 'signedIn' && !member)) {
    return (
      <ThemedView style={styles.center}>
        <ActivityIndicator />
      </ThemedView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {member ? (
        <ScreenScroll onRefresh={() => refresh.mutate()} refreshing={refresh.isPending}>
          <MemberProfile member={member} />
          <Link href="/edit-profile">
            <ThemedText type="linkPrimary">{strings.profile.link}</ThemedText>
          </Link>
          {member.hiker ? (
            <View style={styles.links}>
              <Link href="/my-points">
                <ThemedText type="linkPrimary">{strings.points.link}</ThemedText>
              </Link>
              <Link href="/my-rewards">
                <ThemedText type="linkPrimary">{strings.rewards.link}</ThemedText>
              </Link>
              <Link href="/my-orders">
                <ThemedText type="linkPrimary">{strings.order.myOrders}</ThemedText>
              </Link>
              <Link href="/x-events">
                <ThemedText type="linkPrimary">{strings.xEvents.link}</ThemedText>
              </Link>
              <Link href="/rank-system">
                <ThemedText type="linkPrimary">{strings.rankSystem.link}</ThemedText>
              </Link>
              <Link href="/leaderboard">
                <ThemedText type="linkPrimary">{strings.leaderboard.link}</ThemedText>
              </Link>
              <Link href="/guides">
                <ThemedText type="linkPrimary">{strings.guides.link}</ThemedText>
              </Link>
              <Link href="/equipment">
                <ThemedText type="linkPrimary">{strings.equipment.title}</ThemedText>
              </Link>
            </View>
          ) : null}
          <MyUpcomingBookings />
          <Button title={strings.account.logout} onPress={() => logout.mutate()} loading={logout.isPending} variant="secondary" />
          <Link href="/delete-account" style={styles.deleteLink}>
            <ThemedText type="small" themeColor="danger">{strings.deleteAccount.link}</ThemedText>
          </Link>
        </ScreenScroll>
      ) : (
        <ScreenScroll>
          <LoginForm />
          <Link href="/rank-system">
            <ThemedText type="linkPrimary">{strings.rankSystem.link}</ThemedText>
          </Link>
          <Link href="/guides">
            <ThemedText type="linkPrimary">{strings.guides.link}</ThemedText>
          </Link>
          <Link href="/equipment">
            <ThemedText type="linkPrimary">{strings.equipment.title}</ThemedText>
          </Link>
        </ScreenScroll>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
  links: { gap: Spacing.two },
  deleteLink: { alignSelf: 'center', marginTop: Spacing.four },
});
