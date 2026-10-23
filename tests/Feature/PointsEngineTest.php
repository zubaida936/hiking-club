<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Actions\SyncRegistrationPoints;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->admin = userWithRole(Role::Admin);
    $this->event = Event::factory()->published()->withMeetingPoints(1)->create([
        'title' => 'Qadisha Valley Descent',
        'starts_on' => '2026-11-20',
        'difficulty_level' => DifficultyLevel::Endurance,
        'fee_cents' => 3000,
        'capacity' => 30,
    ]);
    $this->travelTo('2026-11-01 10:00');
});

function bookingFor(Event $event, array $attributes = []): EventRegistration
{
    return EventRegistration::factory()->for($event)->create($attributes + ['created_at' => '2026-11-01 10:00']);
}

function bookViaForm(Event $event, array $overrides = []): void
{
    test()->post(route('events.register.store', $event), array_merge([
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'meeting_point_id' => $event->meetingPoints()->value('id'),
        'emergency_contact_name' => 'Karim',
        'emergency_contact_phone' => '+961 3 000 000',
        'terms' => '1',
        'payment_method' => 'cash',
        'level_acknowledged' => '1',
    ], $overrides))->assertSessionHasNoErrors();
}

function attend(EventRegistration $registration): void
{
    test()->travelTo($registration->event->starts_on->copy()->setTime(18, 0));
    test()->actingAs(test()->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended'])
        ->assertSessionHas('success');
}

describe('hikers', function () {
    it('links bookings with the same phone number to one hiker', function () {
        bookViaForm($this->event);
        $other = Event::factory()->published()->withMeetingPoints(1)->create(['starts_on' => '2026-11-27']);
        bookViaForm($other, ['phone' => '+96170123456', 'name' => 'Rima H.', 'email' => 'rima@example.com']);

        $hiker = Hiker::sole();
        expect($hiker)->phone->toBe('+96170123456')->name->toBe('Rima H.')->email->toBe('rima@example.com')
            ->and($hiker->registrations)->toHaveCount(2);
    });

    it('remembers who invited a hiker when the friend is a known hiker', function () {
        $friend = Hiker::factory()->create(['phone' => '+96171999888']);

        bookViaForm($this->event, ['referrer_phone' => '+961 71 999 888']);
        bookViaForm($this->event, ['phone' => '+96170000001', 'referrer_phone' => '+961 76 555 444']); // unknown number

        expect(EventRegistration::where('phone', '+96170123456')->sole()->referred_by_hiker_id)->toBe($friend->id)
            ->and(EventRegistration::where('phone', '+96170000001')->sole()->referred_by_hiker_id)->toBeNull();

        $this->post(route('events.register.store', $this->event), ['phone' => '+96170000002', 'referrer_phone' => '+96170000002'])
            ->assertSessionHasErrors(['referrer_phone' => 'Enter the phone number of the friend who invited you, not yours.']);
    });

    it('links old bookings to hikers with points:sync', function () {
        $registration = bookingFor($this->event);
        $registration->update(['hiker_id' => null]);

        $this->artisan('points:sync')->assertSuccessful();

        expect($registration->fresh()->hiker_id)->not->toBeNull();
    });
});

describe('earning points', function () {
    it('awards points by level when a paid booking is marked attended', function (DifficultyLevel $level, int $points) {
        $this->event->update(['difficulty_level' => $level]);
        $registration = bookingFor($this->event, ['paid_at' => now(), 'created_at' => '2026-11-18 10:00']);

        attend($registration);

        expect($registration->hiker->totalPoints())->toBe($points);
        expect(PointTransaction::sole())
            ->type->toBe(PointType::Hike)
            ->description->toBe("Completed Qadisha Valley Descent ({$level->label()})");
    })->with([
        'easy' => [DifficultyLevel::Foundation, 8],
        'moderate' => [DifficultyLevel::Endurance, 14],
        'challenging' => [DifficultyLevel::Advanced, 22],
        'xtreme' => [DifficultyLevel::Xtreme, 35],
    ]);

    it('adds the early-booking bonus for bookings made 5+ days before the hike day', function (string $bookedAt, int $total) {
        $registration = bookingFor($this->event, ['paid_at' => now(), 'created_at' => $bookedAt]);

        attend($registration);

        expect($registration->hiker->totalPoints())->toBe($total);
    })->with([
        // Hike on 20 Nov (Lebanon time, UTC+2 in November).
        '5 days ahead' => ['2026-11-15 08:00', 18],
        'late evening, still 15 Nov in Beirut' => ['2026-11-15 21:30', 18],
        'just after midnight 16 Nov in Beirut' => ['2026-11-15 22:30', 14],
        '2 days ahead' => ['2026-11-18 09:00', 14],
    ]);

    it('gives nothing for no-shows, unpaid paid hikes or unconfirmed bookings', function () {
        $noShow = bookingFor($this->event, ['paid_at' => now()]);
        $unpaid = bookingFor($this->event);
        $this->travelTo('2026-11-20 18:00');

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $noShow), ['attendance' => 'no_show']);
        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $unpaid), ['attendance' => 'attended']);
        expect(PointTransaction::count())->toBe(0);

        // Paying later awards the points.
        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $unpaid), ['paid' => '1']);
        expect($unpaid->hiker->totalPoints())->toBe(18);
    });

    it('counts free hikes without payment, and every hike when paid_only is off', function () {
        $this->event->update(['fee_cents' => 0]);
        $free = bookingFor($this->event);
        attend($free);
        expect($free->hiker->totalPoints())->toBe(18);

        config(['points.paid_only' => false]);
        $paidHike = Event::factory()->published()->create(['starts_on' => '2026-11-27', 'fee_cents' => 3000, 'difficulty_level' => DifficultyLevel::Foundation]);
        $unpaid = bookingFor($paidHike, ['created_at' => '2026-11-26 10:00']);
        attend($unpaid);
        expect($unpaid->hiker->totalPoints())->toBe(8);
    });
});

describe('corrections', function () {
    it('reverses points when attendance is cleared, the booking is cancelled or marked unpaid', function () {
        $registration = bookingFor($this->event, ['paid_at' => now()]);
        attend($registration);
        expect($registration->hiker->totalPoints())->toBe(18);

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => '']);
        expect($registration->hiker->totalPoints())->toBe(0)
            ->and(PointTransaction::where('points', '<', 0)->pluck('description')->all())
            ->toContain('Reversed: Completed hike (Qadisha Valley Descent)', 'Reversed: Early booking (Qadisha Valley Descent)');

        $this->actingAs($this->admin)->post(route('admin.registrations.attendance', $registration), ['attendance' => 'attended']);
        expect($registration->hiker->totalPoints())->toBe(18);

        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $registration), ['paid' => '0']);
        expect($registration->hiker->totalPoints())->toBe(0);

        $this->actingAs($this->admin)->post(route('admin.registrations.payment', $registration), ['paid' => '1']);
        $this->actingAs($this->admin)->post(route('admin.registrations.cancel', $registration), ['notify' => '0']);
        expect($registration->hiker->totalPoints())->toBe(0);
    });

    it('is safe to sync again and again', function () {
        $registration = bookingFor($this->event, ['paid_at' => now()]);
        attend($registration);
        $lines = PointTransaction::count();

        expect(app(SyncRegistrationPoints::class)->handle($registration))->toBe(0);
        $this->artisan('points:sync')->assertSuccessful();
        expect(PointTransaction::count())->toBe($lines);
    });

    it('applies changed point values with points:sync', function () {
        $registration = bookingFor($this->event, ['paid_at' => now(), 'created_at' => '2026-11-19 10:00']);
        attend($registration);

        config(['points.hike.2' => 20]);
        $this->artisan('points:sync')->assertSuccessful();

        expect($registration->hiker->totalPoints())->toBe(20)
            ->and(PointTransaction::latest('id')->first())
            ->points->toBe(6)
            ->description->toStartWith('Updated: Completed Qadisha Valley Descent');
    });

    it('moves the points when an organizer corrects the phone number', function () {
        $registration = bookingFor($this->event, ['paid_at' => now(), 'created_at' => '2026-11-19 10:00']);
        attend($registration);
        $wrong = $registration->hiker;

        $this->actingAs($this->admin)->put(route('admin.registrations.update', $registration), [
            'name' => $registration->name,
            'phone' => '+961 71 222 333',
            'emergency_contact_name' => 'Someone',
            'emergency_contact_phone' => '+961 3 444 555',
        ])->assertSessionHasNoErrors();

        $right = Hiker::where('phone', '+96171222333')->sole();
        expect($wrong->totalPoints())->toBe(0)->and($right->totalPoints())->toBe(14);
    });
});

describe('referral bonus', function () {
    it('pays the friend once, when the new hiker completes a first hike', function () {
        $friend = Hiker::factory()->create();
        $first = bookingFor($this->event, ['paid_at' => now(), 'referred_by_hiker_id' => $friend->id, 'created_at' => '2026-11-19 10:00']);
        $later = Event::factory()->published()->create(['starts_on' => '2026-11-27', 'fee_cents' => 0, 'difficulty_level' => DifficultyLevel::Foundation]);
        $second = bookingFor($later, ['phone' => $first->phone, 'hiker_id' => $first->hiker_id, 'referred_by_hiker_id' => $friend->id, 'created_at' => '2026-11-26 10:00']);

        attend($first);
        attend($second);

        expect($friend->totalPoints())->toBe(10)
            ->and($first->hiker->totalPoints())->toBe(14 + 8)
            ->and(PointTransaction::where('type', PointType::Referral)->sole()->description)
            ->toBe("Brought a new hiker: {$first->name} (Qadisha Valley Descent)");
    });

    it('does not pay for hikers who already completed a hike before', function () {
        $friend = Hiker::factory()->create();
        $earlier = Event::factory()->published()->create(['starts_on' => '2026-11-10', 'fee_cents' => 0]);
        $old = bookingFor($earlier, ['attendance' => Attendance::Attended, 'created_at' => '2026-11-09 10:00']);
        $registration = bookingFor($this->event, ['phone' => $old->phone, 'hiker_id' => $old->hiker_id, 'paid_at' => now(), 'referred_by_hiker_id' => $friend->id]);

        attend($registration);

        expect($friend->totalPoints())->toBe(0);
    });
});

describe('CMS', function () {
    it('lists hikers by points and shows their history', function () {
        $top = Hiker::factory()->create(['name' => 'Top Hiker']);
        $top->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 50, 'description' => 'Welcome bonus']);
        Hiker::factory()->create(['name' => 'New Hiker']);

        $this->actingAs($this->admin)->get(route('admin.points.index'))
            ->assertOk()
            ->assertSeeInOrder(['Top Hiker', '50', 'New Hiker', '0']);

        $this->actingAs($this->admin)->get(route('admin.points.show', $top))
            ->assertOk()
            ->assertSee('Welcome bonus')
            ->assertSee('+50');
    });

    it('lets admins adjust points with a reason', function () {
        $hiker = Hiker::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.points.adjust', $hiker), ['points' => '-5', 'reason' => 'Duplicate entry'])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.points.adjust', $hiker), ['points' => '0', 'reason' => 'Nothing'])
            ->assertSessionHasErrors(['points' => 'Enter a number of points other than 0.']);

        expect($hiker->totalPoints())->toBe(-5)
            ->and(PointTransaction::sole())->created_by->toBe($this->admin->id)->type->toBe(PointType::Adjustment);
    });

    it('is only for staff who manage loyalty', function () {
        $hiker = Hiker::factory()->create();

        $this->actingAs(userWithRole(Role::Guide))->get(route('admin.points.index'))->assertForbidden();
        $this->actingAs(userWithRole(Role::Guide))->post(route('admin.points.adjust', $hiker), ['points' => 5, 'reason' => 'x'])->assertForbidden();
    });
});

describe('public pages', function () {
    it('shows the points a hike is worth', function () {
        $this->get(route('events.show', $this->event))
            ->assertSee('+14')
            ->assertSee('+4 if you book now (5+ days ahead)');

        $this->travelTo('2026-11-17 10:00');
        $this->get(route('events.show', $this->event))->assertSee('+14')->assertDontSee('if you book now');

        bookViaForm($this->event);
        $this->get(route('registrations.show', EventRegistration::sole()))
            ->assertSee('You earn')
            ->assertSee('14 points')
            ->assertSee('(paid bookings only)');
    });
});
