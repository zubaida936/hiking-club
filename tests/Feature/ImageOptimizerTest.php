<?php

use App\Domain\Identity\Enums\Role;
use App\Models\Event;
use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function storedSize(string $path): array
{
    return getimagesizefromstring(Storage::disk('public')->get($path)) ?: [];
}

it('scales big photos down to 1920 px and saves them as JPEG', function () {
    $path = ImageOptimizer::storeJpeg(UploadedFile::fake()->image('huge.png', 4000, 3000), 'events');

    expect($path)->toStartWith('events/')->toEndWith('.jpg');

    [$width, $height, $type] = storedSize($path);
    expect([$width, $height, $type])->toBe([1920, 1440, IMAGETYPE_JPEG]);
});

it('keeps small images at their size', function () {
    $path = ImageOptimizer::storeJpeg(UploadedFile::fake()->image('small.jpg', 1200, 600), 'events');

    expect(array_slice(storedSize($path), 0, 2))->toBe([1200, 600]);
});

it('scales portrait photos by their height', function () {
    $path = ImageOptimizer::storeJpeg(UploadedFile::fake()->image('tall.jpg', 2000, 4000), 'events');

    expect(array_slice(storedSize($path), 0, 2))->toBe([960, 1920]);
});

it('makes uploads much smaller', function () {
    // Like a phone photo: 4000×3000, lots of detail, saved at maximum JPEG quality.
    $gd = imagecreatetruecolor(4000, 3000);
    for ($i = 0; $i < 20000; $i++) {
        imagefilledellipse($gd, random_int(0, 3999), random_int(0, 2999), random_int(5, 80), random_int(5, 80), random_int(0, 0xFFFFFF));
    }
    $tmp = tempnam(sys_get_temp_dir(), 'img').'.jpg';
    imagejpeg($gd, $tmp, 100);
    $upload = new UploadedFile($tmp, 'photo.jpg', 'image/jpeg', null, true);

    $path = ImageOptimizer::storeJpeg($upload, 'events');

    expect(Storage::disk('public')->size($path))->toBeLessThan(filesize($tmp) / 2);
});

it('stores files GD cannot read untouched', function () {
    $path = ImageOptimizer::storeJpeg(UploadedFile::fake()->create('notes.txt', 1, 'text/plain'), 'events');

    expect($path)->toStartWith('events/')->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('optimizes event cover uploads from the CMS', function () {
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->post(route('admin.events.store'), [
        'title' => 'Big Photo Hike', 'location' => 'Ehden', 'starts_on' => now()->addWeek()->format('Y-m-d'),
        'distance_km' => '8', 'elevation_gain_m' => '200', 'elevation_loss_m' => '200', 'fee' => '30', 'capacity' => '20',
        'cover_image' => UploadedFile::fake()->image('cover.png', 3840, 2160),
    ])->assertRedirect();

    $path = Event::where('title', 'Big Photo Hike')->value('cover_image_path');

    expect($path)->toEndWith('.jpg')
        ->and(array_slice(storedSize($path), 0, 2))->toBe([1920, 1080]);
});
