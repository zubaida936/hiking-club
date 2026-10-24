<?php

namespace App\Domain\Content\Data;

use Illuminate\Http\UploadedFile;

/**
 * A team member as edited in the CMS.
 */
final readonly class TeamMemberData
{
    public function __construct(
        public string $name,
        public string $role,
        public ?string $bio,
        public int $position,
        public bool $isActive,
        public ?UploadedFile $photo = null,
        public bool $removePhoto = false,
    ) {}
}
