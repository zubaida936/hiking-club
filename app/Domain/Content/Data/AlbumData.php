<?php

namespace App\Domain\Content\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * A gallery album as edited in the CMS.
 */
final readonly class AlbumData
{
    /**
     * @param  list<UploadedFile>  $newPhotos  added after the existing ones
     * @param  list<int>  $removePhotoIds
     * @param  ?int  $coverPhotoId  moved to the front
     */
    public function __construct(
        public string $title,
        public ?string $slug,
        public ?int $eventId,
        public ?string $description,
        public ?Carbon $takenOn,
        public bool $isPublished,
        public array $newPhotos = [],
        public array $removePhotoIds = [],
        public ?int $coverPhotoId = null,
    ) {}
}
