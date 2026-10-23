<?php

namespace App\Models;

use App\Domain\XEvents\Enums\XApplicationStatus;
use Database\Factories\XApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An application to join X Events.
 *
 * @property int $id
 * @property int $hiker_id
 * @property XApplicationStatus $status
 * @property string $name
 * @property string $phone
 * @property ?string $email
 * @property int $age
 * @property string $city
 * @property ?string $guardian_name
 * @property ?string $guardian_phone
 * @property string $hike_frequency config xevents.frequency key
 * @property string $hardest_route
 * @property ?Carbon $hardest_date
 * @property float $hardest_km
 * @property int $hardest_elevation_m
 * @property float $hardest_duration_hours
 * @property float $longest_km
 * @property int $max_ascent_m
 * @property string $pace config xevents.pace key
 * @property bool $steady_pace
 * @property string $main_challenge
 * @property ?string $track_link
 * @property ?list<string> $experience config xevents.experience keys
 * @property ?list<string> $gear config xevents.gear keys
 * @property ?string $limitations
 * @property Carbon $consent_at
 * @property ?string $internal_note
 * @property ?string $decision_note
 * @property ?int $reviewed_by
 * @property ?Carbon $reviewed_at
 * @property ?Carbon $access_sent_at
 * @property Carbon $created_at
 */
class XApplication extends Model
{
    /** @use HasFactory<XApplicationFactory> */
    use HasFactory;

    /**
     * Written only by the XEvents domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => XApplicationStatus::class,
            'age' => 'integer',
            'hardest_date' => 'date',
            'hardest_km' => 'float',
            'hardest_elevation_m' => 'integer',
            'hardest_duration_hours' => 'float',
            'longest_km' => 'float',
            'max_ascent_m' => 'integer',
            'steady_pace' => 'boolean',
            'experience' => 'array',
            'gear' => 'array',
            'consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'access_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Hiker, $this>
     */
    public function hiker(): BelongsTo
    {
        return $this->belongsTo(Hiker::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isMinor(): bool
    {
        return $this->age < (int) config('xevents.guardian_under_age');
    }

    /**
     * Labels for the stored option keys (config/xevents.php).
     *
     * @param  'frequency'|'pace'|'experience'|'gear'  $group
     * @return list<string>
     */
    public function labels(string $group): array
    {
        $keys = match ($group) {
            'frequency' => [$this->hike_frequency],
            'pace' => [$this->pace],
            'experience' => $this->experience ?? [],
            'gear' => $this->gear ?? [],
        };

        return array_map(fn (string $key) => (string) config("xevents.{$group}.{$key}", $key), $keys);
    }
}
