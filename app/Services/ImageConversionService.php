<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Exceptions\EncoderException;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;
use Throwable;

class ImageConversionService
{
    private const STORAGE_DIRECTORY = 'conversions';

    /**
     * @return array<string, mixed>
     */
    public function convert(UploadedFile $file, string $format, int $quality, ?int $maxWidth): array
    {
        if ($format === 'avif' && ! $this->supportsAvif()) {
            throw new RuntimeException('AVIF belum didukung oleh server ini. Silakan gunakan WebP.');
        }

        try {
            $image = Image::read($file->getRealPath())->orient();
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            if ($maxWidth !== null) {
                $image->scaleDown(width: $maxWidth);
            }

            $convertedName = (string) Str::uuid().'.'.$format;
            $convertedPath = self::STORAGE_DIRECTORY.'/'.$convertedName;
            $absolutePath = Storage::disk('public')->path($convertedPath);

            Storage::disk('public')->makeDirectory(self::STORAGE_DIRECTORY);
            $image->encodeByExtension($format, quality: $quality)->save($absolutePath);

            $convertedSize = Storage::disk('public')->size($convertedPath);
            $originalSize = $file->getSize() ?: 0;

            return [
                'original_name' => $file->getClientOriginalName(),
                'original_mime' => $file->getMimeType(),
                'original_format' => strtoupper((string) $file->getClientOriginalExtension()),
                'original_size' => $originalSize,
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'converted_name' => $convertedName,
                'converted_path' => $convertedPath,
                'converted_download_name' => $this->downloadName($file, $format),
                'converted_mime' => 'image/'.$format,
                'converted_format' => strtoupper($format),
                'converted_size' => $convertedSize,
                'converted_width' => $image->width(),
                'converted_height' => $image->height(),
                'saving_percent' => $this->savingPercent($originalSize, $convertedSize),
                'download_url' => route('conversions.download', ['filename' => $convertedName]),
            ];
        } catch (EncoderException $exception) {
            throw new RuntimeException('Gambar gagal dikonversi. Format output belum didukung server.', 0, $exception);
        } catch (Throwable $exception) {
            throw new RuntimeException('Gambar gagal dikonversi. Pastikan format file valid dan ukuran tidak melebihi batas.', 0, $exception);
        }
    }

    public function supportsAvif(): bool
    {
        return function_exists('imageavif') || extension_loaded('imagick');
    }

    private function savingPercent(int $originalSize, int $convertedSize): float
    {
        if ($originalSize <= 0) {
            return 0.0;
        }

        return round((($originalSize - $convertedSize) / $originalSize) * 100, 2);
    }

    private function downloadName(UploadedFile $file, string $format): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($name);

        return ($slug ?: 'converted-image').'.'.$format;
    }
}
