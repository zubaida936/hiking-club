<?php

use App\Domain\Identity\Enums\Role;
use App\Models\User;

it('shows the CMS login page to guests', function () {
    $this->get(route('admin.login'))->assertOk()->assertSee('Sign in');
});

it('redirects guests away from the CMS', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('logs staff in with email and password and records the login time', function () {
    $user = userWithRole(Role::Admin, ['email' => 'admin@example.com']);

    $this->post(route('admin.login.store'), ['email' => 'ADMIN@example.com', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password', function () {
    userWithRole(Role::Admin, ['email' => 'admin@example.com']);

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects inactive staff', function () {
    userWithRole(Role::Admin, ['email' => 'admin@example.com', 'is_active' => false]);

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects members without CMS access', function () {
    userWithRole(Role::Member, ['email' => 'member@example.com']);

    $this->post(route('admin.login.store'), ['email' => 'member@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks the login after 5 failed attempts', function () {
    userWithRole(Role::Admin, ['email' => 'admin@example.com']);

    foreach (range(1, 5) as $_) {
        $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'wrong']);
    }

    // Even the correct password is refused while locked out.
    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out', function () {
    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

it('kicks out a user deactivated mid-session', function () {
    $user = userWithRole(Role::Admin);
    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('shows the dashboard to every staff role', function (Role $role) {
    $this->actingAs(userWithRole($role))->get(route('admin.dashboard'))->assertOk();
})->with(Role::staff());

it('blocks a logged-in member from the CMS', function () {
    $this->actingAs(User::factory()->withRole(Role::Member)->create())
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});
