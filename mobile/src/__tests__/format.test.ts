import { formatDay, formatHours, formatKm, formatMoment, paragraphs } from '@/format';

it('formats the club days the same on every phone', () => {
  expect(formatDay('2026-11-02')).toBe('Mon, 2 Nov');
  expect(formatDay('2026-11-02', 'long')).toBe('Monday, 2 November 2026');
  expect(formatDay('2027-01-01', 'long')).toBe('Friday, 1 January 2027');
});

it('formats a moment in the phone time zone', () => {
  const date = new Date(2026, 10, 1, 23, 5);

  expect(formatMoment(date.toISOString())).toBe('1 Nov, 23:05');
});

it('formats distances, durations and paragraphs like the website', () => {
  expect(formatKm(12.5)).toBe('12.5 km');
  expect(formatKm(8)).toBe('8 km');
  expect(formatKm(14.04)).toBe('14 km');
  expect(formatHours(6.5)).toBe('6.5 h');
  expect(paragraphs('First.\n\n  Second.\n\n\n')).toEqual(['First.', 'Second.']);
  expect(paragraphs(null)).toEqual([]);
});
