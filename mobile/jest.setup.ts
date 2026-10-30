// Runs before every test file. Native modules that don't exist in Jest get their library's test stand-in.

// The cart's storage: an in-memory AsyncStorage (the library's own mock).
jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));

// The booking QR code draws SVG, which tests can't read: show what it holds as text instead.
jest.mock('react-native-qrcode-svg', () => {
  const { Text } = require('react-native');
  const { createElement } = require('react');
  return { __esModule: true, default: ({ value }: { value: string }) => createElement(Text, { testID: 'qr-code' }, value) };
});

// The root layout wraps the app in GestureHandlerRootView (photo viewer zoom): use the library's test stand-in.
require('react-native-gesture-handler/jestSetup');

// The phone's connection (offline banner, React Query's online state): the library's test stand-in (always online).
jest.mock('@react-native-community/netinfo', () => require('@react-native-community/netinfo/jest/netinfo-mock.js'));
