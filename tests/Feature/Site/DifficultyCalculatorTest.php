<?php

use App\Domain\Events\Enums\DifficultyLevel;
use App\Models\Event;

it('explains the formula, levels and limitations', function () {
    $this->get(route('guide.difficulty'))
        ->assertOk()
        ->assertSee('<title>Difficulty Calculator · '.e(config('app.name')).'</title>', false)
        ->assertSee('score = 8 × (km + gain ÷ 100 + loss ÷ 300)')
        ->assertSee('10 km with +500 m and −500 m scores 133')
        ->assertSeeInOrder(['Level 1 · Foundation', 'score 0–99', 'Level 2 · Endurance', 'score 100–160', 'Level 3 · Advanced', 'score 161–220', 'Level 4 · Xtreme', 'score 221+'])
        ->assertSee('Weather:')
        ->assertSee('Terrain:')
        ->assertSee('Pace and breaks:')
        ->assertSee('Enter a distance to see the score.');
});

it('calculates on the server for shared links and without JavaScript', function () {
    // 8 km, +220 m, -320 m → 8 × (8 + 2.2 + 1.067) = 90 → Level 1
    $this->get(route('guide.difficulty', ['km' => 8, 'gain' => 220, 'loss' => 320]))
        ->assertSeeInOrder(['Result', '90', 'effort score', 'Level 1 · Foundation', DifficultyLevel::Foundation->description()])
        ->assertSee('value="8"', false)
        ->assertSee('value="220"', false);
});

it('gives the browser the same formula parameters and level texts', function () {
    $this->get(route('guide.difficulty'))
        ->assertSee('difficultyCalculator(', false)
        ->assertSee('gainPerKm')
        ->assertSee(DifficultyLevel::Xtreme->description())
        ->assertSee('Completing a hike at this level earns');
});

it('suggests upcoming hikes of the same level', function () {
    Event::factory()->published()->create(['title' => 'Easy Walk', 'difficulty_level' => DifficultyLevel::Foundation, 'starts_on' => now()->addDays(5)->toDateString()]);
    Event::factory()->published()->create(['title' => 'Big Day', 'difficulty_level' => DifficultyLevel::Xtreme, 'starts_on' => now()->addDays(50)->toDateString()]);

    $this->get(route('guide.difficulty', ['km' => 8, 'gain' => 220]))
        ->assertSee('Upcoming Level 1 · Foundation hikes')
        ->assertSee('Easy Walk')
        ->assertDontSee('Big Day');
});

it('ignores invalid values instead of failing', function (array $query) {
    $this->get(route('guide.difficulty', $query))->assertOk()->assertSee('Enter a distance to see the score.');
})->with([
    'text' => [['km' => 'far', 'gain' => 'high']],
    'negative' => [['km' => -5]],
    'too long' => [['km' => 5000]],
]);

it('is in the header menu and the sitemap', function () {
    $this->get(route('home'))->assertSee('href="'.route('guide.difficulty').'"', false);
    $this->get(route('sitemap'))->assertSee('<loc>'.route('guide.difficulty').'</loc>', false);
    $this->get(route('guide.difficulty'))->assertDontSee('noindex');
});
