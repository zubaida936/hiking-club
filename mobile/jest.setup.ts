// Runs before every test file. Native modules that don't exist in Jest get their library's test stand-in.

// The cart's storage: an in-memory AsyncStorage (the library's own mock).
jest.mock('@react-native-async-storage/async-storage', () => require('@react-native-async-storage/async-storage/jest/async-storage-mock'));
