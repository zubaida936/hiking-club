<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Models\Event;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Filters of the hike lists (upcoming and past). A FormRequest so the API docs, and the app's generated
 * types, list every filter. Unknown values are rejected (422): the app builds them, a wrong one is a bug.
 */
class EventFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'level' => ['sometimes', Rule::enum(DifficultyLevel::class)],
            'month' => ['sometimes', 'date_format:Y-m'],
            // Upcoming list only: true = only X events, false = no X events.
            'xtreme' => ['sometimes', 'boolean'],
            // Words in the title or the place ("cedars", "Ehden").
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * The level, month and search filters as a query constraint.
     *
     * @return Closure(Builder<Event>): Builder<Event>
     */
    public function filters(): Closure
    {
        $level = $this->has('level') ? DifficultyLevel::from($this->integer('level')) : null;
        $month = $this->has('month') ? Carbon::createFromFormat('!Y-m', (string) $this->query('month')) : null;
        $search = trim((string) $this->query('search')) ?: null;

        return function (Builder $q) use ($search, $level, $month) {
            /** @var Builder<Event> $q */
            return $q
                ->search($search)
                ->when($level, fn (Builder $q) => $q->where('difficulty_level', $level))
                ->when($month, fn (Builder $q) => $q->whereBetween('starts_on', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ]));
        };
    }
}
