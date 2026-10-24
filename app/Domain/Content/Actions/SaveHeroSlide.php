<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Data\HeroSlideData;
use App\Domain\Content\SingleImage;
use App\Models\HeroSlide;

final class SaveHeroSlide
{
    public function handle(HeroSlideData $data, ?HeroSlide $slide = null): HeroSlide
    {
        $slide ??= new HeroSlide;
        $image = new SingleImage('hero', 1920);

        $slide->fill([
            'eyebrow' => $data->eyebrow,
            'heading' => $data->heading,
            'text' => $data->text,
            'cta_label' => $data->ctaLabel,
            'cta_url' => $data->ctaUrl,
            'image_path' => $image->resolve($slide->image_path, $data->image, $data->removeImage),
            'position' => $data->position,
            'is_active' => $data->isActive,
        ])->save();

        $image->deleteReplaced();

        return $slide;
    }
}
