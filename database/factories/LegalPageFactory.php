<?php

namespace Database\Factories;

use App\Domain\Content\Enums\LegalDocument;
use App\Models\LegalPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalPage>
 */
class LegalPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document' => LegalDocument::Privacy,
            'title' => 'Privacy Policy',
            'body' => "## What we collect\n\nYour name, phone and email when you book a hike.",
        ];
    }
}
