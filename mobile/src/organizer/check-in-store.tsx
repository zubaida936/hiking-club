import AsyncStorage from '@react-native-async-storage/async-storage';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import { ApiError, apiFetch } from '@/api/client';
import type { CheckIn } from '@/api/types';
import { useAuth } from '@/auth/auth-context';

export type AttendanceValue = CheckIn['attendance'];

/** A mark made on the phone and not yet accepted by the server (sent again until it is, or until it's refused). */
type PendingMark = { slug: string; reference: string; attendance: AttendanceValue; previous: AttendanceValue };

type SavedList = { items: CheckIn[]; savedAt: string };

type CheckInState = {
  /** The last check-in list loaded for each hike (by slug), kept for working without signal. */
  lists: Record<string, SavedList>;
  /** Marks waiting to be sent, oldest first. */
  pending: PendingMark[];
  /** The server's message for marks it refused (by booking reference). */
  refused: Record<string, string>;
  saveList: (slug: string, items: CheckIn[]) => void;
  mark: (slug: string, reference: string, attendance: AttendanceValue) => void;
  /** Send the waiting marks now; stops at the first network problem. */
  send: () => Promise<void>;
};

/** What's kept on the phone, for one member (`owner`, their id): another login never sees it. */
type Stored = { owner: number | null; lists: Record<string, SavedList>; pending: PendingMark[]; refused: Record<string, string> };

const KEY = 'hiking.organizer';
const EMPTY: Stored = { owner: null, lists: {}, pending: [], refused: {} };

const CheckInContext = createContext<CheckInState | null>(null);

/** Change one hiker's row in a saved list. */
function changeItem(lists: Stored['lists'], slug: string, reference: string, change: (item: CheckIn) => CheckIn): Stored['lists'] {
  const list = lists[slug];
  if (!list) return lists;

  return { ...lists, [slug]: { ...list, items: list.items.map((item) => (item.reference === reference ? change(item) : item)) } };
}

/**
 * Organizer check-in that works on the trail without signal: each hike's list is saved on the phone when it loads,
 * a mark shows at once and waits in a queue until the server accepts it (PUT /organizer/registrations/{ref}/attendance,
 * which accepts the same mark twice). The server's rules still decide: a refused mark is undone with its message.
 * The lists hold hikers' phone numbers and emergency contacts: they belong to the logged-in organizer and are
 * deleted from the phone on logout.
 */
export function CheckInProvider({ children }: { children: ReactNode }) {
  const { status, member } = useAuth();
  const owner = member?.id ?? null;
  // The data lives in a ref, always current for send() across its awaits; the state is its copy for rendering.
  const data = useRef<Stored>(EMPTY);
  const [stored, setStored] = useState<Stored>(EMPTY);
  const [loaded, setLoaded] = useState(false);
  const sending = useRef(false);
  // Only the logged-in member's data counts.
  const mine = owner !== null && stored.owner === owner ? stored : { ...EMPTY, owner };

  useEffect(() => {
    let active = true;
    AsyncStorage.getItem(KEY)
      .then((saved) => {
        if (!active || !saved) return;
        data.current = { ...EMPTY, ...(JSON.parse(saved) as Partial<Stored>) };
        setStored(data.current);
      })
      .catch(() => undefined)
      .finally(() => active && setLoaded(true));
    return () => {
      active = false;
    };
  }, []);

  // Saved while the organizer is logged in; on logout (here or from elsewhere) deleted from the phone. Waits for
  // the saved copy to be read first, so the empty start never overwrites it and a late read can't restore it.
  useEffect(() => {
    if (!loaded) return;
    if (status === 'signedIn' && stored.owner !== null) void AsyncStorage.setItem(KEY, JSON.stringify(stored)).catch(() => undefined);
    if (status === 'signedOut') void AsyncStorage.removeItem(KEY).catch(() => undefined);
  }, [stored, loaded, status]);

  /** Apply a change to the logged-in member's data (starting fresh if the phone held someone else's). */
  const update = useCallback(
    (change: (current: Stored) => Stored) => {
      const current = data.current.owner === owner ? data.current : { ...EMPTY, owner };
      data.current = change(current);
      setStored(data.current);
    },
    [owner],
  );

  const send = useCallback(async () => {
    if (sending.current || owner === null) return;
    sending.current = true;
    try {
      // One at a time, oldest first, so a later mark for the same hiker wins.
      for (let next = data.current.pending[0]; next; next = data.current.pending[0]) {
        const mark = next;
        try {
          const answer = await apiFetch<{ data: CheckIn }>(`/organizer/registrations/${encodeURIComponent(mark.reference)}/attendance`, {
            method: 'PUT',
            body: { attendance: mark.attendance },
          });
          update((current) => {
            const pending = current.pending.slice(1);
            // A newer mark for this hiker made meanwhile stays on screen; otherwise show what the server says.
            const newer = pending.some((other) => other.reference === mark.reference);
            return { ...current, pending, lists: newer ? current.lists : changeItem(current.lists, mark.slug, mark.reference, () => answer.data) };
          });
        } catch (error) {
          // No signal: keep the mark for later.
          if (!(error instanceof ApiError) || error.status === 0) break;
          // The server refused it (e.g. the booking was cancelled meanwhile): undo it and say why.
          update((current) => ({
            ...current,
            pending: current.pending.slice(1),
            lists: changeItem(current.lists, mark.slug, mark.reference, (item) => ({ ...item, attendance: mark.previous })),
            refused: { ...current.refused, [mark.reference]: error.message },
          }));
        }
      }
    } finally {
      sending.current = false;
    }
  }, [update, owner]);

  const mark = useCallback(
    (slug: string, reference: string, attendance: AttendanceValue) => {
      update((current) => {
        const previous = current.lists[slug]?.items.find((item) => item.reference === reference)?.attendance ?? null;
        const refused = { ...current.refused };
        delete refused[reference];
        return {
          ...current,
          pending: [...current.pending, { slug, reference, attendance, previous }],
          lists: changeItem(current.lists, slug, reference, (item) => ({ ...item, attendance })),
          refused,
        };
      });
      void send();
    },
    [update, send],
  );

  const saveList = useCallback(
    (slug: string, items: CheckIn[]) =>
      update((current) => {
        // Marks not sent yet stay visible over the fresh list.
        const waiting = new Map(current.pending.filter((entry) => entry.slug === slug).map((entry) => [entry.reference, entry.attendance]));
        const merged = items.map((item) => (waiting.has(item.reference) ? { ...item, attendance: waiting.get(item.reference) ?? null } : item));
        return { ...current, lists: { ...current.lists, [slug]: { items: merged, savedAt: new Date().toISOString() } } };
      }),
    [update],
  );

  const value = useMemo<CheckInState>(
    () => ({ lists: mine.lists, pending: mine.pending, refused: mine.refused, saveList, mark, send }),
    [mine.lists, mine.pending, mine.refused, saveList, mark, send],
  );

  return <CheckInContext.Provider value={value}>{children}</CheckInContext.Provider>;
}

export function useCheckIn(): CheckInState {
  const state = useContext(CheckInContext);
  if (!state) throw new Error('useCheckIn() must be used inside <CheckInProvider>.');

  return state;
}

/** What a member's booking QR code holds: a prefix (so other QR codes are ignored) and the booking reference. */
export const QR_PREFIX = 'hikingclub-booking:';

export function referenceFromQr(data: string): string | null {
  return data.startsWith(QR_PREFIX) ? data.slice(QR_PREFIX.length).trim().toUpperCase() || null : null;
}
