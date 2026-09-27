<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores product photos on the "uploads" disk (public/uploads/products).
 * When PHP's GD extension is available, photos are shrunk to at most
 * 800px and saved as WebP so the register grid loads fast; without GD
 * the original file is kept as-is.
 */
class ProductImageService
{
    public const MAX_SIDE = 800;

    public function store(UploadedFile $file): string
    {
        $name = 'products/'.now()->format('Y/m').'/'.Str::random(24);

        if ($optimised = $this->optimise($file->getRealPath())) {
            Storage::disk('uploads')->put($name.'.webp', $optimised);

            return $name.'.webp';
        }

        $path = $name.'.'.strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        Storage::disk('uploads')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);
        }
    }

    private function optimise(string $source): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));
        if (! $image) {
            return null;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, self::MAX_SIDE / max($w, $h));

        if ($scale < 1) {
            $resized = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $w, $h);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        $ok = imagewebp($image, null, 82);
        $data = ob_get_clean();
        imagedestroy($image);

        return $ok && $data ? $data : null;
    }
}
