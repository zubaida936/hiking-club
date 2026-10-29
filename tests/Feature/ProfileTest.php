<?php

use App\Domain\Identity\Enums\Role;
use App\Models\Event;
use App\Models\Hiker;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'password' => 'secret-pass']);
    $this->user->assignRole(Role::Member->value);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com', 'user_id' => $this->user->id]);
});

describe('in the app (API)', function () {
    beforeEach(fn () => $this->token = $this->user->createToken('Phone')->plainTextToken);

    it('updates name and emergency contact, on the account and the hiker', function () {
        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), [
            'name' => '  Rima   Haddad-Khoury ',
            'email' => 'rima@example.com',
            'emergency_contact_name' => 'Karim Haddad',
            'emergency_contact_phone' => '+961 3 111 222',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Rima Haddad-Khoury')
            ->assertJsonPath('data.emergency_contact', ['name' => 'Karim Haddad', 'phone' => '+9613111222']);

        expect($this->user->fresh()->name)->toBe('Rima Haddad-Khoury')
            ->and($this->hiker->fresh())->name->toBe('Rima Haddad-Khoury')->emergency_contact_phone->toBe('+9613111222');
    });

    it('needs the current password to change the email', function () {
        $profile = ['name' => 'Rima Haddad', 'email' => 'new@example.com'];

        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), $profile)
            ->assertJsonValidationErrors(['current_password' => 'Enter your password to change your email.']);
        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), $profile + ['current_password' => 'wrong'])
            ->assertJsonValidationErrors(['current_password' => 'This password is not right.']);
        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), $profile + ['current_password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');

        expect($this->hiker->fresh()->email)->toBe('new@example.com');
    });

    it('validates like the booking form', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), [
            'name' => '',
            'email' => 'taken@example.com',
            'current_password' => 'secret-pass',
            'emergency_contact_name' => 'Me',
            'emergency_contact_phone' => '+961 70 123 456',
        ])
            ->assertJsonValidationErrors([
                'name',
                'email' => 'Another account already uses this email.',
                'emergency_contact_phone' => 'Your emergency contact needs a different phone number than yours.',
            ]);

        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), ['name' => 'Rima', 'email' => 'rima@example.com', 'emergency_contact_phone' => '+9613111222'])
            ->assertJsonValidationErrors(['emergency_contact_name' => 'Enter the name of your emergency contact.']);
    });

    it('fills in the booking form with the saved emergency contact', function () {
        $this->hiker->update(['emergency_contact_name' => 'Karim Haddad', 'emergency_contact_phone' => '+9613111222']);
        $event = Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString()]);

        $this->withToken($this->token)->getJson(route('api.v1.events.booking-form', $event))
            ->assertJsonPath('data.member.emergency_contact_name', 'Karim Haddad')
            ->assertJsonPath('data.member.emergency_contact_phone', '+9613111222');
    });

    it('never changes the phone number', function () {
        $this->withToken($this->token)->putJson(route('api.v1.me.profile'), ['name' => 'Rima', 'email' => 'rima@example.com', 'phone' => '+96171999999'])->assertOk();

        expect($this->user->fresh()->phone)->toBe('+96170123456')->and($this->hiker->fresh()->phone)->toBe('+96170123456');
    });
});

describe('on the website', function () {
    it('edits the profile from My account', function () {
        $this->actingAs($this->user)->get(route('account'))->assertSee(route('account.profile'));
        $this->get(route('account.profile'))->assertOk()->assertSee('+96170123456')->assertSee('message us on WhatsApp');

        $this->post(route('account.profile.update'), [
            'name' => 'Rima Khoury', 'email' => 'rima@example.com', 'emergency_contact_name' => 'Karim', 'emergency_contact_phone' => '+961 3 111 222',
        ])->assertRedirect(route('account'))->assertSessionHas('success', 'Your profile is saved.');

        expect($this->hiker->fresh())->name->toBe('Rima Khoury')->emergency_contact_name->toBe('Karim');
    });

    it('fills in the website booking form with the saved emergency contact', function () {
        $this->hiker->update(['emergency_contact_name' => 'Karim Haddad', 'emergency_contact_phone' => '+9613111222']);
        $event = Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString()]);

        $this->actingAs($this->user)->get(route('events.register', $event))->assertSee('value="Karim Haddad"', false);
    });

    it('needs a login', function () {
        $this->get(route('account.profile'))->assertRedirect(route('login'));
    });
});
