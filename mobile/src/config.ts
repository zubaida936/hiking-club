import Constants from 'expo-constants';

/**
 * The Laravel API. Set EXPO_PUBLIC_API_URL (the Docker `mobile` service sets it from EXPO_HOST_IP);
 * read with dot notation so Expo inlines it at build time.
 */
export const API_URL = (process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8080/api/v1').replace(/\/+$/, '');

/** This app's version, from app.json ("1.0.0"); compared with the API's min_version. */
export const APP_VERSION = Constants.expoConfig?.version ?? '0.0.0';
