<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $this->user->assignRole(Role::Member->value);
    $this->hiker = Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'user_id' => $this->user->id]);
    $this->token = $this->user->createToken('Phone')->plainTextToken;
});

function apiMyBooking(Hiker $hiker, Event $event, array $attributes = []): EventRegistration
{
    return EventRegistration::factory()->for($event)->create($attributes + ['hiker_id' => $hiker->id, 'phone' => $hiker->phone]);
}

describe('my profile', function () {
    it('shows hikes completed, km and leaderboard place', function () {
        $hike = Event::factory()->published()->past()->create(['distance_km' => 12.5]);
        $registration = apiMyBooking($this->hiker, $hike, ['attendance' => Attendance::Attended]);
        $registration->pointTransactions()->create(['hiker_id' => $this->hiker->id, 'type' => PointType::Hike, 'points' => 20, 'description' => 'Hike']);

        $this->withToken($this->token)->getJson(route('api.v1.me'))
            ->assertJsonPath('data.hiker.points', 20)
            ->assertJsonPath('data.hiker.hikes_completed', 1)
            ->assertJsonPath('data.hiker.distance_km', 12.5)
            ->assertJsonPath('data.hiker.leaderboard_position', 1);
    });
});

describe('my bookings', function () {
    it('lists upcoming bookings soonest first, without cancelled or past ones', function () {
        $later = apiMyBooking($this->hiker, Event::factory()->published()->create(['title' => 'Later', 'starts_on' => now()->addDays(20)->toDateString()]));
        $soon = apiMyBooking($this->hiker, Event::factory()->published()->create(['title' => 'Soon', 'starts_on' => now()->addDays(4)->toDateString()]), ['status' => RegistrationStatus::Waitlisted]);
        apiMyBooking($this->hiker, Event::factory()->published()->create(['starts_on' => now()->addDays(8)->toDateString()]), ['status' => RegistrationStatus::Cancelled]);
        apiMyBooking($this->hiker, Event::factory()->published()->past()->create());
        EventRegistration::factory()->for(Event::factory()->published()->create(['starts_on' => now()->addDays(6)->toDateString()]))->create(); // someone else

        $this->withToken($this->token)->getJson(route('api.v1.me.bookings'))
            ->assertOk()
            ->assertJsonPath('data.*.event.title', ['Soon', 'Later'])
            ->assertJsonPath('data.0.status.key', 'waitlisted')
            ->assertJsonPath('data.0.token', $soon->token)
            ->assertJsonPath('data.1.reference', $later->reference)
            ->assertJsonPath('data.1.cancellation.can_cancel', true)
            ->assertJsonMissingPath('data.0.earned_points')
            ->assertJsonPath('meta.total', 2);
    });

    it('lists completed hikes newest first with the points each earned', function () {
        $older = apiMyBooking($this->hiker, Event::factory()->published()->create(['title' => 'Older', 'starts_on' => now()->subMonths(2)->toDateString()]), ['attendance' => Attendance::Attended]);
        apiMyBooking($this->hiker, Event::factory()->published()->create(['title' => 'Newer', 'starts_on' => now()->subDays(5)->toDateString()]), ['attendance' => Attendance::Attended]);
        apiMyBooking($this->hiker, Event::factory()->published()->past()->create(['title' => 'No-show']), ['attendance' => Attendance::NoShow]);
        $older->pointTransactions()->create(['hiker_id' => $this->hiker->id, 'type' => PointType::Hike, 'points' => 14, 'description' => 'Hike']);
        $older->pointTransactions()->create(['hiker_id' => Hiker::factory()->create()->id, 'type' => PointType::Referral, 'points' => 10, 'description' => 'Someone else']);

        $this->withToken($this->token)->getJson(route('api.v1.me.bookings', ['status' => 'completed']))
            ->assertOk()
            ->assertJsonPath('data.*.event.title', ['Newer', 'Older'])
            ->assertJsonPath('data.0.earned_points', 0)
            ->assertJsonPath('data.1.earned_points', 14);
    });

    it('rejects an unknown status and needs a login', function () {
        $this->withToken($this->token)->getJson(route('api.v1.me.bookings', ['status' => 'all']))->assertJsonValidationErrors('status');
        $this->app['auth']->forgetGuards();
        $this->withoutToken()->getJson(route('api.v1.me.bookings'))->assertUnauthorized();
    });
});

describe('my points', function () {
    it('lists the points ledger newest first', function () {
        $registration = apiMyBooking($this->hiker, Event::factory()->published()->past()->create(['title' => 'Qadisha Valley']), ['attendance' => Attendance::Attended]);
        $registration->pointTransactions()->create(['hiker_id' => $this->hiker->id, 'type' => PointType::Hike, 'points' => 14, 'description' => 'Completed Qadisha']);
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => -4, 'description' => 'Correction']);

        $this->withToken($this->token)->getJson(route('api.v1.me.points'))
            ->assertOk()
            ->assertJsonPath('data.0.type', ['key' => 'adjustment', 'label' => 'Adjustment'])
            ->assertJsonPath('data.0.points', -4)
            ->assertJsonPath('data.0.event', null)
            ->assertJsonPath('data.1.event.title', 'Qadisha Valley')
            ->assertJsonPath('meta.total', 2);
    });
});

describe('my rewards', function () {
    it('shows every reward, unlocked or why not, and where it was used', function () {
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 250, 'description' => 'Test']); // Mountaineer
        $hike = Event::factory()->published()->create(['starts_on' => now()->addDays(5)->toDateString(), 'difficulty_level' => DifficultyLevel::Endurance]);
        $registration = apiMyBooking($this->hiker, $hike);
        $this->hiker->rewardRedemptions()->create(['reward' => 'mountaineer_free_moderate', 'event_registration_id' => $registration->id, 'discount_cents' => 3000]);

        $rewards = collect($this->withToken($this->token)->getJson(route('api.v1.me.rewards'))->assertOk()->json('data'))->keyBy('key');

        expect($rewards['mountaineer_free_moderate'])
            ->unlocked->toBeTrue()
            ->uses_left->toBe(0)
            ->rank->toBe(['key' => 'mountaineer', 'label' => 'Mountaineer'])
            ->and($rewards['mountaineer_free_moderate']['used'][0]['event']['slug'])->toBe($hike->slug)
            ->and($rewards['mountaineer_free_moderate']['used'][0]['discount_cents'])->toBe(3000)
            ->and($rewards['trekker_half_off'])->unlocked->toBeFalse()->locked_reason->toBe('After 5 completed hikes (0 so far)')
            ->and($rewards['highlander_gift_card']['locked_reason'])->toStartWith('Reach Highlander');
    });

    it('is empty for an account without a hiker profile', function () {
        $staff = User::factory()->create(['phone' => '+96171000111']);
        $token = $staff->createToken('Phone')->plainTextToken;

        $this->withToken($token)->getJson(route('api.v1.me.rewards'))->assertExactJson(['data' => []]);
        $this->withToken($token)->getJson(route('api.v1.me.bookings'))->assertJsonPath('data', []);
        $this->withToken($token)->getJson(route('api.v1.me.points'))->assertJsonPath('data', []);
        $this->withToken($token)->putJson(route('api.v1.me.leaderboard'), ['show_on_leaderboard' => false])
            ->assertNotFound()
            ->assertJsonPath('message', 'You will appear on the leaderboard after your first hike.');
    });
});

describe('leaderboard visibility', function () {
    it('hides and shows me on the leaderboard', function () {
        $this->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 30, 'description' => 'Test']);

        $this->withToken($this->token)->putJson(route('api.v1.me.leaderboard'), ['show_on_leaderboard' => false])
            ->assertOk()
            ->assertJsonPath('data.hiker.show_on_leaderboard', false)
            ->assertJsonPath('data.hiker.leaderboard_position', null);
        expect($this->hiker->fresh()->show_on_leaderboard)->toBeFalse();

        $this->withToken($this->token)->putJson(route('api.v1.me.leaderboard'), ['show_on_leaderboard' => true])
            ->assertJsonPath('data.hiker.leaderboard_position', 1);

        $this->withToken($this->token)->putJson(route('api.v1.me.leaderboard'), [])->assertJsonValidationErrors('show_on_leaderboard');
    });
});
