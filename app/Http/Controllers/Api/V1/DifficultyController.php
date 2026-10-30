<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
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
     */
    public function levels(DifficultyCalculator $calculator): JsonResponse
    {
        $formula = $calculator->toArray();

        // A loop with casts (not array_map) so the API docs type every field.
        $levels = [];
        foreach ($formula['levels'] as $bounds) {
            $levels[] = $this->level(DifficultyLevel::from($bounds['value']), $bounds['max']);
        }

        return response()->json(['data' => [
            'levels' => $levels,
            'formula' => [
                'multiplier' => (float) $formula['multiplier'],
                'gain_per_km' => (float) $formula['gainPerKm'],
                'loss_per_km' => (float) $formula['lossPerKm'],
            ],
        ]]);
    }

    /**
     * Calculate a route's difficulty.
     *
     * Effort score and level for a distance and elevation, the points a hike like that earns, and up to
     * 3 upcoming hikes of that level.
     *
     * @unauthenticated
     */
    public function calculate(Request $request, DifficultyCalculator $calculator): JsonResponse
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

        return response()->json(['data' => [
            'effort_score' => $result->effortScore,
            'difficulty' => [
                'level' => $result->level->value,
                'name' => $result->level->name,
                'label' => $result->level->label(),
                'description' => $result->level->description(),
            ],
            'points' => (int) config("points.hike.{$result->level->value}"),
            'upcoming_hikes' => EventResource::collection($upcoming),
        ]]);
    }

    /**
     * One level for the explanation screen; `max_score` is the highest effort score of the level, null for the last one.
     * (Its cast looks redundant, but without it the API docs type the field as a string. Comments next to array keys
     * become API field descriptions, so this one lives here.)
     *
     * @return array{level: int, name: string, label: string, description: string, max_score: int|null, points: int}
     */
    private function level(DifficultyLevel $level, ?int $maxScore): array
    {
        return [
            'level' => $level->value,
            'name' => $level->name,
            'label' => $level->label(),
            'description' => $level->description(),
            'max_score' => $maxScore === null ? null : (int) $maxScore,
            'points' => (int) config("points.hike.{$level->value}"),
        ];
    }
}
