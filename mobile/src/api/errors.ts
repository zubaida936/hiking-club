import { ApiError } from '@/api/client';
import { strings } from '@/strings';

/** Validation messages per field (shown under each field), empty when the error isn't a validation error. */
export function fieldErrors(error: unknown): Record<string, string[]> {
  return error instanceof ApiError ? error.errors : {};
}

/**
 * The message for the whole form: a club rule ("This code is wrong or has expired.") or a network problem.
 * Null for validation errors, which are shown under their fields instead.
 */
export function formMessage(error: unknown): string | null {
  if (!error) return null;
  if (!(error instanceof ApiError)) return strings.errors.generic;

  return Object.keys(error.errors).length > 0 ? null : error.message;
}
