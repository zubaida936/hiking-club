<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Members\MemberLinks;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = userWithRole(Role::Admin);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'email' => 'rima@example.com']);
});

function memberAccount(Hiker $hiker, array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['name' => $hiker->name, 'phone' => $hiker->phone, 'password' => 'old-password']);
    $user->assignRole(Role::Member->value);
    $hiker->update(['user_id' => $user->id]);

    return $user;
}

describe('members list', function () {
    it('lists hikers with rank, hikes, last booking and account status', function () {
        EventRegistration::factory()->for(Event::factory()->published())->create(['hiker_id' => $this->hiker->id, 'phone' => $this->hiker->phone]);
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 90, 'description' => 'x']);
        $active = Hiker::factory()->create(['name' => 'Active Member']);
        memberAccount($active);
        $gone = Hiker::factory()->create(['name' => 'Gone Member']);
        memberAccount($gone, ['is_active' => false]);

        $this->actingAs($this->admin)->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Rima Haddad')->assertSee('Trekker')->assertSee('90 points')->assertSee('No account')->assertSee('Invite to activate')
            ->assertSee('Active Member')->assertSee('Password link')
            ->assertSee('Gone Member')->assertSee('Reactivate');

        $this->actingAs($this->admin)->get(route('admin.members.index', ['account' => 'none']))
            ->assertSee('Rima Haddad')->assertDontSee('Active Member');
        $this->actingAs($this->admin)->get(route('admin.members.index', ['account' => 'deactivated']))
            ->assertSee('Gone Member')->assertDontSee('Rima Haddad');
        $this->actingAs($this->admin)->get(route('admin.members.index', ['q' => '70 123']))
            ->assertSee('Rima Haddad')->assertDontSee('Gone Member');
    });

    it('is for staff who manage members', function () {
        $this->actingAs(userWithRole(Role::Guide))->get(route('admin.members.index'))->assertForbidden();
        $this->actingAs(userWithRole(Role::Editor))->post(route('admin.members.invite', $this->hiker))->assertForbidden();
    });
});

describe('invite to activate', function () {
    it('creates a WhatsApp-ready activation link that sets up the account', function () {
        $this->actingAs($this->admin)->post(route('admin.members.invite', $this->hiker))->assertSessionHas('memberLink');
        $link = session('memberLink');

        expect($link['whatsapp'])->toStartWith('https://wa.me/96170123456?text=')->toContain(rawurlencode($link['url']));
        $this->actingAs($this->admin)->get(route('admin.members.index'))->assertSee('Send on WhatsApp');

        $this->post(route('logout'));
        $this->get($link['url'])->assertOk()->assertSee('Hi Rima Haddad!');
        $this->post($link['url'], ['password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertRedirect(route('account'));

        $user = $this->hiker->fresh()->user;
        expect($user)->phone->toBe('+96170123456')->and(Hash::check('new-pass-123', $user->password))->toBeTrue()
            ->and($user->hasRole(Role::Member->value))->toBeTrue();
        $this->assertAuthenticatedAs($user);

        // Used: the link now sends to the login page.
        $this->post(route('logout'));
        $this->get($link['url'])->assertRedirect(route('login'));
    });

    it('rejects tampered or expired links', function () {
        $url = app(MemberLinks::class)->activation($this->hiker);
        $other = Hiker::factory()->create();

        $this->get(str_replace("/invite/{$this->hiker->id}", "/invite/{$other->id}", $url))->assertForbidden();
        $this->travel(8)->days();
        $this->get($url)->assertForbidden();
    });

    it('is not offered to hikers who already have an account', function () {
        memberAccount($this->hiker);

        $this->actingAs($this->admin)->post(route('admin.members.invite', $this->hiker))
            ->assertSessionHas('error', 'This hiker already has an account: send a password link instead.');
    });
});

describe('account management', function () {
    it('sends a one-time password link', function () {
        $user = memberAccount($this->hiker);

        $this->actingAs($this->admin)->post(route('admin.members.password-link', $this->hiker))->assertSessionHas('memberLink');
        $url = session('memberLink')['url'];
        $this->post(route('logout'));

        $this->get($url)->assertOk()->assertSee('Choose a new password');
        $this->post($url, ['password' => 'brand-new-1', 'password_confirmation' => 'brand-new-1'])->assertRedirect(route('account'));
        expect(Hash::check('brand-new-1', $user->fresh()->password))->toBeTrue();

        $this->post(route('logout'));
        $this->get($url)->assertRedirect(route('login'))
            ->assertSessionHas('error', 'This link was already used or is no longer valid. Ask us for a new one.');
    });

    it('deactivates and reactivates member accounts, and logs deactivated members out', function () {
        $user = memberAccount($this->hiker);

        $this->actingAs($user)->get(route('account'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.members.deactivate', $this->hiker))->assertSessionHas('success');
        expect($user->fresh()->is_active)->toBeFalse();

        $this->actingAs($user->fresh())->get(route('account'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), ['phone' => '+96170123456', 'password' => 'old-password'])->assertSessionHasErrors('phone');

        $this->actingAs($this->admin)->post(route('admin.members.reactivate', $this->hiker));
        $this->post(route('logout'));
        $this->post(route('login.store'), ['phone' => '+96170123456', 'password' => 'old-password'])->assertRedirect(route('account'));
    });

    it('leaves staff accounts to Staff & roles', function () {
        $staff = userWithRole(Role::Guide, ['phone' => '+96170123456']);
        $this->hiker->update(['user_id' => $staff->id]);

        $this->actingAs($this->admin)->get(route('admin.members.index'))->assertSee('Staff account');
        $this->actingAs($this->admin)->post(route('admin.members.deactivate', $this->hiker))
            ->assertSessionHas('error', 'This is a staff account: manage it in Staff & roles.');
        expect($staff->fresh()->is_active)->toBeTrue();
    });
});

it('shows the log of rewards used', function () {
    $event = Event::factory()->published()->create(['title' => 'Chouf Cedars Ridge']);
    $booking = EventRegistration::factory()->for($event)->create(['hiker_id' => $this->hiker->id, 'phone' => $this->hiker->phone, 'reference' => 'K7F3Q2MZ']);
    RewardRedemption::create(['hiker_id' => $this->hiker->id, 'reward' => 'mountaineer_free_moderate', 'event_registration_id' => $booking->id, 'discount_cents' => 3000]);
    RewardRedemption::create(['hiker_id' => $this->hiker->id, 'reward' => 'highlander_gift_card', 'note' => 'Given at the bus', 'created_by' => $this->admin->id]);
    RewardRedemption::create(['hiker_id' => $this->hiker->id, 'reward' => 'elite_free_hike', 'released_at' => now()]);

    $this->actingAs($this->admin)->get(route('admin.members.rewards'))
        ->assertOk()
        ->assertSee('One free Moderate (Level 2) hike')->assertSee('Chouf Cedars Ridge')->assertSee('K7F3Q2MZ · −$30.00')
        ->assertSee('Gift card')->assertSee('Given at the bus')
        ->assertSee('Given back (booking cancelled)');

    $this->actingAs($this->admin)->get(route('admin.members.rewards', ['reward' => 'highlander_gift_card']))
        ->assertSee('Given at the bus')->assertDontSee('Chouf Cedars Ridge');
});
