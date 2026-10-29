<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Site\Auth\MemberLoginRequest;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;

/**
 * App login: same rules, error message and attempt limit as the website login, but without a session.
 */
class TokenLoginRequest extends MemberLoginRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'password' => ['required', 'string'],
            // Shown to the member as the name of the login, e.g. "Rima's Pixel 8".
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * The member whose credentials matched, once authenticate() passed.
     */
    public function member(): User
    {
        /** @var User */
        return Auth::guard('web')->user();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function attempt(array $credentials): bool
    {
        return Auth::guard('web')->once($credentials);
    }
}
