<?php

namespace App\Domain\Content\Data;

use Illuminate\Http\UploadedFile;

/**
 * A homepage hero slide as edited in the CMS.
 */
final readonly class HeroSlideData
{
    public function __construct(
        public ?string $eyebrow,
        public string $heading,
        public ?string $text,
        public ?string $ctaLabel,
        public ?string $ctaUrl,
        public int $position,
        public bool $isActive,
        public ?UploadedFile $image = null,
        public bool $removeImage = false,
    ) {}
}
