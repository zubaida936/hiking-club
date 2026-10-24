@php
    $publishDate = $article->published_at?->setTimezone(config('site.timezone'))->format('Y-m-d');
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
        <x-admin.input name="title" label="Title" :value="$article->title" required maxlength="160" />
        <x-admin.input name="excerpt" label="Short summary" :value="$article->excerpt" maxlength="300"
                       hint="Shown on the article list. Leave empty to use the start of the text." />
        <x-admin.textarea name="body" label="Text" :value="$article->body" rows="18" required
                          hint="Markdown: ## Heading, **bold**, *italic*, - list item, [link text](https://…). Leave an empty line between paragraphs." />
    </section>

    <div class="space-y-6">
        <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Publishing</h2>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300" @checked(old('is_published', $article->published_at !== null))> Published
            </label>
            <x-admin.input name="published_at" label="Publish date" type="date" :value="$publishDate"
                           hint="Empty = today. A future date schedules the article. Unticked = draft." />
            <x-admin.input name="author_name" label="Author" :value="$article->author_name" required maxlength="80" />
            <x-admin.input name="slug" label="URL slug" :value="$article->slug" maxlength="180" hint="Leave empty to generate from the title." />
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Cover photo</h2>
            @if ($article->coverUrl())
                <img src="{{ $article->coverUrl() }}" alt="" class="mb-2 aspect-3/2 w-full rounded object-cover">
                <label class="mb-4 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_cover" value="1" class="rounded border-slate-300"> Remove photo
                </label>
            @endif
            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP, max 10 MB. Landscape works best (3:2).</p>
            @error('cover') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>
</div>
