<?php

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;

it('gives super admin every permission', function () {
    expect(Role::SuperAdmin->permissions())->toBe(Permission::cases());
});

it('keeps staff management for super admins only', function (Role $role) {
    expect($role->permissions())->not->toContain(Permission::ManageStaff);
})->with([Role::Admin, Role::Editor, Role::Guide, Role::Member]);

it('gives CMS access to every staff role and not to members', function () {
    foreach (Role::staff() as $role) {
        expect($role->permissions())->toContain(Permission::AccessAdmin);
    }

    expect(Role::Member->permissions())->toBeEmpty();
});
