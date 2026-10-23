<?php

namespace App\Models;

use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use Database\Factories\HikerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person who books hikes, identified by phone number. Owns bookings and points.
 *
 * @property int $id
 * @property string $phone normalized: "+" and digits
 * @property string $name
 * @property ?string $email
 * @property ?int $user_id
 */
class Hiker extends Model
{
    /** @use HasFactory<HikerFactory> */
    use HasFactory;

    /**
     * Written only by domain actions with explicit attribute arrays.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<PointTransaction, $this>
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Adds total_points and hikes_completed.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithPointTotals(Builder $query): void
    {
        $query->withSum('pointTransactions as total_points', 'points')
            ->withCount(['registrations as hikes_completed' => fn ($q) => $q
                ->where('status', RegistrationStatus::Confirmed)
                ->where('attendance', Attendance::Attended)]);
    }

    public function totalPoints(): int
    {
        return (int) ($this->attributes['total_points'] ?? $this->pointTransactions()->sum('points'));
    }
}
