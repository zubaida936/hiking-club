import * as SecureStore from 'expo-secure-store';

/**
 * The login token from POST /auth/login. Only ever kept in secure storage (Android Keystore),
 * never in AsyncStorage or logs.
 */
const KEY = 'hiking.api-token';

export function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(KEY);
}

export function saveToken(token: string): Promise<void> {
  return SecureStore.setItemAsync(KEY, token);
}

export function clearToken(): Promise<void> {
  return SecureStore.deleteItemAsync(KEY);
}
