import { useMutation } from '@tanstack/react-query';
import { ActivityIndicator, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '@/auth/auth-context';
import { Button } from '@/components/form';
import { LoginForm } from '@/components/login-form';
import { MemberProfile } from '@/components/member-profile';
import { ScreenScroll } from '@/components/screen-scroll';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Logged out: the login form. Logged in: the member's rank and points, and log out. */
export default function AccountScreen() {
  const { status, member, memberError, reloadMember, signOut } = useAuth();
  const logout = useMutation({ mutationFn: signOut });

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
      <ScreenScroll>
        {member ? <MemberProfile member={member} onLogout={() => logout.mutate()} loggingOut={logout.isPending} /> : <LoginForm />}
      </ScreenScroll>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
