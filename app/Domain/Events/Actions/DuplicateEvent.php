<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies an event as a new draft (common for recurring routes). The cover
 * image and GPX files are copied too, so deleting one event never breaks the other.
 */
final class DuplicateEvent
{
    public function handle(Event $source, User $actor): Event
    {
        return DB::transaction(function () use ($source, $actor) {
            $copy = $source->replicate([
                'slug', 'status', 'published_at', 'cancelled_at', 'cancellation_reason',
                'cover_image_path', 'gpx_path', 'registration_closes_at', 'created_by', 'updated_by',
            ]);

            $copy->title = Str::limit($source->title.' (copy)', 150, '');
            $copy->slug = UniqueSlug::for(Event::class, $copy->title, reserved: Event::RESERVED_SLUGS);
            $copy->status = EventStatus::Draft;
            $copy->created_by = $actor->id;
            $copy->updated_by = $actor->id;
            $copy->cover_image_path = $this->copyFile($source->cover_image_path);
            $copy->gpx_path = $this->copyFile($source->gpx_path);
            $copy->route_points = $copy->gpx_path ? $source->route_points : null;
            $copy->save();

            foreach ($source->meetingPoints as $point) {
                $copy->meetingPoints()->create($point->only(['name', 'pickup_time', 'position']));
            }

            return $copy;
        });
    }

    private function copyFile(?string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $newPath = dirname($path).'/'.Str::random(40).'.'.pathinfo($path, PATHINFO_EXTENSION);

        return $disk->copy($path, $newPath) ? $newPath : null;
    }
}
