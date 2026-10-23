<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\User;
use App\Models\XApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => Mail::fake());

function applicationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'email' => 'rima@example.com',
        'age' => '29',
        'city' => 'Beirut',
        'hike_frequency' => 'weekly',
        'hardest_route' => 'Qornet es Sawda from Bcharre',
        'hardest_date' => '2026-06-14',
        'hardest_km' => '24',
        'hardest_elevation_m' => '1400',
        'hardest_duration_hours' => '9.5',
        'longest_km' => '30',
        'max_ascent_m' => '1600',
        'pace' => 'steady',
        'steady_pace' => '1',
        'main_challenge' => 'Knees on long descents.',
        'track_link' => 'https://www.strava.com/athletes/123',
        'experience' => ['winter', 'night'],
        'gear' => ['boots', 'headlamp', 'water'],
        'limitations' => '',
        'consent' => '1',
    ], $overrides);
}

function xMember(array $hikerAttributes = []): User
{
    $user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $user->assignRole(Role::Member->value);
    Hiker::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456', 'user_id' => $user->id] + $hikerAttributes);

    return $user;
}

describe('landing page', function () {
    it('explains X Events and lists the upcoming ones', function () {
        Event::factory()->published()->create(['title' => 'X30 Tannourine to Douma', 'is_xtreme' => true]);
        Event::factory()->published()->create(['title' => 'Easy Sunday', 'is_xtreme' => false, 'starts_on' => now()->addDays(50)->toDateString()]);

        $this->get(route('x-events'))
            ->assertOk()
            ->assertSee('Longer routes. Sustained effort. Prepared hikers.')
            ->assertSeeInOrder(['Endurance', 'Preparation', 'Team discipline'])
            ->assertSeeInOrder(['Apply once', 'Manual review', 'Personal access'])
            ->assertSee('X30 Tannourine to Douma')
            ->assertDontSee('Easy Sunday')
            ->assertSee(route('x-events.apply'));
    });

    it('shows a member where they stand', function () {
        $user = xMember();
        XApplication::factory()->create(['hiker_id' => $user->hiker->id]);
        $this->actingAs($user)->get(route('x-events'))->assertSee('Your application is under review.');

        $user->hiker->update(['x_approved_at' => now()]);
        $this->actingAs($user)->get(route('x-events'))->assertSee('You have access to X Events.');
    });

    it('is linked from the homepage and in the sitemap only once real', function () {
        $this->get(route('home'))->assertSee(route('x-events'));
        $this->get(route('x-events'))->assertDontSee('noindex');
        $this->get(route('sitemap'))->assertSee('<loc>'.route('x-events').'</loc>', false);
    });
});

describe('application form', function () {
    it('takes an application and links it to the hiker by WhatsApp number', function () {
        $this->get(route('x-events.apply'))->assertOk()->assertSee('Your hardest hike so far')->assertSee('Broken-in hiking boots');

        $this->post(route('x-events.apply.store'), applicationPayload())->assertRedirect(route('x-events.applied'));

        $application = XApplication::sole();
        expect($application)
            ->status->toBe(XApplicationStatus::Pending)
            ->phone->toBe('+96170123456')
            ->hardest_km->toBe(24.0)
            ->steady_pace->toBeTrue()
            ->experience->toBe(['winter', 'night'])
            ->guardian_name->toBeNull()
            ->and($application->hiker->phone)->toBe('+96170123456');
        $this->get(route('x-events.applied'))->assertSee('Application sent');
    });

    it('needs a parent or guardian for minors', function () {
        $this->post(route('x-events.apply.store'), applicationPayload(['age' => '16']))
            ->assertSessionHasErrors(['guardian_name', 'guardian_phone']);

        $this->post(route('x-events.apply.store'), applicationPayload(['age' => '16', 'guardian_name' => 'Mona Haddad', 'guardian_phone' => '+961 3 111 222']))
            ->assertSessionHasNoErrors();
        expect(XApplication::sole())->guardian_name->toBe('Mona Haddad')->guardian_phone->toBe('+9613111222');
    });

    it('validates the answers', function (array $overrides, string $field) {
        $this->post(route('x-events.apply.store'), applicationPayload($overrides))->assertSessionHasErrors($field);
    })->with([
        'no phone' => [['phone' => ''], 'phone'],
        'unknown frequency' => [['hike_frequency' => 'daily'], 'hike_frequency'],
        'unknown gear' => [['gear' => ['jetpack']], 'gear.0'],
        'bad link' => [['track_link' => 'not a url'], 'track_link'],
        'future date' => [['hardest_date' => now()->addYear()->toDateString()], 'hardest_date'],
        'no consent' => [['consent' => ''], 'consent'],
        'no steady pace answer' => [['steady_pace' => ''], 'steady_pace'],
    ]);

    it('is "apply once": no second application while one is under review or after approval', function () {
        $this->post(route('x-events.apply.store'), applicationPayload());
        $this->post(route('x-events.apply.store'), applicationPayload())
            ->assertSessionHas('error', 'You already have an application under review. We will contact you on WhatsApp.');

        XApplication::sole()->hiker->update(['x_approved_at' => now()]);
        XApplication::query()->update(['status' => XApplicationStatus::Approved]);
        $this->post(route('x-events.apply.store'), applicationPayload())
            ->assertSessionHas('error', "You're already approved for X Events. Log in to book them.");

        // After a rejection they may apply again.
        XApplication::query()->update(['status' => XApplicationStatus::Rejected]);
        XApplication::sole()->hiker->update(['x_approved_at' => null]);
        $this->post(route('x-events.apply.store'), applicationPayload())->assertRedirect(route('x-events.applied'));
        expect(XApplication::count())->toBe(2);
    });
});

describe('review in the CMS', function () {
    beforeEach(fn () => $this->admin = userWithRole(Role::Admin));

    it('lists waiting applications first and shows every answer', function () {
        $done = XApplication::factory()->create(['name' => 'Done Person', 'status' => XApplicationStatus::Rejected]);
        $waiting = XApplication::factory()->create(['name' => 'Waiting Person', 'limitations' => 'Old knee injury']);

        $this->actingAs($this->admin)->get(route('admin.x-applications.index'))
            ->assertSeeInOrder(['Waiting Person', 'Done Person'])
            ->assertSee('Under review (1)');

        $this->actingAs($this->admin)->get(route('admin.x-applications.show', $waiting))
            ->assertOk()
            ->assertSee('Qornet es Sawda summit')
            ->assertSee('22 km · +1300 m · 9 h')
            ->assertSee('Two or three times a month')
            ->assertSee('Winter / snow hikes')
            ->assertSee('Missing: Warm and waterproof layers')
            ->assertSee('Old knee injury')
            ->assertSee('Approve');
    });

    it('approves, sends the personal access link, and revokes', function () {
        $application = XApplication::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);

        $this->actingAs($this->admin)->post(route('admin.x-applications.approve', $application), ['decision_note' => 'Strong record'])
            ->assertSessionHas('success');
        expect($application->fresh())->status->toBe(XApplicationStatus::Approved)->decision_note->toBe('Strong record')
            ->reviewed_by->toBe($this->admin->id)
            ->and($application->hiker->fresh()->x_approved_at)->not->toBeNull();

        // No account yet: the access link is an account invite.
        $this->actingAs($this->admin)->post(route('admin.x-applications.access-link', $application))->assertSessionHas('memberLink');
        expect(session('memberLink')['url'])->toContain('/activate/invite/')
            ->and(session('memberLink')['whatsapp'])->toStartWith('https://wa.me/96170123456?text=');
        expect($application->fresh()->access_sent_at)->not->toBeNull();

        $this->actingAs($this->admin)->post(route('admin.x-applications.revoke', $application), ['decision_note' => 'Unsafe on last hike'])
            ->assertSessionHas('success', 'X Events access revoked.');
        expect($application->fresh()->status)->toBe(XApplicationStatus::Revoked)
            ->and($application->hiker->fresh()->x_approved_at)->toBeNull();
    });

    it('sends members with an account to the X Events page', function () {
        $user = xMember(['x_approved_at' => now()]);
        $application = XApplication::factory()->create(['hiker_id' => $user->hiker->id, 'status' => XApplicationStatus::Approved]);

        $this->actingAs($this->admin)->post(route('admin.x-applications.access-link', $application));

        expect(session('memberLink')['url'])->toBe(route('x-events'));
    });

    it('rejects, keeps internal notes, and refuses double decisions', function () {
        $application = XApplication::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.x-applications.note', $application), ['internal_note' => 'Call to check the Qornet hike'])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.x-applications.reject', $application), ['decision_note' => 'Not enough long hikes yet']);
        $this->actingAs($this->admin)->post(route('admin.x-applications.approve', $application))
            ->assertSessionHas('error', 'Only applications under review can be approved or rejected.');

        expect($application->fresh())->status->toBe(XApplicationStatus::Rejected)->internal_note->toBe('Call to check the Qornet hike')
            ->and($application->hiker->fresh()->x_approved_at)->toBeNull();
    });

    it('is only for staff who review applications', function () {
        $application = XApplication::factory()->create();

        $this->actingAs(userWithRole(Role::Guide))->get(route('admin.x-applications.index'))->assertForbidden();
        $this->actingAs(userWithRole(Role::Editor))->post(route('admin.x-applications.approve', $application))->assertForbidden();
    });
});

describe('booking X events', function () {
    beforeEach(function () {
        $this->x = Event::factory()->published()->withMeetingPoints(1)->create(['is_xtreme' => true, 'difficulty_level' => DifficultyLevel::Xtreme, 'fee_cents' => 0]);
    });

    function bookX(Event $event): TestResponse
    {
        return test()->post(route('events.register.store', $event), [
            'name' => 'Rima Haddad', 'phone' => '+961 70 123 456',
            'meeting_point_id' => $event->meetingPoints()->value('id'),
            'emergency_contact_name' => 'Karim', 'emergency_contact_phone' => '+961 3 000 000',
            'terms' => '1', 'level_acknowledged' => '1',
        ]);
    }

    it('lets approved members book and confirms them straight away', function () {
        $this->actingAs(xMember(['x_approved_at' => now()]));

        $this->get(route('events.register', $this->x))->assertSee('Book my seat');
        bookX($this->x)->assertSessionHasNoErrors();
        expect(EventRegistration::sole()->status)->toBe(RegistrationStatus::Confirmed);
    });

    it('lets Elite members in without an application', function () {
        $user = xMember();
        $user->hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => 750, 'description' => 'Elite']);

        $this->actingAs($user);
        bookX($this->x)->assertSessionHasNoErrors();
        expect(EventRegistration::sole()->status)->toBe(RegistrationStatus::Confirmed);
    });

    it('refuses members who are not (or no longer) approved', function () {
        $this->actingAs(xMember());
        bookX($this->x)->assertSessionHas('error', 'X Events are for approved hikers: apply first, or log in if you are already approved.');

        $this->get(route('events.show', $this->x))->assertSee('How to get access');
        expect(EventRegistration::count())->toBe(0);
    });
});
