<?php

use App\Domain\Identity\Enums\Role;
use App\Models\User;

function validStaffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rami Guide',
        'email' => 'rami@example.com',
        'phone' => '+961 70 111 222',
        'role' => Role::Guide->value,
        'is_active' => '1',
        'password' => 'Mountain2026',
        'password_confirmation' => 'Mountain2026',
    ], $overrides);
}

beforeEach(function () {
    $this->superAdmin = userWithRole(Role::SuperAdmin);
});

it('lists staff only, not members', function () {
    $guide = userWithRole(Role::Guide, ['name' => 'Guide Person']);
    userWithRole(Role::Member, ['name' => 'Member Person']);

    $this->actingAs($this->superAdmin)->get(route('admin.staff.index'))
        ->assertOk()
        ->assertSee($guide->name)
        ->assertDontSee('Member Person');
});

it('filters staff by search term and role', function () {
    userWithRole(Role::Guide, ['name' => 'Alpha Guide']);
    userWithRole(Role::Editor, ['name' => 'Beta Editor']);

    $this->actingAs($this->superAdmin)->get(route('admin.staff.index', ['q' => 'Alpha']))
        ->assertSee('Alpha Guide')->assertDontSee('Beta Editor');

    $this->actingAs($this->superAdmin)->get(route('admin.staff.index', ['role' => 'editor']))
        ->assertSee('Beta Editor')->assertDontSee('Alpha Guide');
});

it('creates a staff user with a role', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.staff.store'), validStaffPayload())
        ->assertRedirect(route('admin.staff.index'))
        ->assertSessionHas('success');

    $user = User::where('email', 'rami@example.com')->firstOrFail();
    expect($user->hasRole('guide'))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

it('validates staff input', function (array $overrides, string $field) {
    userWithRole(Role::Editor, ['email' => 'taken@example.com']);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.staff.store'), validStaffPayload($overrides))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'bad email' => [['email' => 'nope'], 'email'],
    'duplicate email' => [['email' => 'taken@example.com'], 'email'],
    'bad phone' => [['phone' => 'abc'], 'phone'],
    'member role not allowed' => [['role' => 'member'], 'role'],
    'weak password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'Different2026'], 'password'],
]);

it('updates a staff user and keeps the password when left empty', function () {
    $user = userWithRole(Role::Guide);
    $oldHash = $user->password;

    $this->actingAs($this->superAdmin)
        ->put(route('admin.staff.update', $user), validStaffPayload([
            'email' => $user->email,
            'role' => Role::Editor->value,
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertRedirect(route('admin.staff.index'));

    $user->refresh();
    expect($user->hasRole('editor'))->toBeTrue()
        ->and($user->hasRole('guide'))->toBeFalse()
        ->and($user->password)->toBe($oldHash);
});

it('deletes a staff user (soft delete)', function () {
    $user = userWithRole(Role::Editor);

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.staff.destroy', $user))
        ->assertRedirect(route('admin.staff.index'));

    $this->assertSoftDeleted($user);
});

it('prevents deleting yourself', function () {
    $this->actingAs($this->superAdmin)
        ->delete(route('admin.staff.destroy', $this->superAdmin))
        ->assertSessionHas('error');

    $this->assertNotSoftDeleted($this->superAdmin);
});

it('prevents demoting or deactivating the last super admin', function (array $overrides) {
    $admin = userWithRole(Role::Admin);
    $admin->givePermissionTo('staff.manage');

    $this->actingAs($admin)
        ->put(route('admin.staff.update', $this->superAdmin), validStaffPayload(array_merge([
            'email' => $this->superAdmin->email,
            'password' => '',
            'password_confirmation' => '',
        ], $overrides)))
        ->assertSessionHas('error');

    expect($this->superAdmin->fresh()->hasRole('super-admin'))->toBeTrue();
})->with([
    'demote' => [['role' => 'admin']],
    'deactivate' => [['role' => 'super-admin', 'is_active' => '0']],
]);

it('returns 404 when trying to manage a member through staff routes', function () {
    $member = userWithRole(Role::Member);

    $this->actingAs($this->superAdmin)->get(route('admin.staff.edit', $member))->assertNotFound();
});

it('forbids roles without staff.manage', function (Role $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.staff.index'))
        ->assertForbidden();
})->with([Role::Admin, Role::Editor, Role::Guide]);
