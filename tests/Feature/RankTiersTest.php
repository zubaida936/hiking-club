<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Loyalty\Leaderboard;
use App\Domain\Registrations\Enums\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;

function hikerWithPoints(string $name, int $points, float $km = 0): Hiker
{
    $hiker = Hiker::factory()->create(['name' => $name]);
    if ($points !== 0) {
        $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);
    }
    if ($km > 0) {
        $event = Event::factory()->published()->past()->create(['distance_km' => $km]);
        EventRegistration::factory()->for($event)->create(['hiker_id' => $hiker->id, 'phone' => $hiker->phone, 'attendance' => Attendance::Attended]);
    }

    return $hiker;
}

it('maps points to tiers', function (int $points, RankTier $tier) {
    expect(RankTier::forPoints($points))->toBe($tier);
})->with([
    [0, RankTier::Pathfinder], [79, RankTier::Pathfinder], [80, RankTier::Trekker], [199, RankTier::Trekker],
    [200, RankTier::Mountaineer], [399, RankTier::Mountaineer], [400, RankTier::Highlander], [699, RankTier::Highlander],
    [700, RankTier::Elite], [5000, RankTier::Elite], [-10, RankTier::Pathfinder],
]);

it('follows the thresholds in the config', function () {
    config(['points.tiers.trekker' => 50]);

    expect(RankTier::forPoints(50))->toBe(RankTier::Trekker)
        ->and(RankTier::Pathfinder->next())->toBe(RankTier::Trekker)
        ->and(RankTier::Elite->next())->toBeNull();
});

it('tells how far the next tier is', function () {
    expect(hikerWithPoints('A', 75))->pointsToNextTier()->toBe(5)
        ->and(hikerWithPoints('B', 900))->pointsToNextTier()->toBeNull();
});

it('ranks by points, then by distance hiked', function () {
    $short = hikerWithPoints('Short Walker', 100, 8);
    $long = hikerWithPoints('Long Walker', 100, 25.5);
    $top = hikerWithPoints('Top Hiker', 300);
    $new = hikerWithPoints('New Hiker', 0);

    $board = app(Leaderboard::class);

    expect($board->query()->pluck('name')->all())->toBe(['Top Hiker', 'Long Walker', 'Short Walker', 'New Hiker'])
        ->and($board->positionOf($long))->toBe(2)
        ->and($board->positionOf($new))->toBe(4)
        ->and($board->query()->find($long->id)->totalDistanceKm())->toBe(25.5);
});

it('shows rank, tier and distance in the CMS', function () {
    $hiker = hikerWithPoints('Long Walker', 210, 12.5);
    hikerWithPoints('Top Hiker', 750);
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->get(route('admin.points.index'))
        ->assertSeeInOrder(['#1', 'Top Hiker', 'Elite', '750', '#2', 'Long Walker', 'Mountaineer', '210', '12.5 km']);

    $this->actingAs($admin)->get(route('admin.points.show', $hiker))
        ->assertSee('Mountaineer')
        ->assertSee('Rank #2')
        ->assertSee('190 points to Highlander')
        ->assertSee('12.5 km');
});
