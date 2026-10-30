import { ThemedText } from '@/components/themed-text';
import { formatMoment } from '@/format';
import { strings } from '@/strings';

/**
 * Under a screen that shows saved data because the last refresh failed (no signal, server down): when it was saved.
 * Nothing when the data is fresh.
 */
export function SavedCopyNote({ error, updatedAt }: { error: Error | null; updatedAt: number }) {
  if (!error || updatedAt === 0) return null;

  return (
    <ThemedText type="small" themeColor="textSecondary">
      {strings.errors.savedCopy(formatMoment(new Date(updatedAt).toISOString()))}
    </ThemedText>
  );
}
