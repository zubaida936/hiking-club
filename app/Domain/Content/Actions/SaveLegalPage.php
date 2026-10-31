<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Data\LegalPageData;
use App\Domain\Content\Enums\LegalDocument;
use App\Models\LegalPage;

/**
 * Saves the Privacy Policy or the Terms. Its save date is shown as "Last updated".
 */
final class SaveLegalPage
{
    public function handle(LegalDocument $document, LegalPageData $data): LegalPage
    {
        return LegalPage::updateOrCreate(
            ['document' => $document],
            ['title' => $data->title, 'body' => $data->body],
        );
    }
}
