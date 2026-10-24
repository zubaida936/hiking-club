<?php

namespace App\Domain\Content\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * An article as edited in the CMS.
 */
final readonly class ArticleData
{
    public function __construct(
        public string $title,
        public ?string $slug,
        public ?string $excerpt,
        public string $body,
        public string $authorName,
        public bool $published,
        public ?Carbon $publishedAt,
        public ?UploadedFile $cover = null,
        public bool $removeCover = false,
    ) {}
}
