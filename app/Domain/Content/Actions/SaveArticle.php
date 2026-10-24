<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Data\ArticleData;
use App\Domain\Content\SingleImage;
use App\Models\Article;
use App\Support\UniqueSlug;

/**
 * Creates or updates a guide article. Publishing without a date publishes now
 * (or keeps the original date); a future date schedules it.
 */
final class SaveArticle
{
    public function handle(ArticleData $data, ?Article $article = null): Article
    {
        $article ??= new Article;
        $cover = new SingleImage('articles', 1600);

        $article->fill([
            'title' => $data->title,
            // An empty slug keeps the current one, so shared links keep working.
            'slug' => UniqueSlug::for(Article::class, $data->slug ?: ($article->exists ? $article->slug : $data->title), $article->id),
            'excerpt' => $data->excerpt,
            'body' => $data->body,
            'author_name' => $data->authorName,
            'cover_path' => $cover->resolve($article->cover_path, $data->cover, $data->removeCover),
            'published_at' => $data->published ? ($data->publishedAt ?? $article->published_at ?? now()) : null,
        ])->save();

        $cover->deleteReplaced();

        return $article;
    }
}
