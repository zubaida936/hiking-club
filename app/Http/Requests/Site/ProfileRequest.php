<?php

namespace App\Http\Requests\Site;

use App\Domain\Members\Data\ProfileData;
use App\Models\User;
use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * My profile (website My account and the app's PUT /me/profile). Changing the email needs the current
 * password: password-reset codes go to that email.
 */
class ProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($this->member()->id)],
            'emergency_contact_name' => ['nullable', 'string', 'max:120', 'required_with:emergency_contact_phone'],
            'emergency_contact_phone' => ['nullable', 'string', 'regex:'.Phone::PATTERN, 'required_with:emergency_contact_name'],
            // Only when the email changes.
            'current_password' => [Rule::requiredIf($this->emailChanges()), 'nullable', 'string', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another account already uses this email.',
            'emergency_contact_phone.regex' => 'Enter a valid phone number, e.g. +961 3 123 456.',
            'emergency_contact_name.required_with' => 'Enter the name of your emergency contact.',
            'emergency_contact_phone.required_with' => 'Enter the phone number of your emergency contact.',
            'current_password.required' => 'Enter your password to change your email.',
            'current_password.current_password' => 'This password is not right.',
        ];
    }

    /**
     * A different emergency phone than the member's own (same rule as the booking form).
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $own = $this->member()->phone;
            if ($own !== null && Phone::normalize($this->input('emergency_contact_phone')) === $own) {
                $validator->errors()->add('emergency_contact_phone', 'Your emergency contact needs a different phone number than yours.');
            }
        }];
    }

    public function toData(): ProfileData
    {
        $text = fn (string $key) => $this->filled($key) ? $this->string($key)->squish()->toString() : null;

        return new ProfileData(
            name: $this->string('name')->squish()->toString(),
            email: $this->emailInput(),
            emergencyContactName: $text('emergency_contact_name'),
            emergencyContactPhone: Phone::normalize($this->input('emergency_contact_phone')),
        );
    }

    private function emailInput(): ?string
    {
        $email = strtolower(trim((string) $this->input('email')));

        return $email !== '' ? $email : null;
    }

    private function emailChanges(): bool
    {
        return $this->emailInput() !== $this->member()->email;
    }

    private function member(): User
    {
        /** @var User $user */
        $user = $this->user();

        return $user;
    }
}
