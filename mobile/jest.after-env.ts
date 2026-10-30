// Runs in every test file after Jest is ready (beforeEach exists here, not in jest.setup.ts).
import AsyncStorage from '@react-native-async-storage/async-storage';

// The saved query cache lives in AsyncStorage: every test starts without one.
beforeEach(async () => {
  await AsyncStorage.clear();
});
