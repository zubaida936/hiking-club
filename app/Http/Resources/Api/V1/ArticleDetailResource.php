<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * One guide article: the list fields, the body as safe HTML and a few other recent articles.
 * Pass the other articles with withMore().
 *
 * @mixin Article
 */
class ArticleDetailResource extends ArticleResource
{
    /** @var Collection<int, Article>|null */
    private ?Collection $more = null;

    /**
     * @param  Collection<int, Article>  $more
     */
    public function withMore(Collection $more): static
    {
        $this->more = $more;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Spread, not `+`: the API docs (and the app's types) only follow the spread.
        return [
            ...parent::toArray($request),
            // The article as safe HTML (raw HTML in the text is escaped), for a web view or an HTML renderer.
            'body_html' => (string) $this->bodyHtml()->toHtml(),
            // Up to 3 other recent articles.
            'more' => ArticleResource::collection($this->more ?? collect()),
        ];
    }
}
