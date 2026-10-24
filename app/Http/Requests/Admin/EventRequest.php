<?php

namespace App\Http\Requests\Admin;

use App\Domain\Events\Data\EventData;
use App\Domain\Events\Data\MeetingPointData;
use App\Domain\Events\Gpx\GpxReader;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event|null $event */
        $event = $this->route('event');

        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash:ascii', Rule::notIn(Event::RESERVED_SLUGS), Rule::unique('events', 'slug')->ignore($event)],
            'season_tag' => ['nullable', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'elevation_gain_m' => ['required', 'integer', 'min:0', 'max:9000'],
            'elevation_loss_m' => ['required', 'integer', 'min:0', 'max:9000'],
            'duration_hours' => ['nullable', 'numeric', 'min:0.5', 'max:72'],
            'fee' => ['required', 'numeric', 'min:0', 'max:10000', 'decimal:0,2'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            // Club local time (config site.timezone); at the latest during the hike day.
            'registration_closes_at' => ['nullable', 'date_format:Y-m-d\TH:i', function (string $attribute, mixed $value, Closure $fail) {
                $startsOn = (string) $this->input('starts_on');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsOn) && substr((string) $value, 0, 10) > $startsOn) {
                    $fail('Bookings must close on or before the day of the hike.');
                }
            }],
            'waitlist_enabled' => ['boolean'],
            'included' => ['nullable', 'string', 'max:2000'],
            'what_to_bring' => ['nullable', 'string', 'max:2000'],
            'itinerary' => ['nullable', 'string', 'max:3000'],
            'is_xtreme' => ['boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=800,min_height=400'],
            'remove_cover_image' => ['boolean'],
            'photo_credit' => ['nullable', 'string', 'max:120'],
            'gpx_file' => ['nullable', 'file', 'extensions:gpx', 'max:5120', function (string $attribute, mixed $file, Closure $fail) {
                if ($file instanceof UploadedFile && app(GpxReader::class)->points((string) $file->get()) === []) {
                    $fail('The GPX file has no track or route points we can read.');
                }
            }],
            'remove_gpx' => ['boolean'],
            'meeting_points' => ['nullable', 'array', 'max:10'],
            'meeting_points.*.name' => ['required', 'string', 'max:120'],
            'meeting_points.*.pickup_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'starts_on' => 'date',
            'distance_km' => 'distance',
            'elevation_gain_m' => 'elevation gain',
            'elevation_loss_m' => 'elevation loss',
            'duration_hours' => 'duration',
            'gpx_file' => 'GPX file',
            'registration_closes_at' => 'booking deadline',
            'meeting_points.*.name' => 'meeting point name',
            'meeting_points.*.pickup_time' => 'pickup time',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Rows the organizer left completely empty in the repeater are ignored.
        /** @var array<int, mixed> $rows */
        $rows = (array) $this->input('meeting_points', []);

        $points = collect($rows)
            ->filter(fn ($p) => is_array($p) && (filled($p['name'] ?? null) || filled($p['pickup_time'] ?? null)))
            ->values()
            ->all();

        $this->merge([
            'is_xtreme' => $this->boolean('is_xtreme'),
            'remove_cover_image' => $this->boolean('remove_cover_image'),
            'remove_gpx' => $this->boolean('remove_gpx'),
            'waitlist_enabled' => $this->boolean('waitlist_enabled'),
            'meeting_points' => $points,
            'slug' => $this->filled('slug') ? strtolower(trim($this->string('slug')->toString())) : null,
        ]);
    }

    public function toData(): EventData
    {
        return new EventData(
            title: $this->string('title')->trim()->toString(),
            slug: $this->input('slug'),
            seasonTag: $this->input('season_tag'),
            location: $this->string('location')->trim()->toString(),
            startsOn: CarbonImmutable::createFromFormat('!Y-m-d', $this->string('starts_on')->toString()) ?: CarbonImmutable::today(),
            summary: $this->input('summary'),
            description: $this->input('description'),
            distanceKm: round((float) $this->input('distance_km'), 1),
            elevationGainM: $this->integer('elevation_gain_m'),
            elevationLossM: $this->integer('elevation_loss_m'),
            durationHours: $this->filled('duration_hours') ? round((float) $this->input('duration_hours'), 1) : null,
            feeCents: (int) round((float) $this->input('fee') * 100),
            capacity: $this->integer('capacity'),
            included: $this->lines('included'),
            whatToBring: $this->lines('what_to_bring'),
            itinerary: $this->lines('itinerary', unique: false),
            isXtreme: $this->boolean('is_xtreme'),
            meetingPoints: array_map(
                fn (array $p) => new MeetingPointData(trim($p['name']), $p['pickup_time']),
                $this->validated('meeting_points') ?? [],
            ),
            photoCredit: $this->filled('photo_credit') ? $this->string('photo_credit')->trim()->toString() : null,
            coverImage: $this->file('cover_image'),
            removeCoverImage: $this->boolean('remove_cover_image'),
            gpxFile: $this->file('gpx_file'),
            removeGpx: $this->boolean('remove_gpx'),
            registrationClosesAt: $this->filled('registration_closes_at')
                ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->string('registration_closes_at')->toString(), config('site.timezone'))?->utc()
                : null,
            waitlistEnabled: $this->boolean('waitlist_enabled'),
        );
    }

    /**
     * Textarea with one item per line -> list of trimmed, non-empty strings.
     *
     * @return list<string>
     */
    private function lines(string $key, bool $unique = true): array
    {
        return collect(preg_split('/\R/', $this->string($key)->toString()) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->when($unique, fn ($lines) => $lines->unique())
            ->values()
            ->all();
    }
}
