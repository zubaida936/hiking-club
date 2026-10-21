<?php

namespace App\Domain\Events\Difficulty;

use App\Domain\Events\Enums\DifficultyLevel;
use InvalidArgumentException;

/**
 * Single source of truth for effort score and level. The CMS live preview
 * and the public Difficulty Calculator receive the same parameters via
 * toArray(), so the browser never has its own copy of the numbers.
 */
final readonly class DifficultyCalculator
{
    /**
     * @param  array{foundation: int, endurance: int, advanced: int}  $levels
     */
    public function __construct(
        private float $multiplier,
        private float $gainPerKm,
        private float $lossPerKm,
        private array $levels,
    ) {
        if ($gainPerKm <= 0 || $lossPerKm <= 0) {
            throw new InvalidArgumentException('gain_per_km and loss_per_km must be positive.');
        }
    }

    public static function fromConfig(): self
    {
        /** @var array{multiplier: float, gain_per_km: float, loss_per_km: float, levels: array{foundation: int, endurance: int, advanced: int}} $c */
        $c = config('difficulty');

        return new self($c['multiplier'], $c['gain_per_km'], $c['loss_per_km'], $c['levels']);
    }

    public function calculate(float $distanceKm, int $elevationGainM, int $elevationLossM = 0): Difficulty
    {
        $equivalentKm = max(0, $distanceKm)
            + max(0, $elevationGainM) / $this->gainPerKm
            + max(0, $elevationLossM) / $this->lossPerKm;

        $score = (int) round($this->multiplier * $equivalentKm);

        return new Difficulty($score, $this->levelFor($score));
    }

    public function levelFor(int $score): DifficultyLevel
    {
        return match (true) {
            $score <= $this->levels['foundation'] => DifficultyLevel::Foundation,
            $score <= $this->levels['endurance'] => DifficultyLevel::Endurance,
            $score <= $this->levels['advanced'] => DifficultyLevel::Advanced,
            default => DifficultyLevel::Xtreme,
        };
    }

    /**
     * @return array{multiplier: float, gainPerKm: float, lossPerKm: float, levels: list<array{max: int|null, value: int, label: string}>}
     */
    public function toArray(): array
    {
        return [
            'multiplier' => $this->multiplier,
            'gainPerKm' => $this->gainPerKm,
            'lossPerKm' => $this->lossPerKm,
            'levels' => [
                ['max' => $this->levels['foundation'], 'value' => 1, 'label' => DifficultyLevel::Foundation->label()],
                ['max' => $this->levels['endurance'], 'value' => 2, 'label' => DifficultyLevel::Endurance->label()],
                ['max' => $this->levels['advanced'], 'value' => 3, 'label' => DifficultyLevel::Advanced->label()],
                ['max' => null, 'value' => 4, 'label' => DifficultyLevel::Xtreme->label()],
            ],
        ];
    }
}
