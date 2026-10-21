<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Data\StaffUserData;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Shared by create + update; password is required only on create.
 */
class StaffUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ]{6,20}$/', Rule::unique('users', 'phone')->ignore($user)],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::staff()))],
            'is_active' => ['boolean'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function toData(): StaffUserData
    {
        /** @var array{name: string, email: string, phone?: ?string, role: string, is_active?: bool, password?: ?string} $validated */
        $validated = $this->validated();

        return StaffUserData::fromArray($validated);
    }
}
