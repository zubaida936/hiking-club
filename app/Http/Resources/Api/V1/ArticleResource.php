<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A guide article in a list (CMS → Guide articles).
 *
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cover = $this->coverUrl();

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary(),
            'author' => $this->author_name,
            // null when the article has no cover image.
            'cover_url' => $cover === null ? null : (string) $cover,
            'reading_minutes' => $this->readingMinutes(),
            'published_at' => $this->published_at === null ? null : $this->published_at->toIso8601String(),
            'web_url' => route('guide.articles.show', $this->resource),
        ];
    }
}
