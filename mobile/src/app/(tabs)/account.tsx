import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'expo-router';
import { StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { ListRow, ListSection } from '@/components/list-row';
import { LoginForm } from '@/components/login-form';
import { MemberProfile } from '@/components/member-profile';
import { MyUpcomingBookings, upcomingBookingsKey } from '@/components/my-upcoming-bookings';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ErrorState, LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Logged out: the login form. Logged in: rank and points, my own screens, my upcoming hikes, and log out. The club's
 * screens (guides, gallery, about…) are in More. */
export default function AccountScreen() {
  const { status, member, memberError, reloadMember, signOut } = useAuth();
  const queryClient = useQueryClient();
  const logout = useMutation({ mutationFn: signOut });
  const refresh = useMutation({
    mutationFn: () => Promise.all([queryClient.invalidateQueries({ queryKey: ['me'] }), queryClient.invalidateQueries({ queryKey: upcomingBookingsKey })]),
  });

  if (status === 'signedIn' && !member && memberError) {
    return <ErrorState message={memberError.message} onRetry={reloadMember} />;
  }

  if (status === 'loading' || (status === 'signedIn' && !member)) {
    return <LoadingState />;
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {member ? (
        <ScreenScroll onRefresh={() => refresh.mutate()} refreshing={refresh.isPending}>
          <MemberProfile member={member} />
          <ListSection title={strings.account.mine}>
            <ListRow title={strings.profile.link} href="/edit-profile" />
            {member.hiker ? (
              <>
                <ListRow title={strings.points.link} href="/my-points" />
                <ListRow title={strings.rewards.link} href="/my-rewards" />
                <ListRow title={strings.order.myOrders} href="/my-orders" />
              </>
            ) : null}
          </ListSection>
          <MyUpcomingBookings />
          <Button title={strings.account.logout} onPress={() => logout.mutate()} loading={logout.isPending} variant="secondary" />
          <Link href="/delete-account" style={styles.deleteLink}>
            <ThemedText type="small" themeColor="danger">{strings.deleteAccount.link}</ThemedText>
          </Link>
        </ScreenScroll>
      ) : (
        <ScreenScroll>
          <LoginForm />
        </ScreenScroll>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  deleteLink: { alignSelf: 'center', marginTop: Spacing.four },
});
