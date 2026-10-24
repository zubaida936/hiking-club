<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Data\ArticleData;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Article|null $article */
        $article = $this->route('article');

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('articles', 'slug')->ignore($article)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:100000'],
            'author_name' => ['required', 'string', 'max:80'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date_format:Y-m-d'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_cover' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['author_name' => 'author', 'published_at' => 'publish date', 'cover' => 'cover photo'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_cover' => $this->boolean('remove_cover'),
            'slug' => $this->filled('slug') ? strtolower(trim($this->string('slug')->toString())) : null,
        ]);
    }

    public function toData(): ArticleData
    {
        return new ArticleData(
            title: $this->string('title')->squish()->toString(),
            slug: $this->input('slug'),
            excerpt: $this->filled('excerpt') ? $this->string('excerpt')->squish()->toString() : null,
            body: $this->string('body')->trim()->toString(),
            authorName: $this->string('author_name')->squish()->toString(),
            published: $this->boolean('is_published'),
            // A date is a day in the club's timezone: the article goes live at its start.
            publishedAt: $this->filled('published_at')
                ? Carbon::createFromFormat('Y-m-d', $this->string('published_at')->toString(), config('site.timezone'))?->startOfDay()->utc()
                : null,
            cover: $this->file('cover'),
            removeCover: $this->boolean('remove_cover'),
        );
    }
}
