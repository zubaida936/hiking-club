<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Models\Event;
use App\Models\Hiker;
use App\Models\User;
use App\Models\XApplication;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function apiXApplication(array $overrides = []): array
{
    return array_merge([
        'name' => 'Rima Haddad',
        'phone' => '+961 70 123 456',
        'email' => 'rima@example.com',
        'age' => 29,
        'city' => 'Beirut',
        'hike_frequency' => 'weekly',
        'hardest_route' => 'Qornet es Sawda from Bcharre',
        'hardest_date' => '2026-06-14',
        'hardest_km' => 24,
        'hardest_elevation_m' => 1400,
        'hardest_duration_hours' => 9.5,
        'longest_km' => 30,
        'max_ascent_m' => 1600,
        'pace' => 'steady',
        'steady_pace' => true,
        'main_challenge' => 'Knees on long descents.',
        'experience' => ['winter', 'night'],
        'gear' => ['boots', 'headlamp'],
        'consent' => true,
    ], $overrides);
}

/**
 * @return array{0: Hiker, 1: string}
 */
function apiXMember(array $hikerAttributes = []): array
{
    $user = User::factory()->create(['name' => 'Rima Haddad', 'phone' => '+96170123456']);
    $user->assignRole(Role::Member->value);
    $hiker = Hiker::factory()->create($hikerAttributes + ['name' => 'Rima Haddad', 'phone' => '+96170123456', 'user_id' => $user->id]);

    return [$hiker, $user->createToken('Phone')->plainTextToken];
}

describe('X Events list', function () {
    it('lists upcoming X hikes for everyone', function () {
        $x = Event::factory()->published()->create(['starts_on' => now()->addDays(9)->toDateString(), 'is_xtreme' => true]);
        Event::factory()->published()->create(['starts_on' => now()->addDays(3)->toDateString()]);
        Event::factory()->published()->past()->create(['is_xtreme' => true]);

        $this->getJson(route('api.v1.x-events.index'))
            ->assertOk()
            ->assertJsonPath('data.events.*.slug', [$x->slug])
            ->assertJsonPath('data.access', null);
    });

    it('gives the same page texts as the website', function () {
        config(['xevents.page.heading' => 'Big days out.', 'xevents.page.steps.0.title' => 'Apply']);

        $this->getJson(route('api.v1.x-events.index'))
            ->assertJsonPath('data.page.heading', 'Big days out.')
            ->assertJsonPath('data.page.requirements.*.title', ['Endurance', 'Preparation', 'Team discipline'])
            ->assertJsonPath('data.page.steps.0.title', 'Apply')
            ->assertJsonPath('data.page.no_events', config('xevents.page.no_events'));

        $this->get(route('x-events'))->assertSee('Big days out.');
    });

    it('tells the member their access and application status', function () {
        [$hiker, $token] = apiXMember();

        $this->withToken($token)->getJson(route('api.v1.x-events.index'))
            ->assertJsonPath('data.access', ['can_book' => false, 'application' => null, 'can_apply' => true]);

        XApplication::factory()->for($hiker)->create(['status' => XApplicationStatus::Pending]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson(route('api.v1.x-events.index'))
            ->assertJsonPath('data.access.can_book', false)
            ->assertJsonPath('data.access.application.status', ['key' => 'pending', 'label' => 'Under review'])
            ->assertJsonPath('data.access.can_apply', false);
    });

    it('lets approved members book', function () {
        [, $token] = apiXMember(['x_approved_at' => now()]);

        $this->withToken($token)->getJson(route('api.v1.x-events.index'))
            ->assertJsonPath('data.access.can_book', true)
            ->assertJsonPath('data.access.can_apply', false);
    });
});

describe('application', function () {
    it('gives the form choices from the config', function () {
        $this->getJson(route('api.v1.x-events.application-form'))
            ->assertOk()
            ->assertJsonPath('data.frequency.0', ['key' => 'weekly', 'label' => 'Every week'])
            ->assertJsonPath('data.gear.*.key', array_keys(config('xevents.gear')))
            ->assertJsonPath('data.guardian_under_age', 18)
            ->assertJsonPath('data.consent', config('xevents.consent'))
            ->assertJsonPath('data.privacy_url', route('legal.privacy'))
            ->assertJsonPath('data.member', null);

        [, $token] = apiXMember();
        $this->withToken($token)->getJson(route('api.v1.x-events.application-form'))
            ->assertJsonPath('data.member.phone', '+96170123456');
    });

    it('sends an application once', function () {
        $this->postJson(route('api.v1.x-events.apply'), apiXApplication())
            ->assertCreated()
            ->assertJsonPath('data.status', ['key' => 'pending', 'label' => 'Under review']);

        expect(XApplication::sole())
            ->phone->toBe('+96170123456')
            ->experience->toBe(['winter', 'night'])
            ->hiker->phone->toBe('+96170123456');

        $this->postJson(route('api.v1.x-events.apply'), apiXApplication())
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You already have an application under review. We will contact you on WhatsApp.');
    });

    it('uses the website validation, including a guardian for minors', function () {
        $this->postJson(route('api.v1.x-events.apply'), apiXApplication(['age' => 16, 'consent' => false, 'pace' => 'sprint']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.guardian_name.0', 'Applicants under 18 need a parent or guardian.')
            ->assertJsonPath('errors.consent.0', 'Please accept the consent to send your application.')
            ->assertJsonValidationErrors(['guardian_phone', 'pace']);

        $this->postJson(route('api.v1.x-events.apply'), apiXApplication(['age' => 16, 'guardian_name' => 'Mona Haddad', 'guardian_phone' => '+961 3 111 222']))
            ->assertCreated();
    });

    it('tells approved hikers they can already book', function () {
        apiXMember(['x_approved_at' => now()]);

        $this->postJson(route('api.v1.x-events.apply'), apiXApplication())
            ->assertUnprocessable()
            ->assertJsonPath('message', "You're already approved for X Events. Log in to book them.");
    });
});
