import { ApiError, apiFetch } from '@/api/client';
import { getToken } from '@/auth/token';
import { strings } from '@/strings';

jest.mock('@/auth/token', () => ({ getToken: jest.fn() }));

const mockedGetToken = jest.mocked(getToken);

function answer(status: number, body: unknown) {
  return Promise.resolve(new Response(body === undefined ? null : JSON.stringify(body), { status }));
}

beforeEach(() => {
  mockedGetToken.mockResolvedValue(null);
  global.fetch = jest.fn();
});

it('sends JSON with the login token and returns the answer', async () => {
  mockedGetToken.mockResolvedValue('secret-token');
  jest.mocked(fetch).mockReturnValue(answer(201, { data: { reference: 'K7F3Q2MZ' } }));

  const result = await apiFetch<{ data: { reference: string } }>('/events/x/bookings', { method: 'POST', body: { terms: true } });

  expect(result.data.reference).toBe('K7F3Q2MZ');
  const [url, init] = jest.mocked(fetch).mock.calls[0];
  expect(url).toMatch(/\/events\/x\/bookings$/);
  expect(init?.headers).toMatchObject({ Accept: 'application/json', Authorization: 'Bearer secret-token', 'Content-Type': 'application/json' });
  expect(init?.body).toBe('{"terms":true}');
});

it('adds query parameters and leaves out undefined ones', async () => {
  jest.mocked(fetch).mockReturnValue(answer(200, { data: [] }));

  await apiFetch('/events', { query: { level: 2, month: undefined } });

  expect(jest.mocked(fetch).mock.calls[0][0]).toMatch(/\/events\?level=2$/);
});

it('turns API errors into an ApiError with the message and field errors', async () => {
  jest.mocked(fetch).mockReturnValue(answer(422, { message: 'Enter a valid phone number.', errors: { phone: ['Enter a valid phone number.'] } }));

  const error = await apiFetch('/auth/login').catch((e: unknown) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect(error).toMatchObject({ message: 'Enter a valid phone number.', status: 422, errors: { phone: ['Enter a valid phone number.'] } });
});

it('gives a friendly message when the server cannot be reached', async () => {
  jest.mocked(fetch).mockRejectedValue(new TypeError('Network request failed'));

  await expect(apiFetch('/events')).rejects.toMatchObject({ status: 0, message: strings.errors.offline });
});

it('resolves empty answers (204)', async () => {
  jest.mocked(fetch).mockReturnValue(answer(204, undefined));

  await expect(apiFetch('/auth/logout', { method: 'POST' })).resolves.toBeUndefined();
});
