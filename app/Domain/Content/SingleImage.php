<?php

namespace App\Domain\Content;

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The one optional picture of an article, slide or team member: a new upload
 * replaces it, "remove" clears it. The old file is deleted only after the
 * record is saved (call deleteReplaced()), so a failed save never loses it.
 */
final class SingleImage
{
    private ?string $replaced = null;

    public function __construct(
        private readonly string $dir,
        private readonly int $maxSize,
    ) {}

    /**
     * The path to store on the record.
     */
    public function resolve(?string $current, ?UploadedFile $upload, bool $remove): ?string
    {
        $path = $upload ? (ImageOptimizer::storeJpeg($upload, $this->dir, maxSize: $this->maxSize) ?? $current) : ($remove ? null : $current);
        $this->replaced = $current !== null && $current !== $path ? $current : null;

        return $path;
    }

    public function deleteReplaced(): void
    {
        if ($this->replaced !== null) {
            Storage::disk('public')->delete($this->replaced);
            $this->replaced = null;
        }
    }
}
