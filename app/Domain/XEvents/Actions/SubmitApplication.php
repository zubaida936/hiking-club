<?php

namespace App\Domain\XEvents\Actions;

use App\Domain\Members\Actions\ResolveHiker;
use App\Domain\XEvents\Data\XApplicationData;
use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Domain\XEvents\Exceptions\XApplicationException;
use App\Models\Hiker;
use App\Models\XApplication;
use Illuminate\Support\Facades\DB;

/**
 * "Apply once": a hiker (identified by WhatsApp number) sends one application;
 * they can apply again only after a rejection or a revoked access.
 */
final class SubmitApplication
{
    public function __construct(private readonly ResolveHiker $resolveHiker) {}

    /**
     * @throws XApplicationException
     */
    public function handle(XApplicationData $data): XApplication
    {
        return DB::transaction(function () use ($data) {
            $hiker = $this->resolveHiker->handle($data->phone, $data->name, $data->email);
            Hiker::whereKey($hiker->id)->lockForUpdate()->first();

            if ($hiker->x_approved_at !== null) {
                throw XApplicationException::alreadyApproved();
            }
            if ($hiker->xApplications()->where('status', XApplicationStatus::Pending)->exists()) {
                throw XApplicationException::alreadyApplied();
            }

            return $hiker->xApplications()->create([
                'status' => XApplicationStatus::Pending,
                'name' => $data->name,
                'phone' => $data->phone,
                'email' => $data->email,
                'age' => $data->age,
                'city' => $data->city,
                'guardian_name' => $data->guardianName,
                'guardian_phone' => $data->guardianPhone,
                'hike_frequency' => $data->hikeFrequency,
                'hardest_route' => $data->hardestRoute,
                'hardest_date' => $data->hardestDate?->toDateString(),
                'hardest_km' => $data->hardestKm,
                'hardest_elevation_m' => $data->hardestElevationM,
                'hardest_duration_hours' => $data->hardestDurationHours,
                'longest_km' => $data->longestKm,
                'max_ascent_m' => $data->maxAscentM,
                'pace' => $data->pace,
                'steady_pace' => $data->steadyPace,
                'main_challenge' => $data->mainChallenge,
                'track_link' => $data->trackLink,
                'experience' => $data->experience,
                'gear' => $data->gear,
                'limitations' => $data->limitations,
                'consent_at' => now(),
            ]);
        });
    }
}
