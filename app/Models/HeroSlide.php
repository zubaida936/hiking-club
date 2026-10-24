<?php

namespace App\Models;

use Database\Factories\HeroSlideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * One slide of the homepage hero carousel.
 *
 * @property int $id
 * @property ?string $eyebrow
 * @property string $heading
 * @property ?string $text
 * @property ?string $cta_label
 * @property ?string $cta_url site path ("/events") or full https:// link
 * @property ?string $image_path
 * @property int $position
 * @property bool $is_active
 */
class HeroSlide extends Model
{
    /** @use HasFactory<HeroSlideFactory> */
    use HasFactory;

    /**
     * Written only by the Content domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $slide) => $slide->image_path && Storage::disk('public')->delete($slide->image_path));
    }

    /**
     * Active slides in carousel order.
     *
     * @param  Builder<self>  $query
     */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * The button link as an absolute URL ("/events" -> "http://site/events").
     */
    public function ctaHref(): ?string
    {
        return $this->cta_url ? url($this->cta_url) : null;
    }
}
