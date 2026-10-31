<?php

namespace App\Domain\Content\Data;

/**
 * A legal page as edited in the CMS.
 */
final readonly class LegalPageData
{
    public function __construct(
        public string $title,
        public ?string $body,
    ) {}
}
