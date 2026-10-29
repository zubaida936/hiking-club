<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

/**
 * The difficulty calculator. The score is always computed here, with the same formula the CMS uses
 * for events, so the app never has its own copy of the numbers.
 */
class DifficultyController extends Controller
{
    /**
     * Difficulty levels.
     *
     * The 4 levels with their score range and description, and the formula
     * (score = multiplier × (km + gain / gain_per_km + loss / loss_per_km)), for the explanation screen.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function levels(DifficultyCalculator $calculator): array
    {
        $formula = $calculator->toArray();

        return ['data' => [
            'levels' => array_map(fn (array $level) => [
                'level' => $level['value'],
                'name' => DifficultyLevel::from($level['value'])->name,
                'label' => $level['label'],
                'description' => DifficultyLevel::from($level['value'])->description(),
                // Highest effort score of the level; null for the last one.
                'max_score' => $level['max'],
                'points' => (int) config("points.hike.{$level['value']}"),
            ], $formula['levels']),
            'formula' => [
                'multiplier' => $formula['multiplier'],
                'gain_per_km' => $formula['gainPerKm'],
                'loss_per_km' => $formula['lossPerKm'],
            ],
        ]];
    }

    /**
     * Calculate a route's difficulty.
     *
     * Effort score and level for a distance and elevation, the points a hike like that earns, and up to
     * 3 upcoming hikes of that level.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function calculate(Request $request, DifficultyCalculator $calculator): array
    {
        $validated = $request->validate([
            'km' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'gain' => ['sometimes', 'numeric', 'min:0', 'max:9000'],
            'loss' => ['sometimes', 'numeric', 'min:0', 'max:9000'],
        ]);

        $result = $calculator->calculate((float) $validated['km'], (int) round((float) ($validated['gain'] ?? 0)), (int) round((float) ($validated['loss'] ?? 0)));

        $upcoming = Event::published()->upcoming()
            ->where('difficulty_level', $result->level)
            ->withSeatCounts()
            ->orderBy('starts_on')->orderBy('id')
            ->limit(3)
            ->get();

        return ['data' => [
            'effort_score' => $result->effortScore,
            'difficulty' => [
                'level' => $result->level->value,
                'name' => $result->level->name,
                'label' => $result->level->label(),
                'description' => $result->level->description(),
            ],
            'points' => (int) config("points.hike.{$result->level->value}"),
            'upcoming_hikes' => EventResource::collection($upcoming)->resolve($request),
        ]];
    }
}
