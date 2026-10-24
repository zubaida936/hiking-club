<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Data\AlbumData;
use App\Models\Album;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AlbumRequest extends FormRequest
{
    /**
     * PHP accepts at most 20 files per upload (max_file_uploads) and 25 MB in total (post_max_size).
     */
    public const MAX_PHOTOS_PER_UPLOAD = 20;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Album|null $album */
        $album = $this->route('album');

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash:ascii', Rule::unique('albums', 'slug')->ignore($album)],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'taken_on' => ['nullable', 'date_format:Y-m-d'],
            'is_published' => ['boolean'],
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS_PER_UPLOAD],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['integer'],
            'cover_photo' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['event_id' => 'hike', 'taken_on' => 'date', 'photos.*' => 'photo'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'slug' => $this->filled('slug') ? strtolower(trim($this->string('slug')->toString())) : null,
        ]);
    }

    public function toData(): AlbumData
    {
        return new AlbumData(
            title: $this->string('title')->squish()->toString(),
            slug: $this->input('slug'),
            eventId: $this->filled('event_id') ? $this->integer('event_id') : null,
            description: $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            takenOn: $this->filled('taken_on') ? Carbon::createFromFormat('Y-m-d', $this->string('taken_on')->toString())?->startOfDay() : null,
            isPublished: $this->boolean('is_published'),
            newPhotos: array_values((array) $this->file('photos', [])),
            removePhotoIds: array_map('intval', (array) $this->input('remove_photos', [])),
            coverPhotoId: $this->filled('cover_photo') ? $this->integer('cover_photo') : null,
        );
    }
}
