<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Data\LegalPageData;
use Illuminate\Foundation\Http\FormRequest;

class LegalPageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:100000'],
        ];
    }

    public function toData(): LegalPageData
    {
        return new LegalPageData(
            title: $this->string('title')->squish()->toString(),
            body: $this->filled('body') ? $this->string('body')->trim()->toString() : null,
        );
    }
}
