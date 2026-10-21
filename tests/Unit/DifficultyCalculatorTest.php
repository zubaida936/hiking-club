<?php

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\DifficultyLevel;

function calculator(): DifficultyCalculator
{
    return new DifficultyCalculator(8, 100, 300, ['foundation' => 99, 'endurance' => 160, 'advanced' => 220]);
}

it('computes the effort score from distance, gain and loss', function () {
    // 8 + 220/100 + 320/300 = 11.27 -> × 8 = 90.1 -> 90
    $d = calculator()->calculate(8.0, 220, 320);

    expect($d->effortScore)->toBe(90)
        ->and($d->level)->toBe(DifficultyLevel::Foundation);
});

it('maps scores to levels with inclusive upper bounds', function (int $score, DifficultyLevel $level) {
    expect(calculator()->levelFor($score))->toBe($level);
})->with([
    [0, DifficultyLevel::Foundation],
    [99, DifficultyLevel::Foundation],
    [100, DifficultyLevel::Endurance],
    [160, DifficultyLevel::Endurance],
    [161, DifficultyLevel::Advanced],
    [220, DifficultyLevel::Advanced],
    [221, DifficultyLevel::Xtreme],
]);

it('treats negative input as zero', function () {
    expect(calculator()->calculate(-5, -100, -100)->effortScore)->toBe(0);
});

it('rejects a non-positive divisor', function () {
    new DifficultyCalculator(8, 0, 300, ['foundation' => 99, 'endurance' => 160, 'advanced' => 220]);
})->throws(InvalidArgumentException::class);

it('exposes the same parameters to the browser', function () {
    $params = calculator()->toArray();

    expect($params['multiplier'])->toEqual(8)
        ->and($params['gainPerKm'])->toEqual(100)
        ->and($params['levels'])->toHaveCount(4)
        ->and($params['levels'][3]['max'])->toBeNull();
});
