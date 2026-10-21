<?php

namespace App\Domain\Events\Difficulty;

use App\Domain\Events\Enums\DifficultyLevel;

final readonly class Difficulty
{
    public function __construct(
        public int $effortScore,
        public DifficultyLevel $level,
    ) {}
}
