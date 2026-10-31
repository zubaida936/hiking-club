<?php

namespace App\Models;

use App\Domain\Content\Enums\LegalDocument;
use Database\Factories\LegalPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A legal page (Privacy Policy, Terms). The body is Markdown.
 *
 * @property int $id
 * @property LegalDocument $document
 * @property string $title
 * @property ?string $body
 * @property ?Carbon $updated_at
 */
class LegalPage extends Model
{
    /** @use HasFactory<LegalPageFactory> */
    use HasFactory;

    /**
     * Written only by the Content domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document' => LegalDocument::class];
    }

    /**
     * The saved page, or an unsaved empty one with the default title.
     */
    public static function for(LegalDocument $document): self
    {
        return self::query()->firstOrNew(['document' => $document], ['title' => $document->defaultTitle()]);
    }

    public function hasContent(): bool
    {
        return trim((string) $this->body) !== '';
    }

    /**
     * The body as HTML. Raw HTML in the Markdown is escaped and javascript: links dropped.
     */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]));
    }
}
