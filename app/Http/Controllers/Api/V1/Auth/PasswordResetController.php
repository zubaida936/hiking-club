<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Members\Actions\ResetPassword;
use App\Domain\Members\Codes\OneTimeCodes;
use App\Domain\Members\Exceptions\MemberException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Forgot password: send a code to the member, then set a new password with it (same as the website).
 * The answer is the same whether or not the number has an account.
 */
class PasswordResetController extends Controller
{
    use IssuesTokens;

    /**
     * Send a reset code.
     *
     * @unauthenticated
     */
    public function send(Request $request, OneTimeCodes $codes): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'regex:'.Phone::PATTERN]], [
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
        ]);

        $user = User::where('phone', Phone::normalize($request->input('phone')))->where('is_active', true)->first();

        if ($user !== null && $codes->canReach($user)) {
            $codes->issue($user, OneTimeCodes::PASSWORD_RESET);
        }

        return response()->json([
            'message' => 'If this number has an account, we sent it a code. It is valid for '.config('members.code_ttl_minutes').' minutes.',
        ], 202);
    }

    /**
     * Set a new password with the code.
     *
     * Logs out every other device and returns a token for this one, like login.
     *
     * @unauthenticated
     */
    public function reset(Request $request, ResetPassword $action): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min((int) config('members.password_min_length'))],
            'device_name' => ['required', 'string', 'max:100'],
        ], ['phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.']);

        $user = User::where('phone', Phone::normalize($request->input('phone')))->where('is_active', true)->first()
            ?? throw MemberException::invalidCode();

        $action->handle($user, $request->string('code')->toString(), $request->string('password')->toString());

        return $this->tokenResponse($user, $request->string('device_name')->toString());
    }
}
