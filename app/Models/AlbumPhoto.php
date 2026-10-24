<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $album_id
 * @property string $path
 * @property int $position
 */
class AlbumPhoto extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Album, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
