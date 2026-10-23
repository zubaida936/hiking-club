<?php

namespace App\Http\Requests\Site;

use App\Domain\XEvents\Data\XApplicationData;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class XApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = fn (string $group) => array_keys((array) config("xevents.{$group}"));

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:'.Phone::PATTERN],
            'email' => ['nullable', 'string', 'email:rfc', 'max:190'],
            'age' => ['required', 'integer', 'min:14', 'max:90'],
            'city' => ['required', 'string', 'max:80'],
            'guardian_name' => [Rule::requiredIf($this->isMinor()), 'nullable', 'string', 'max:120'],
            'guardian_phone' => [Rule::requiredIf($this->isMinor()), 'nullable', 'string', 'regex:'.Phone::PATTERN, 'different:phone'],

            'hike_frequency' => ['required', Rule::in($keys('frequency'))],
            'hardest_route' => ['required', 'string', 'max:150'],
            'hardest_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'hardest_km' => ['required', 'numeric', 'min:1', 'max:200'],
            'hardest_elevation_m' => ['required', 'integer', 'min:0', 'max:9000'],
            'hardest_duration_hours' => ['required', 'numeric', 'min:0.5', 'max:99'],
            'longest_km' => ['required', 'numeric', 'min:1', 'max:200'],
            'max_ascent_m' => ['required', 'integer', 'min:0', 'max:9000'],
            'pace' => ['required', Rule::in($keys('pace'))],
            'steady_pace' => ['required', 'boolean'],
            'main_challenge' => ['required', 'string', 'max:1000'],
            'track_link' => ['nullable', 'url:https,http', 'max:255'],
            'experience' => ['nullable', 'array'],
            'experience.*' => [Rule::in($keys('experience'))],
            'gear' => ['nullable', 'array'],
            'gear.*' => [Rule::in($keys('gear'))],
            'limitations' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            // Honeypot: hidden from people, bots fill it in.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'WhatsApp number',
            'guardian_name' => "parent or guardian's name",
            'guardian_phone' => "parent or guardian's phone",
            'hike_frequency' => 'how often you hike',
            'hardest_route' => 'hardest hike',
            'hardest_km' => 'distance of your hardest hike',
            'hardest_elevation_m' => 'elevation gain of your hardest hike',
            'hardest_duration_hours' => 'duration of your hardest hike',
            'longest_km' => 'longest distance',
            'max_ascent_m' => 'biggest ascent',
            'steady_pace' => 'steady pace',
            'main_challenge' => 'main challenge',
            'track_link' => 'Strava / Wikiloc link',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, e.g. +961 70 123 456.',
            'guardian_phone.regex' => 'Enter a valid phone number, e.g. +961 3 123 456.',
            'guardian_name.required' => 'Applicants under '.config('xevents.guardian_under_age').' need a parent or guardian.',
            'guardian_phone.required' => 'Applicants under '.config('xevents.guardian_under_age').' need a parent or guardian.',
            'consent.accepted' => 'Please accept the consent to send your application.',
        ];
    }

    /**
     * Minors need a parent or guardian (from the age entered, never a form flag).
     */
    private function isMinor(): bool
    {
        return is_numeric($this->input('age')) && (int) $this->input('age') < (int) config('xevents.guardian_under_age');
    }

    public function toData(): XApplicationData
    {
        $text = fn (string $key) => $this->filled($key) ? $this->string($key)->trim()->toString() : null;

        return new XApplicationData(
            name: $this->string('name')->squish()->toString(),
            phone: (string) Phone::normalize($this->input('phone')),
            email: $this->filled('email') ? strtolower($this->string('email')->trim()->toString()) : null,
            age: $this->integer('age'),
            city: $this->string('city')->squish()->toString(),
            guardianName: $this->isMinor() ? $text('guardian_name') : null,
            guardianPhone: $this->isMinor() ? Phone::normalize($this->input('guardian_phone')) : null,
            hikeFrequency: $this->string('hike_frequency')->toString(),
            hardestRoute: $this->string('hardest_route')->squish()->toString(),
            hardestDate: $this->filled('hardest_date') ? CarbonImmutable::createFromFormat('!Y-m-d', $this->string('hardest_date')->toString()) ?: null : null,
            hardestKm: round((float) $this->input('hardest_km'), 1),
            hardestElevationM: $this->integer('hardest_elevation_m'),
            hardestDurationHours: round((float) $this->input('hardest_duration_hours'), 1),
            longestKm: round((float) $this->input('longest_km'), 1),
            maxAscentM: $this->integer('max_ascent_m'),
            pace: $this->string('pace')->toString(),
            steadyPace: $this->boolean('steady_pace'),
            mainChallenge: $this->string('main_challenge')->trim()->toString(),
            trackLink: $text('track_link'),
            experience: array_values(array_unique(array_map('strval', (array) $this->input('experience', [])))),
            gear: array_values(array_unique(array_map('strval', (array) $this->input('gear', [])))),
            limitations: $text('limitations'),
        );
    }
}
