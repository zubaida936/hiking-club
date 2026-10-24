<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

final class UniqueSlug
{
    /**
     * Build a slug from $source that is unique in $model's table (soft-deleted
     * rows included, since the unique index covers them), appending -2, -3…
     * $reserved slugs are treated as taken (e.g. a fixed page under the same URL).
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $reserved
     */
    public static function for(string $model, string $source, ?int $ignoreId = null, string $column = 'slug', array $reserved = []): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;

        while (in_array($slug, $reserved, true) || self::exists($model, $column, $slug, $ignoreId)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function exists(string $model, string $column, string $slug, ?int $ignoreId): bool
    {
        // Include soft-deleted rows: the unique index covers them too.
        return $model::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->where($column, $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
