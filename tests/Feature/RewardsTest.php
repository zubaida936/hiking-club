<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Rewards\HikerRewards;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $this->user->assignRole(Role::Member->value);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'user_id' => $this->user->id]);
    $this->event = Event::factory()->published()->withMeetingPoints(1)->create([
        'title' => 'Chouf Cedars Ridge', 'fee_cents' => 3000, 'difficulty_level' => DifficultyLevel::Endurance,
    ]);
});

function givePoints(Hiker $hiker, int $points): void
{
    $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);
}

function completeHikes(Hiker $hiker, int $count): void
{
    foreach (range(1, $count) as $i) {
        EventRegistration::factory()->for(Event::factory()->published()->past())->create([
            'hiker_id' => $hiker->id, 'phone' => $hiker->phone, 'attendance' => Attendance::Attended,
        ]);
    }
}

function bookWithReward(Event $event, ?string $reward, array $overrides = []): TestResponse
{
    return test()->post(route('events.register.store', $event), array_merge([
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'meeting_point_id' => $event->meetingPoints()->value('id'),
        'emergency_contact_name' => 'Karim',
        'emergency_contact_phone' => '+961 3 000 000',
        'terms' => '1',
        'level_acknowledged' => '1',
        'payment_method' => 'cash',
        'reward' => $reward,
    ], $overrides));
}

describe('unlocking', function () {
    it('unlocks rewards by rank and extra conditions', function () {
        givePoints($this->hiker, 90); // Trekker
        $states = collect(app(HikerRewards::class)->statesFor($this->hiker))->keyBy(fn ($s) => $s['reward']->key);

        expect($states['trekker_half_off'])->unlocked->toBeFalse()->locked_reason->toBe('After 5 completed hikes (0 so far)')
            ->and($states['mountaineer_free_moderate'])->unlocked->toBeFalse()->locked_reason->toBe('Reach Mountaineer (200 points)');

        completeHikes($this->hiker, 5);
        expect(app(HikerRewards::class)->discountsFor($this->hiker, $this->event))->toHaveCount(1);
    });

    it('adds the Mountaineer +5 bonus once', function () {
        $admin = userWithRole(Role::Admin);

        $this->actingAs($admin)->post(route('admin.points.adjust', $this->hiker), ['points' => 200, 'reason' => 'Test']);
        expect($this->hiker->totalPoints())->toBe(205);

        $this->actingAs($admin)->post(route('admin.points.adjust', $this->hiker), ['points' => 10, 'reason' => 'More']);
        $this->artisan('points:sync')->assertSuccessful();
        expect($this->hiker->totalPoints())->toBe(215)
            ->and($this->hiker->pointTransactions()->where('type', PointType::RankBonus)->sole()->description)->toBe('Mountaineer rank bonus');
    });
});

describe('using a discount when booking', function () {
    beforeEach(function () {
        givePoints($this->hiker, 250); // Mountaineer: free Moderate (Level 2) hike
    });

    it('offers the reward on the booking form and makes the booking free', function () {
        $this->actingAs($this->user)->get(route('events.register', $this->event))
            ->assertSee('Use a reward?')
            ->assertSee('One free Moderate (Level 2) hike');

        $this->actingAs($this->user);
        bookWithReward($this->event, 'mountaineer_free_moderate', ['payment_method' => ''])->assertSessionHasNoErrors();

        $booking = EventRegistration::latest('id')->firstOrFail();
        expect($booking)->amount_due_cents->toBe(0)->payment_method->toBeNull()
            ->and($booking->amountDueCents())->toBe(0)
            ->and(RewardRedemption::where('reward', 'mountaineer_free_moderate')->sole())
            ->event_registration_id->toBe($booking->id)->discount_cents->toBe(3000);

        $this->get(route('registrations.show', $booking))->assertDontSee('Payment ·');
        $this->get(route('account'))->assertSee('Free with your reward');

        // Used: no longer offered.
        $other = Event::factory()->published()->create(['fee_cents' => 3000, 'difficulty_level' => DifficultyLevel::Endurance]);
        $this->get(route('events.register', $other))->assertDontSee('Use a reward?');
    });

    it('applies percentage discounts to the amount to pay', function () {
        givePoints($this->hiker, 200); // Highlander (450): 15% off Level 3
        $hard = Event::factory()->published()->withMeetingPoints(1)->create(['fee_cents' => 4000, 'difficulty_level' => DifficultyLevel::Advanced]);
        $this->actingAs($this->user);

        bookWithReward($hard, 'highlander_challenging_15')->assertSessionHasNoErrors();

        $booking = EventRegistration::latest('id')->firstOrFail();
        expect($booking->amount_due_cents)->toBe(3400);
        $this->get(route('registrations.show', $booking))->assertSee('Payment · $34')->assertSee('in cash');
        $this->actingAs(userWithRole(Role::Admin))->get(route('admin.events.registrations', $hard))
            ->assertSee('Reward: pays $34 instead of $40');
    });

    it('refuses rewards that are used up, not for this hike, or not the member\'s own booking', function () {
        $this->actingAs($this->user);
        $hard = Event::factory()->published()->withMeetingPoints(1)->create(['difficulty_level' => DifficultyLevel::Advanced]);

        bookWithReward($hard, 'mountaineer_free_moderate')
            ->assertSessionHas('error', "This reward can't be used on this hike.");
        bookWithReward($this->event, 'mountaineer_free_moderate', ['phone' => '+96171999999'])
            ->assertSessionHas('error', 'Rewards can only be used on a booking with your own phone number.');
        bookWithReward($this->event, 'elite_free_hike')
            ->assertSessionHas('error', 'This reward is not available to you (not unlocked yet, or already used).');

        $this->post(route('logout'));
        bookWithReward($this->event, 'mountaineer_free_moderate')
            ->assertSessionHas('error', 'Rewards can only be used on a booking with your own phone number.');
        expect(RewardRedemption::where('reward', 'mountaineer_free_moderate')->count())->toBe(0);
    });

    it('gives the reward back when the booking is cancelled', function () {
        $this->actingAs($this->user);
        bookWithReward($this->event, 'mountaineer_free_moderate', ['payment_method' => '']);
        $booking = EventRegistration::latest('id')->firstOrFail();

        $this->post(route('account.bookings.cancel', $booking))->assertSessionHas('success');

        expect(RewardRedemption::where('reward', 'mountaineer_free_moderate')->sole()->released_at)->not->toBeNull()
            ->and(app(HikerRewards::class)->discountsFor($this->hiker, $this->event))->toHaveCount(1);
    });
});

it('confirms Elite members on X events straight away', function () {
    givePoints($this->hiker, 750);
    $x = Event::factory()->published()->withMeetingPoints(1)->create(['is_xtreme' => true, 'difficulty_level' => DifficultyLevel::Xtreme]);

    $this->actingAs($this->user);
    bookWithReward($x, null)->assertSessionHasNoErrors();

    expect(EventRegistration::latest('id')->first()->status)->toBe(RegistrationStatus::Confirmed);
    $this->get(route('account'))->assertSee('your X event bookings are confirmed right away');
});

it('lets organizers record handed-over perks once', function () {
    givePoints($this->hiker, 450); // Highlander: gift card
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->get(route('admin.points.show', $this->hiker))->assertSee('Gift card')->assertSee('Mark handed over');
    $this->actingAs($admin)->post(route('admin.points.rewards.hand-over', $this->hiker), ['reward' => 'highlander_gift_card', 'note' => 'Given at Qadisha'])
        ->assertSessionHas('success', 'Reward marked as handed over.');
    $this->actingAs($admin)->post(route('admin.points.rewards.hand-over', $this->hiker), ['reward' => 'highlander_gift_card'])
        ->assertSessionHas('error', 'This reward is not available to you (not unlocked yet, or already used).');
    $this->actingAs($admin)->post(route('admin.points.rewards.hand-over', $this->hiker), ['reward' => 'elite_free_hike'])
        ->assertSessionHas('error', 'Only gift-type rewards are handed over by organizers.');

    expect(RewardRedemption::sole())->note->toBe('Given at Qadisha')->created_by->toBe($admin->id);
    $this->actingAs($admin)->get(route('admin.points.show', $this->hiker))->assertSee('Given at Qadisha');
    $this->actingAs($this->user)->get(route('account'))->assertSee('Gift card');
});
