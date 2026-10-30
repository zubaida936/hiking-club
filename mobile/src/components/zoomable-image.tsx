import { Image } from 'expo-image';
import { StyleSheet } from 'react-native';
import { GestureDetector, usePanGesture, usePinchGesture, useSimultaneousGestures, useTapGesture } from 'react-native-gesture-handler';
import Animated, { useAnimatedStyle, useSharedValue, withTiming } from 'react-native-reanimated';
import { scheduleOnRN } from 'react-native-worklets';

const MAX_SCALE = 4;
const DOUBLE_TAP_SCALE = 2.5;

type Props = {
  uri: string;
  accessibilityLabel: string;
  /** Called with true when the photo is zoomed in (the viewer then stops swiping to the next photo) and false when back. */
  onZoomChange: (zoomed: boolean) => void;
  /** Whether panning is on (only while zoomed, so swiping still changes photo otherwise). */
  zoomed: boolean;
};

/** A full-screen photo: pinch to zoom (up to 4×), drag while zoomed, double-tap to zoom in or back out. */
export function ZoomableImage({ uri, accessibilityLabel, onZoomChange, zoomed }: Props) {
  const scale = useSharedValue(1);
  const savedScale = useSharedValue(1);
  const x = useSharedValue(0);
  const y = useSharedValue(0);
  const savedX = useSharedValue(0);
  const savedY = useSharedValue(0);

  const reset = () => {
    'worklet';
    scale.value = withTiming(1);
    savedScale.value = 1;
    x.value = withTiming(0);
    y.value = withTiming(0);
    savedX.value = 0;
    savedY.value = 0;
    scheduleOnRN(onZoomChange, false);
  };

  const pinch = usePinchGesture({
    onUpdate: (event) => {
      'worklet';
      scale.value = Math.min(MAX_SCALE, Math.max(1, savedScale.value * event.scale));
    },
    onDeactivate: () => {
      'worklet';
      if (scale.value < 1.05) {
        reset();
        return;
      }
      savedScale.value = scale.value;
      scheduleOnRN(onZoomChange, true);
    },
  });

  const pan = usePanGesture({
    enabled: zoomed,
    onUpdate: (event) => {
      'worklet';
      x.value = savedX.value + event.translationX;
      y.value = savedY.value + event.translationY;
    },
    onDeactivate: () => {
      'worklet';
      savedX.value = x.value;
      savedY.value = y.value;
    },
  });

  const doubleTap = useTapGesture({
    numberOfTaps: 2,
    onActivate: () => {
      'worklet';
      if (savedScale.value > 1) {
        reset();
        return;
      }
      scale.value = withTiming(DOUBLE_TAP_SCALE);
      savedScale.value = DOUBLE_TAP_SCALE;
      scheduleOnRN(onZoomChange, true);
    },
  });

  const gesture = useSimultaneousGestures(pinch, pan, doubleTap);
  const style = useAnimatedStyle(() => ({ transform: [{ translateX: x.value }, { translateY: y.value }, { scale: scale.value }] }));

  return (
    <GestureDetector gesture={gesture}>
      <Animated.View style={[styles.fill, style]}>
        <Image source={uri} style={styles.fill} contentFit="contain" accessibilityLabel={accessibilityLabel} />
      </Animated.View>
    </GestureDetector>
  );
}

const styles = StyleSheet.create({
  fill: { flex: 1 },
});
