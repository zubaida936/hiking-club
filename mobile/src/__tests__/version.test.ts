import { canUpdate, compareVersions, mustUpdate } from '@/version';

describe('compareVersions', () => {
  it('compares numbers, not text', () => {
    expect(compareVersions('1.10.0', '1.9.0')).toBeGreaterThan(0);
    expect(compareVersions('1.2.0', '1.2.0')).toBe(0);
    expect(compareVersions('1.2', '1.2.0')).toBe(0);
    expect(compareVersions('0.9.9', '1.0.0')).toBeLessThan(0);
  });
});

describe('mustUpdate', () => {
  it('asks to update only below the minimum version', () => {
    expect(mustUpdate('1.0.0', '1.2.0')).toBe(true);
    expect(mustUpdate('1.2.0', '1.2.0')).toBe(false);
    expect(mustUpdate('2.0.0', '1.2.0')).toBe(false);
  });
});

describe('canUpdate', () => {
  it('suggests updating only when a newer version is out', () => {
    expect(canUpdate('1.0.0', '1.1.0')).toBe(true);
    expect(canUpdate('1.1.0', '1.1.0')).toBe(false);
    expect(canUpdate('1.2.0', '1.1.0')).toBe(false);
  });
});
