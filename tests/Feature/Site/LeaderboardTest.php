<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Loyalty\Leaderboard;
use App\Domain\Registrations\Enums\Attendance;
use App\Http\Controllers\Site\LeaderboardController;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\User;

function rankedHiker(string $name, int $points, float $km = 0, array $attributes = []): Hiker
{
    $hiker = Hiker::factory()->create(['name' => $name] + $attributes);
    if ($points !== 0) {
        $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);
    }
    if ($km > 0) {
        $event = Event::factory()->published()->past()->create(['distance_km' => $km]);
        EventRegistration::factory()->for($event)->create(['hiker_id' => $hiker->id, 'phone' => $hiker->phone, 'attendance' => Attendance::Attended]);
    }

    return $hiker;
}

it('shows the top 10 on the homepage with hikes and km', function () {
    foreach (range(1, 12) as $i) {
        rankedHiker("Hiker Number{$i}", $i * 10);
    }
    rankedHiker('Rima Haddad', 500, 14.5);

    $this->get(route('home'))
        ->assertSeeInOrder(['Top 10 Hikers', 'Rima H.', 'Highlander', '1 hike', '14.5 km', '500', 'Hiker N.'])
        ->assertSee('href="'.route('leaderboard').'"', false)
        ->assertDontSee('Rima Haddad');

    expect(app(Leaderboard::class)->top(10))->toHaveCount(10);
});

it('lists up to 100 hikers on the full page, ties broken by distance', function () {
    rankedHiker('Short Walker', 100, 8);
    rankedHiker('Long Walker', 100, 25);
    rankedHiker('Top Hiker', 300);

    $this->get(route('leaderboard'))
        ->assertOk()
        ->assertSee('<title>Leaderboard · '.e(config('app.name')).'</title>', false)
        ->assertSeeInOrder(['Top H.', 'Long W.', 'Short W.']);

    expect(LeaderboardController::SIZE)->toBe(100);
});

it('leaves out hikers without points and hikers who hid themselves, and never shows phones', function () {
    $visible = rankedHiker('Visible Person', 50);
    rankedHiker('Hidden Person', 400, attributes: ['show_on_leaderboard' => false]);
    rankedHiker('Zero Person', 0);

    $this->get(route('leaderboard'))
        ->assertSee('Visible P.')
        ->assertDontSee('Hidden P.')
        ->assertDontSee('Zero P.')
        ->assertDontSee($visible->phone);
});

it('shows a member their own place and lets them hide', function () {
    rankedHiker('Top Hiker', 300);
    $user = User::factory()->create(['name' => 'Rima Haddad']);
    $user->assignRole(Role::Member->value);
    $me = rankedHiker('Rima Haddad', 120, attributes: ['user_id' => $user->id]);

    $this->actingAs($user)->get(route('leaderboard'))
        ->assertSee('<strong>#2</strong> with 120 points', false)
        ->assertSee('(you)');
    $this->actingAs($user)->get(route('account'))
        ->assertSee('as "Rima H."', false)
        ->assertSeeInOrder(['Leaderboard', '#2']);

    $this->actingAs($user)->post(route('account.leaderboard'), ['show_on_leaderboard' => '0'])
        ->assertSessionHas('success', 'You are hidden from the public leaderboard.');
    expect($me->fresh()->show_on_leaderboard)->toBeFalse();

    $this->actingAs($user)->get(route('leaderboard'))->assertDontSee('Rima H.')->assertSee("You're hidden from the leaderboard.", false);
    $this->actingAs($user)->get(route('account'))->assertSee('Show me');

    $this->actingAs($user)->post(route('account.leaderboard'), ['show_on_leaderboard' => '1']);
    expect($me->fresh()->show_on_leaderboard)->toBeTrue();
});

it('shortens names to first name and initial', function (string $name, string $public) {
    expect(Hiker::factory()->make(['name' => $name])->publicName())->toBe($public);
})->with([
    ['Rima Haddad', 'Rima H.'],
    ['Jean Paul el Khoury', 'Jean K.'],
    ['Nour', 'Nour'],
    ['  élie  frem ', 'élie F.'],
]);

it('is in the sitemap', function () {
    $this->get(route('sitemap'))->assertSee('<loc>'.route('leaderboard').'</loc>', false);
});
