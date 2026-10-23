<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Data\EventData;
use App\Domain\Events\Data\MeetingPointData;
use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\EventStatus;
use App\Domain\Events\Gpx\GpxReader;
use App\Models\Event;
use App\Models\User;
use App\Support\ImageOptimizer;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creates or updates an event: recalculates difficulty, keeps the slug
 * unique, replaces meeting points and manages the cover image and GPX route.
 */
final class SaveEvent
{
    private const IMAGE_DIR = 'events';

    private const GPX_DIR = 'events/gpx';

    public function __construct(
        private readonly DifficultyCalculator $difficulty,
        private readonly GpxReader $gpx,
    ) {}

    public function handle(EventData $data, User $actor, ?Event $event = null): Event
    {
        $event ??= new Event(['status' => EventStatus::Draft, 'created_by' => $actor->id]);
        $oldFiles = array_filter([$event->cover_image_path, $event->gpx_path]);

        $difficulty = $this->difficulty->calculate($data->distanceKm, $data->elevationGainM, $data->elevationLossM);

        $event->fill([
            'title' => $data->title,
            // An empty slug keeps the current one on update, so public links stay stable.
            'slug' => UniqueSlug::for(Event::class, $data->slug ?: ($event->exists ? $event->slug : $data->title), $event->id),
            'season_tag' => $data->seasonTag,
            'location' => $data->location,
            'starts_on' => $data->startsOn->toDateString(),
            'summary' => $data->summary,
            'description' => $data->description,
            'distance_km' => $data->distanceKm,
            'elevation_gain_m' => $data->elevationGainM,
            'elevation_loss_m' => $data->elevationLossM,
            'duration_hours' => $data->durationHours,
            'effort_score' => $difficulty->effortScore,
            'difficulty_level' => $difficulty->level,
            'fee_cents' => $data->feeCents,
            'capacity' => $data->capacity,
            'included' => $data->included,
            'what_to_bring' => $data->whatToBring,
            'itinerary' => $data->itinerary,
            'photo_credit' => $data->photoCredit,
            'is_xtreme' => $data->isXtreme,
            'updated_by' => $actor->id,
        ]);

        /** @var list<?string> $newFiles */
        $newFiles = [];

        if ($data->coverImage) {
            $event->cover_image_path = $newFiles[] = ImageOptimizer::storeJpeg($data->coverImage, self::IMAGE_DIR);
        } elseif ($data->removeCoverImage) {
            $event->cover_image_path = null;
        }

        if ($data->gpxFile) {
            $event->route_points = $this->gpx->points((string) $data->gpxFile->get());
            $event->gpx_path = $newFiles[] = $data->gpxFile->storeAs(self::GPX_DIR, Str::random(40).'.gpx', 'public') ?: null;
        } elseif ($data->removeGpx) {
            $event->gpx_path = null;
            $event->route_points = null;
        }

        try {
            DB::transaction(function () use ($event, $data) {
                $event->save();
                $this->syncMeetingPoints($event, $data->meetingPoints);
            });
        } catch (Throwable $e) {
            // Don't leave orphaned uploads behind when the save fails.
            Storage::disk('public')->delete(array_filter($newFiles));

            throw $e;
        }

        // Delete replaced files only once the database change is committed.
        $replaced = array_diff($oldFiles, [$event->cover_image_path, $event->gpx_path]);
        if ($replaced !== []) {
            Storage::disk('public')->delete(array_values($replaced));
        }

        return $event->load('meetingPoints');
    }

    /**
     * @param  list<MeetingPointData>  $points
     */
    private function syncMeetingPoints(Event $event, array $points): void
    {
        $event->meetingPoints()->delete();

        foreach ($points as $position => $point) {
            $event->meetingPoints()->create([
                'name' => $point->name,
                'pickup_time' => $point->pickupTime,
                'position' => $position,
            ]);
        }
    }
}
