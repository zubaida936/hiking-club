<?php

namespace App\Domain\Shop\Data;

use Illuminate\Http\UploadedFile;

/**
 * A product as edited in the CMS.
 */
final readonly class ProductData
{
    /**
     * @param  list<string>  $tags
     * @param  list<array{id: ?int, name: string, stock: int}>  $variants  in display order
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeImageIds
     */
    public function __construct(
        public int $categoryId,
        public string $name,
        public ?string $slug,
        public ?string $description,
        public int $priceCents,
        public ?int $salePriceCents,
        public array $tags,
        public bool $isActive,
        public array $variants,
        public array $newImages = [],
        public array $removeImageIds = [],
    ) {}
}
