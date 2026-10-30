<?php

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Loyalty\RankSystemGuide;
use App\Models\Event;
use App\Models\Hiker;
use App\Models\User;

function apiRankedHiker(string $name, int $points, array $attributes = []): Hiker
{
    $hiker = Hiker::factory()->create($attributes + ['name' => $name]);
    $hiker->pointTransactions()->create(['type' => PointType::Adjustment, 'points' => $points, 'description' => 'Test']);

    return $hiker;
}

describe('leaderboard', function () {
    it('ranks hikers by points with public names, like the website', function () {
        apiRankedHiker('Karim Saad', 90);
        apiRankedHiker('Rima Haddad', 250);
        apiRankedHiker('Hidden Hiker', 500, ['show_on_leaderboard' => false]);
        Hiker::factory()->create(['name' => 'No Points']);

        $this->getJson(route('api.v1.leaderboard'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Rima H.', 'Karim S.'])
            ->assertJsonPath('data.0.position', 1)
            ->assertJsonPath('data.0.rank', ['key' => 'mountaineer', 'label' => 'Mountaineer'])
            ->assertJsonPath('data.0.points', 250)
            ->assertJsonPath('data.0.is_me', false)
            ->assertJsonPath('my_position', null);

        $this->getJson(route('api.v1.leaderboard', ['limit' => 1]))->assertJsonCount(1, 'data');
        $this->getJson(route('api.v1.leaderboard', ['limit' => 500]))->assertJsonValidationErrors('limit');
    });

    it('shows the member their place', function () {
        apiRankedHiker('Rima Haddad', 250);
        $user = User::factory()->create();
        $user->assignRole(Role::Member->value);
        apiRankedHiker('Karim Saad', 90, ['user_id' => $user->id]);

        $this->withToken($user->createToken('Phone')->plainTextToken)->getJson(route('api.v1.leaderboard'))
            ->assertJsonPath('my_position', 2)
            ->assertJsonPath('data.1.is_me', true)
            ->assertJsonPath('data.0.is_me', false);
    });
});

it('explains the rank system from the config', function () {
    $response = $this->getJson(route('api.v1.rank-system'))->assertOk();

    expect($response->json('data.ranks.*.key'))->toBe(['pathfinder', 'trekker', 'mountaineer', 'highlander', 'elite'])
        ->and($response->json('data.ranks.2.min_points'))->toBe((int) config('points.tiers.mountaineer'))
        ->and($response->json('data.ranks.2.rewards.*.key'))->toContain('mountaineer_free_moderate')
        ->and($response->json('data.points.per_hike.1'))->toBe(['level' => 2, 'label' => DifficultyLevel::Endurance->label(), 'points' => (int) config('points.hike.2')])
        ->and($response->json('data.points.early_booking'))->toBe(['points' => 4, 'days' => 5])
        ->and($response->json('data.points.referral'))->toBe(10)
        // The same example and terms as the website page (RankSystemGuide).
        ->and($response->json('data.example'))->toBe([
            'level_label' => DifficultyLevel::Endurance->label(),
            'hike_points' => (int) config('points.hike.2'),
            'early_booking_points' => 4,
            'total' => (int) config('points.hike.2') + 4,
            'rank_label' => 'Trekker',
            'hikes_to_rank' => 5,
        ])
        ->and($response->json('data.terms'))->toBe(app(RankSystemGuide::class)->terms())
        ->and($response->json('data.terms.3'))->toContain('cancelled or unpaid afterwards');
});

it('leaves "or unpaid" out of the terms when free and paid hikes both count', function () {
    config(['points.paid_only' => false]);

    expect($this->getJson(route('api.v1.rank-system'))->json('data.terms.3'))->toContain('not attended, cancelled afterwards');
});

describe('difficulty', function () {
    it('lists the levels and the formula', function () {
        $this->getJson(route('api.v1.difficulty.levels'))
            ->assertOk()
            ->assertJsonCount(4, 'data.levels')
            ->assertJsonPath('data.levels.0.level', 1)
            ->assertJsonPath('data.levels.0.max_score', (int) config('difficulty.levels.foundation'))
            ->assertJsonPath('data.levels.3.max_score', null)
            ->assertJsonPath('data.formula.multiplier', config('difficulty.multiplier'));
    });

    it('calculates with the same formula as the website and suggests hikes', function () {
        $expected = app(DifficultyCalculator::class)->calculate(8, 220, 320);
        $match = Event::factory()->published()->create(['starts_on' => now()->addDays(4)->toDateString(), 'difficulty_level' => $expected->level]);

        $this->getJson(route('api.v1.difficulty.calculate', ['km' => 8, 'gain' => 220, 'loss' => 320]))
            ->assertOk()
            ->assertJsonPath('data.effort_score', $expected->effortScore)
            ->assertJsonPath('data.difficulty.level', $expected->level->value)
            ->assertJsonPath('data.points', (int) config("points.hike.{$expected->level->value}"))
            ->assertJsonPath('data.upcoming_hikes.*.slug', [$match->slug]);
    });

    it('validates the input', function () {
        $this->getJson(route('api.v1.difficulty.calculate', ['gain' => -5, 'loss' => 'a lot']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['km', 'gain', 'loss']);
    });
});
