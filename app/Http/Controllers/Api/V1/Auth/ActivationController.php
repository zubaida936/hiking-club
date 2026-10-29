<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Members\Actions\ActivateAccount;
use App\Http\Controllers\Controller;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * The website's two activation steps in one call: prove the phone number with a booking reference
 * and choose a password. The app keeps both on one screen, so no state is kept between steps.
 */
class ActivationController extends Controller
{
    use IssuesTokens;

    /**
     * Activate an account.
     *
     * For hikers who booked a hike: the booking reference proves the number is theirs.
     * Returns a token, like login.
     *
     * @unauthenticated
     */
    public function store(Request $request, ActivateAccount $action): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'reference' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))],
            'device_name' => ['required', 'string', 'max:100'],
        ], ['phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.']);

        $hiker = $action->verify((string) Phone::normalize($request->input('phone')), $request->string('reference')->toString());
        $user = $action->handle($hiker, $request->string('password')->toString());

        return $this->tokenResponse($user, $request->string('device_name')->toString());
    }
}
