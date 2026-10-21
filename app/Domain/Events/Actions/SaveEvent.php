<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Data\EventData;
use App\Domain\Events\Data\MeetingPointData;
use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creates or updates an event: recalculates difficulty, keeps the slug
 * unique, replaces meeting points and manages the cover image.
 */
final class SaveEvent
{
    private const IMAGE_DIR = 'events';

    public function __construct(private readonly DifficultyCalculator $difficulty) {}

    public function handle(EventData $data, User $actor, ?Event $event = null): Event
    {
        $event ??= new Event(['status' => EventStatus::Draft, 'created_by' => $actor->id]);
        $oldImage = $event->cover_image_path;

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
            'is_xtreme' => $data->isXtreme,
            'updated_by' => $actor->id,
        ]);

        if ($data->coverImage) {
            $event->cover_image_path = $data->coverImage->store(self::IMAGE_DIR, 'public') ?: null;
        } elseif ($data->removeCoverImage) {
            $event->cover_image_path = null;
        }

        try {
            DB::transaction(function () use ($event, $data) {
                $event->save();
                $this->syncMeetingPoints($event, $data->meetingPoints);
            });
        } catch (Throwable $e) {
            // Don't leave an orphaned upload behind when the save fails.
            if ($data->coverImage && $event->cover_image_path) {
                Storage::disk('public')->delete($event->cover_image_path);
            }

            throw $e;
        }

        // Delete the replaced file only once the database change is committed.
        if ($oldImage && $oldImage !== $event->cover_image_path) {
            Storage::disk('public')->delete($oldImage);
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
