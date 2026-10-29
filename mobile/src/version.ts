/**
 * Compares app versions like "1.10.2" part by part (numbers, not text: 1.10 is newer than 1.9).
 * Returns a negative number when a < b, 0 when equal, positive when a > b.
 */
export function compareVersions(a: string, b: string): number {
  const left = a.split('.').map((part) => Number.parseInt(part, 10) || 0);
  const right = b.split('.').map((part) => Number.parseInt(part, 10) || 0);

  for (let i = 0; i < Math.max(left.length, right.length); i++) {
    const diff = (left[i] ?? 0) - (right[i] ?? 0);
    if (diff !== 0) return diff;
  }

  return 0;
}

/** True when the installed app is older than the oldest version the API still supports. */
export function mustUpdate(installed: string, minVersion: string): boolean {
  return compareVersions(installed, minVersion) < 0;
}
