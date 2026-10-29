import { useQuery, useQueryClient } from '@tanstack/react-query';
import * as Device from 'expo-device';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

import { apiFetch, setUnauthorizedHandler } from '@/api/client';
import type { LoginResult, Member } from '@/api/types';
import { clearToken, getToken, saveToken } from '@/auth/token';

type AuthState = {
  /** loading: reading the stored token at start; then signedIn / signedOut. */
  status: 'loading' | 'signedIn' | 'signedOut';
  /** The logged-in member (null while loading or logged out). */
  member: Member | null;
  /** Loading the member failed (e.g. offline at start); `reloadMember` tries again. */
  memberError: Error | null;
  reloadMember: () => void;
  /** Keep the token and member from POST /auth/login, /auth/activate or /auth/password/reset. */
  signIn: (result: LoginResult) => Promise<void>;
  /** Log out this phone: the server revokes the token, the app forgets it. */
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

/** Name of this login on the server ("Pixel 8"), shown later in "my devices". */
export function deviceName(): string {
  return Device.modelName ?? 'Phone';
}

/** Queries that belong to the logged-in member: removed on logout so the next member never sees them. */
const MEMBER_QUERIES = [['me'], ['my']];

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  // undefined: not read yet; null: no token.
  const [token, setToken] = useState<string | null | undefined>(undefined);

  useEffect(() => {
    // Ignore the answer if the app (or a test) closed in the meantime.
    let active = true;
    getToken().then(
      (stored) => active && setToken(stored ?? null),
      () => active && setToken(null),
    );
    return () => {
      active = false;
    };
  }, []);

  const forget = useCallback(async () => {
    await clearToken().catch(() => undefined);
    setToken(null);
    MEMBER_QUERIES.forEach((queryKey) => queryClient.removeQueries({ queryKey }));
  }, [queryClient]);

  // A 401 on a request with the token: it was revoked elsewhere (new password, deactivated, logged out everywhere).
  useEffect(() => {
    setUnauthorizedHandler(() => void forget());
    return () => setUnauthorizedHandler(null);
  }, [forget]);

  const { data: member, error: memberError, refetch } = useQuery({
    queryKey: ['me'],
    queryFn: () => apiFetch<{ data: Member }>('/me').then((answer) => answer.data),
    enabled: Boolean(token),
  });

  const signIn = useCallback(
    async (result: LoginResult) => {
      await saveToken(result.token);
      queryClient.setQueryData(['me'], result.member);
      setToken(result.token);
    },
    [queryClient],
  );

  const signOut = useCallback(async () => {
    // Best effort: even offline, the phone forgets the token.
    await apiFetch('/auth/logout', { method: 'POST' }).catch(() => undefined);
    await forget();
  }, [forget]);

  const value = useMemo<AuthState>(
    () => ({
      status: token === undefined ? 'loading' : token ? 'signedIn' : 'signedOut',
      member: token ? (member ?? null) : null,
      memberError: token ? memberError : null,
      reloadMember: () => void refetch(),
      signIn,
      signOut,
    }),
    [token, member, memberError, refetch, signIn, signOut],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const auth = useContext(AuthContext);
  if (!auth) throw new Error('useAuth() must be used inside <AuthProvider>.');

  return auth;
}
