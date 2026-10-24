<?php

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A guide or organizer shown on the About page.
 *
 * @property int $id
 * @property string $name
 * @property string $role e.g. "Founder & lead guide"
 * @property ?string $bio
 * @property ?string $photo_path
 * @property int $position
 * @property bool $is_active
 */
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
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
        static::deleted(fn (self $member) => $member->photo_path && Storage::disk('public')->delete($member->photo_path));
    }

    /**
     * Active members in display order.
     *
     * @param  Builder<self>  $query
     */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
