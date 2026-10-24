<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Data\TeamMemberData;
use Illuminate\Foundation\Http\FormRequest;

class TeamMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'role' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_photo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }

    public function toData(): TeamMemberData
    {
        return new TeamMemberData(
            name: $this->string('name')->squish()->toString(),
            role: $this->string('role')->squish()->toString(),
            bio: $this->filled('bio') ? $this->string('bio')->trim()->toString() : null,
            position: $this->integer('position'),
            isActive: $this->boolean('is_active'),
            photo: $this->file('photo'),
            removePhoto: $this->boolean('remove_photo'),
        );
    }
}
