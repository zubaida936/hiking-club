<?php

namespace App\Models;

use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A gallery album, usually the photos of one hike.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?int $event_id
 * @property ?string $description
 * @property ?Carbon $taken_on
 * @property bool $is_published
 */
class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    /**
     * Written only by the Content domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['taken_on' => 'date', 'is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        // The photo rows go with the album (cascade); their files are removed here.
        static::deleting(function (self $album) {
            $paths = $album->photos()->pluck('path')->all();
            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<AlbumPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AlbumPhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The first photo is the album cover.
     */
    public function coverUrl(): ?string
    {
        return $this->photos->first()?->url();
    }
}
