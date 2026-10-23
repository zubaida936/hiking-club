<?php

namespace App\Domain\XEvents\Data;

use Carbon\CarbonImmutable;

/**
 * Answers of the X Events application form. Phones are normalized.
 */
final readonly class XApplicationData
{
    /**
     * @param  list<string>  $experience  config xevents.experience keys
     * @param  list<string>  $gear  config xevents.gear keys
     */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public int $age,
        public string $city,
        public ?string $guardianName,
        public ?string $guardianPhone,
        public string $hikeFrequency,
        public string $hardestRoute,
        public ?CarbonImmutable $hardestDate,
        public float $hardestKm,
        public int $hardestElevationM,
        public float $hardestDurationHours,
        public float $longestKm,
        public int $maxAscentM,
        public string $pace,
        public bool $steadyPace,
        public string $mainChallenge,
        public ?string $trackLink,
        public array $experience,
        public array $gear,
        public ?string $limitations,
    ) {}
}
