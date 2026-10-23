<?php

namespace App\Domain\Events\Data;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

final readonly class EventData
{
    /**
     * @param  list<string>  $included
     * @param  list<string>  $whatToBring
     * @param  list<string>  $itinerary
     * @param  list<MeetingPointData>  $meetingPoints
     */
    public function __construct(
        public string $title,
        public ?string $slug,
        public ?string $seasonTag,
        public string $location,
        public CarbonImmutable $startsOn,
        public ?string $summary,
        public ?string $description,
        public float $distanceKm,
        public int $elevationGainM,
        public int $elevationLossM,
        public ?float $durationHours,
        public int $feeCents,
        public int $capacity,
        public array $included,
        public array $whatToBring,
        public array $itinerary,
        public bool $isXtreme,
        public array $meetingPoints,
        public ?string $photoCredit = null,
        public ?UploadedFile $coverImage = null,
        public bool $removeCoverImage = false,
        public ?UploadedFile $gpxFile = null,
        public bool $removeGpx = false,
    ) {}
}
