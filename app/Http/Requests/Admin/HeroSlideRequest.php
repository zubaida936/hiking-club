<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Data\HeroSlideData;
use Illuminate\Foundation\Http\FormRequest;

class HeroSlideRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'heading' => ['required', 'string', 'max:120'],
            'text' => ['nullable', 'string', 'max:300'],
            'cta_label' => ['nullable', 'string', 'max:40', 'required_with:cta_url'],
            // A path on this site ("/events", not "//other.site") or a full http(s) link.
            'cta_url' => ['nullable', 'string', 'max:255', 'required_with:cta_label', 'regex:#^(/(?!/)|https?://)\S*$#i'],
            'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['eyebrow' => 'small title', 'cta_label' => 'button text', 'cta_url' => 'button link', 'image' => 'background photo'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['cta_url.regex' => 'The button link must start with / (a page on this site) or https://.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'cta_url' => $this->filled('cta_url') ? trim($this->string('cta_url')->toString()) : null,
        ]);
    }

    public function toData(): HeroSlideData
    {
        $optional = fn (string $key) => $this->filled($key) ? $this->string($key)->squish()->toString() : null;

        return new HeroSlideData(
            eyebrow: $optional('eyebrow'),
            heading: $this->string('heading')->squish()->toString(),
            text: $optional('text'),
            ctaLabel: $optional('cta_label'),
            ctaUrl: $this->input('cta_url'),
            position: $this->integer('position'),
            isActive: $this->boolean('is_active'),
            image: $this->file('image'),
            removeImage: $this->boolean('remove_image'),
        );
    }
}
