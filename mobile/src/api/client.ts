import { getToken } from '@/auth/token';
import { API_URL } from '@/config';
import { strings } from '@/strings';

/**
 * An error answer from the API. `message` is always safe to show: the API sends user-facing texts
 * (validation and club rules), and network problems get a friendly text here.
 */
export class ApiError extends Error {
  constructor(
    message: string,
    /** HTTP status; 0 when the server couldn't be reached. */
    readonly status: number,
    /** Validation errors per field (422), e.g. { phone: ['Enter a valid phone number…'] }. */
    readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

type Options = {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined>;
};

/**
 * Calls the API (paths like '/events'), sending the login token when there is one.
 * Resolves with the JSON answer; rejects with an ApiError.
 */
export async function apiFetch<T>(path: string, { method = 'GET', body, query }: Options = {}): Promise<T> {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value !== undefined) params.append(key, String(value));
  }
  const url = `${API_URL}${path}${params.size > 0 ? `?${params}` : ''}`;

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const token = await getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  let response: Response;
  try {
    response = await fetch(url, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
  } catch {
    throw new ApiError(strings.errors.offline, 0);
  }

  if (response.status === 204) return undefined as T;

  const json: unknown = await response.json().catch(() => null);
  if (!response.ok) {
    const data = (json ?? {}) as { message?: string; errors?: Record<string, string[]> };
    throw new ApiError(data.message ?? strings.errors.generic, response.status, data.errors ?? {});
  }

  return json as T;
}
