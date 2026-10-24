<?php

namespace App\Domain\Content;

use App\Models\HeroSlide;

/**
 * The homepage carousel slides: the active ones from the CMS, or the
 * placeholders in config/site.php ("hero") while the CMS has none.
 */
final class HomepageHero
{
    /**
     * @return list<array{eyebrow: ?string, heading: string, text: ?string, cta: ?string, url: ?string, image: ?string}>
     */
    public function slides(): array
    {
        $slides = HeroSlide::shown()->get();

        if ($slides->isNotEmpty()) {
            return $slides->map(fn (HeroSlide $slide) => [
                'eyebrow' => $slide->eyebrow,
                'heading' => $slide->heading,
                'text' => $slide->text,
                'cta' => $slide->cta_label,
                'url' => $slide->ctaHref(),
                'image' => $slide->imageUrl(),
            ])->values()->all();
        }

        return array_map(fn (array $slide) => [
            'eyebrow' => $slide['season'] ?? null,
            'heading' => $slide['heading'],
            'text' => $slide['text'] ?? null,
            'cta' => $slide['cta'] ?? null,
            'url' => isset($slide['route']) ? route($slide['route']) : null,
            'image' => isset($slide['image']) ? asset($slide['image']) : null,
        ], array_values((array) config('site.hero')));
    }
}
