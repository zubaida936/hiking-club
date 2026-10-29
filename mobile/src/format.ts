/**
 * Formatting for what the API sends as raw values (dates, km, hours), the same way the website shows them.
 * Dates are formatted by hand: toLocaleDateString() differs between phones and JavaScript engines
 * (e.g. "Sunday, 2 November" vs "Sunday 2 November").
 */

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

/** "2026-11-02" → "Sun, 2 Nov" (short) or "Sunday, 2 November 2026" (long). Dates are the club's days, not times. */
export function formatDay(isoDate: string, style: 'short' | 'long' = 'short'): string {
  const [year, month, day] = isoDate.split('-').map(Number);
  const weekday = DAYS[new Date(Date.UTC(year, month - 1, day)).getUTCDay()];

  return style === 'long'
    ? `${weekday}, ${day} ${MONTHS[month - 1]} ${year}`
    : `${weekday.slice(0, 3)}, ${day} ${MONTHS[month - 1].slice(0, 3)}`;
}

/** An ISO date-time → "1 Nov, 23:59" in the phone's time zone. */
export function formatMoment(isoDateTime: string): string {
  const date = new Date(isoDateTime);
  const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

  return `${date.getDate()} ${MONTHS[date.getMonth()].slice(0, 3)}, ${time}`;
}

/** 12.5 → "12.5 km", 8 → "8 km" (one decimal at most, like the website). */
export function formatKm(km: number): string {
  return `${Number(km.toFixed(1))} km`;
}

/** 6.5 → "6.5 h". */
export function formatHours(hours: number): string {
  return `${Number(hours.toFixed(1))} h`;
}

/** "First paragraph.\n\nSecond." → ["First paragraph.", "Second."] (the API's descriptions are plain text). */
export function paragraphs(text: string | null): string[] {
  return (text ?? '')
    .split(/\n\s*\n/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean);
}
