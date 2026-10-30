import AsyncStorage from '@react-native-async-storage/async-storage';
import { useCallback, useEffect, useState } from 'react';

const KEY = 'hiking.equipment.packed';

/** Ticked items per adventure: { hiking: ['Water', ...] }. */
type Packed = Record<string, string[]>;

/**
 * The equipment checklist's ticks, kept on the phone (AsyncStorage) so they're still there next time. Not personal
 * data: they stay after logging out. Items are remembered by name, so a renamed item simply shows unticked.
 */
export function usePacked() {
  const [packed, setPacked] = useState<Packed>({});

  useEffect(() => {
    let active = true;
    AsyncStorage.getItem(KEY)
      .then((stored) => {
        if (active && stored) setPacked(JSON.parse(stored) as Packed);
      })
      .catch(() => undefined);
    return () => {
      active = false;
    };
  }, []);

  const save = useCallback((next: Packed) => {
    setPacked(next);
    void AsyncStorage.setItem(KEY, JSON.stringify(next)).catch(() => undefined);
  }, []);

  const toggle = useCallback(
    (adventure: string, item: string) => {
      const current = packed[adventure] ?? [];
      save({ ...packed, [adventure]: current.includes(item) ? current.filter((name) => name !== item) : [...current, item] });
    },
    [packed, save],
  );

  const clear = useCallback((adventure: string) => save({ ...packed, [adventure]: [] }), [packed, save]);

  return { packed, toggle, clear };
}
