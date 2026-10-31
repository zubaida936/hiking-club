import { useCameraPermissions } from 'expo-camera';
import type { ReactNode } from 'react';
import { StyleSheet } from 'react-native';

import { Button } from '@/components/form';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { LoadingState } from '@/components/states';
import { Spacing } from '@/constants/theme';
import { strings } from '@/strings';

/** Shows the scanner only once the camera is allowed; asks for it, or explains it's blocked in the phone's settings. */
export function CameraGate({ children, reason }: { children: ReactNode; reason: string }) {
  const [permission, requestPermission] = useCameraPermissions();

  if (!permission) {
    return <LoadingState />;
  }

  if (!permission.granted) {
    return (
      <ThemedView style={styles.center}>
        <ThemedText style={styles.text}>{permission.canAskAgain ? reason : strings.organizer.cameraBlocked}</ThemedText>
        {permission.canAskAgain ? <Button title={strings.organizer.allowCamera} onPress={() => void requestPermission()} /> : null}
      </ThemedView>
    );
  }

  return children;
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: Spacing.three, padding: Spacing.four },
  text: { textAlign: 'center' },
});
