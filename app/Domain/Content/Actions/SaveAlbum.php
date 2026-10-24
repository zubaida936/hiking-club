<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Data\AlbumData;
use App\Models\Album;
use App\Support\ImageOptimizer;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creates or updates a gallery album: details, new photos (added at the end),
 * removed photos and the cover (moved to the front).
 */
final class SaveAlbum
{
    private const PHOTO_DIR = 'albums';

    public function handle(AlbumData $data, ?Album $album = null): Album
    {
        $album ??= new Album;

        $stored = array_values(array_filter(array_map(
            fn ($file) => ImageOptimizer::storeJpeg($file, self::PHOTO_DIR),
            $data->newPhotos,
        )));

        $removedPaths = [];

        DB::transaction(function () use ($data, $album, $stored, &$removedPaths) {
            $album->fill([
                'title' => $data->title,
                'slug' => UniqueSlug::for(Album::class, $data->slug ?: ($album->exists ? $album->slug : $data->title), $album->id),
                'event_id' => $data->eventId,
                'description' => $data->description,
                'taken_on' => $data->takenOn,
                'is_published' => $data->isPublished,
            ])->save();

            $removed = $album->photos()->whereIn('id', $data->removePhotoIds)->get();
            $removedPaths = $removed->pluck('path')->all();
            $album->photos()->whereIn('id', $removed->modelKeys())->delete();

            $next = (int) $album->photos()->max('position') + 1;
            foreach ($stored as $i => $path) {
                $album->photos()->create(['path' => $path, 'position' => $next + $i]);
            }

            if ($data->coverPhotoId !== null && $album->photos()->whereKey($data->coverPhotoId)->exists()) {
                $album->photos()->whereKey($data->coverPhotoId)->update(['position' => (int) $album->photos()->min('position') - 1]);
            }
        });

        // Files are removed only once the database change is committed.
        if ($removedPaths !== []) {
            Storage::disk('public')->delete($removedPaths);
        }

        return $album->load('photos');
    }
}
