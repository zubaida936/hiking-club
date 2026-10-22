<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded photos web-ready: rotated upright (EXIF), scaled down to
 * fit $maxSize px and saved as progressive JPEG. JPEG rather than WebP
 * because every link-preview crawler (WhatsApp, Facebook, X) accepts it.
 */
final class ImageOptimizer
{
    public static function storeJpeg(UploadedFile $file, string $dir, string $disk = 'public', int $maxSize = 1920, int $quality = 82): ?string
    {
        $image = self::load($file);

        // Formats GD can't read are stored untouched rather than rejected.
        if ($image === null) {
            return $file->store($dir, $disk) ?: null;
        }

        $image = self::scaleDown(self::orient($image, $file), $maxSize);

        // Flatten transparency (PNG/WebP) onto white, since JPEG has no alpha.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imageinterlace($canvas, true);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $jpeg = (string) ob_get_clean();

        $path = trim($dir, '/').'/'.Str::random(40).'.jpg';

        return Storage::disk($disk)->put($path, $jpeg) ? $path : null;
    }

    private static function load(UploadedFile $file): ?GdImage
    {
        $contents = @file_get_contents($file->getRealPath());
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;

        return $image instanceof GdImage ? $image : null;
    }

    private static function orient(GdImage $image, UploadedFile $file): GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($file->getRealPath()) : false;

        $rotated = match ((int) (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private static function scaleDown(GdImage $image, int $maxSize): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $maxSize / max($width, $height);

        if ($ratio >= 1) {
            return $image;
        }

        $scaled = imagescale($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)), IMG_BICUBIC);

        return $scaled instanceof GdImage ? $scaled : $image;
    }
}
