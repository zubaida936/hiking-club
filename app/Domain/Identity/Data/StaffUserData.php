<?php

namespace App\Domain\Identity\Data;

use App\Domain\Identity\Enums\Role;

final readonly class StaffUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public Role $role,
        public bool $isActive,
        public ?string $password = null,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, role: string, is_active?: bool, password?: ?string}  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: $input['name'],
            email: strtolower($input['email']),
            phone: $input['phone'] ?? null,
            role: Role::from($input['role']),
            isActive: (bool) ($input['is_active'] ?? false),
            password: $input['password'] ?? null,
        );
    }
}
