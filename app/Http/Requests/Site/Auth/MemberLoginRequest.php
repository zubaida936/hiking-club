<?php

namespace App\Http\Requests\Site\Auth;

use App\Support\Phone;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MemberLoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.'];
    }

    /**
     * Same generic error for unknown numbers, wrong passwords and deactivated accounts.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'phone' => (string) Phone::normalize($this->input('phone')),
            'password' => $this->string('password')->toString(),
            'is_active' => true,
        ];

        if (! $this->attempt($credentials)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['phone' => "This phone number and password don't match an account."]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Website: log in with a session.
     *
     * @param  array<string, mixed>  $credentials
     */
    protected function attempt(array $credentials): bool
    {
        return Auth::attempt($credentials, $this->boolean('remember'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'phone' => __('auth.throttle', [
                'seconds' => $seconds = RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return 'member-login|'.Phone::normalize($this->input('phone')).'|'.$this->ip();
    }
}
