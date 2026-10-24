<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A Hiker's Guide article (trails, peaks, water needs...). The body is Markdown.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property string $body
 * @property string $author_name
 * @property ?string $cover_path
 * @property ?Carbon $published_at null = draft, future = scheduled
 * @property Carbon $created_at
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * Written only by the Content domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $article) => $article->cover_path && Storage::disk('public')->delete($article->cover_path));
    }

    /**
     * Visible on the site: published, and the publish date has come.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * The body as HTML. Raw HTML in the Markdown is escaped and javascript: links dropped.
     */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }

    /**
     * Estimated reading time at about 200 words a minute.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags($this->body)) / 200));
    }

    /**
     * The excerpt, or the start of the body as plain text (Blade escapes it when shown).
     */
    public function summary(int $length = 200): string
    {
        $text = html_entity_decode(strip_tags($this->bodyHtml()->toHtml()), ENT_QUOTES | ENT_HTML5);

        return $this->excerpt ?: Str::limit(trim((string) preg_replace('/\s+/', ' ', $text)), $length);
    }
}
