<?php

namespace App\Http\Controllers\Site;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public difficulty calculator. The form is a plain GET (works without JavaScript);
 * the page also updates live in the browser with the same formula parameters.
 */
class DifficultyCalculatorController extends Controller
{
    public function __invoke(Request $request, DifficultyCalculator $calculator): View
    {
        // Out-of-range or missing values are ignored rather than rejected: these are shareable URLs.
        $km = $this->number($request->query('km'), 0.1, 200);
        $gain = $this->number($request->query('gain'), 0, 9000);
        $loss = $this->number($request->query('loss'), 0, 9000);

        $result = $km !== null ? $calculator->calculate($km, (int) round($gain ?? 0), (int) round($loss ?? 0)) : null;

        return view('site.difficulty-calculator', [
            'input' => ['km' => $km, 'gain' => $gain, 'loss' => $loss],
            'result' => $result,
            'calculator' => $calculator->toArray(),
            'levels' => DifficultyLevel::cases(),
            'similar' => $result
                ? Event::published()->upcoming()->where('difficulty_level', $result->level)->orderBy('starts_on')->limit(3)->get()
                : collect(),
        ]);
    }

    private function number(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number >= $min && $number <= $max ? $number : null;
    }
}
